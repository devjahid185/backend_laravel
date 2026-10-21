<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminNotification;
use App\Models\Admin;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\FoodOrderSupportTicket;
use App\Models\NotificationPreference;
use App\Models\RiderSupportTicket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = max(20, min((int) $request->query('per_page', 100), 100));

        $foodTickets = FoodOrderSupportTicket::query()
            ->with(['user:id,name,phone,email', 'order:id,status'])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (FoodOrderSupportTicket $ticket): array => [...$ticket->toArray(), 'type' => 'food']);

        $riderTickets = RiderSupportTicket::query()
            ->with(['rider:id,user_id,name,phone', 'rider.user:id,name,phone,email', 'order:id,status'])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (RiderSupportTicket $ticket): array => [...$ticket->toArray(), 'type' => 'rider']);

        $tickets = $foodTickets
            ->concat($riderTickets)
            ->sortByDesc('updated_at')
            ->values();

        return response()->json([
            'data' => $tickets,
            'counts' => [
                'all' => $tickets->count(),
                'active' => $tickets->whereIn('status', ['open', 'reviewing'])->count(),
                'answered' => $tickets->filter(fn (array $ticket): bool => ! empty($ticket['admin_reply']))->count(),
                'resolved' => $tickets->whereIn('status', ['resolved', 'closed'])->count(),
            ],
        ]);
    }

    public function reply(Request $request, string $type, int $id): JsonResponse
    {
        $data = $request->validate([
            'reply' => ['required', 'string', 'max:4000'],
            'status' => ['nullable', 'in:reviewing,resolved,closed'],
        ]);

        $ticket = $this->findTicket($type, $id);
        $userId = $this->userIdFor($ticket);
        abort_unless($userId, 422, 'এই ticket-এর customer account পাওয়া যায়নি।');

        $status = $data['status'] ?? 'reviewing';
        $admin = $request->user();

        DB::transaction(function () use ($ticket, $data, $status, $admin): void {
            $ticket->admin_reply = trim($data['reply']);
            $ticket->status = $status;
            $ticket->admin_replied_by_id = $admin instanceof Admin ? $admin->id : null;
            $ticket->admin_replied_at = now();

            if ($ticket instanceof FoodOrderSupportTicket) {
                $ticket->resolved_at = $status === 'resolved' ? now() : null;
            }

            $ticket->save();
        });

        $this->notifyUser($userId, $ticket, $type, $admin instanceof Admin ? $admin->id : null);

        return response()->json([
            'message' => 'সাপোর্টের উত্তর পাঠানো হয়েছে।',
            'ticket' => $ticket->fresh()->load($this->relationsFor($type)),
        ]);
    }

    private function findTicket(string $type, int $id): Model
    {
        return match ($type) {
            'food' => FoodOrderSupportTicket::query()->with('user')->findOrFail($id),
            'rider' => RiderSupportTicket::query()->with('rider.user')->findOrFail($id),
            default => abort(404),
        };
    }

    private function userIdFor(Model $ticket): ?int
    {
        if ($ticket instanceof FoodOrderSupportTicket) {
            return $ticket->user_id ? (int) $ticket->user_id : null;
        }

        return $ticket->rider?->user_id ? (int) $ticket->rider->user_id : null;
    }

    private function notifyUser(int $userId, Model $ticket, string $type, ?int $adminId): void
    {
        $title = 'সাপোর্ট টিম উত্তর দিয়েছে';
        $message = (string) $ticket->admin_reply;
        $notificationData = [
            'type' => 'support_reply',
            'ticket_type' => $type,
            'ticket_id' => (string) $ticket->id,
            'food_order_id' => (string) ($ticket->food_order_id ?? ''),
        ];

        AppNotification::query()->create([
            'user_id' => $userId,
            'sent_by_admin_id' => $adminId,
            'title' => $title,
            'message' => $message,
            'data' => $notificationData,
        ]);

        $pushEnabled = NotificationPreference::query()
            ->where('user_id', $userId)
            ->value('push_enabled');
        if ($pushEnabled === false || $pushEnabled === 0) {
            return;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens) {
            SendAdminNotification::dispatch($tokens, [
                'priority' => 'high',
                'notification' => ['title' => $title, 'body' => $message],
                'data' => ['title' => $title, 'message' => $message, ...$notificationData],
            ]);
        }
    }

    private function relationsFor(string $type): array
    {
        return $type === 'food'
            ? ['user:id,name,phone,email', 'order:id,status']
            : ['rider:id,user_id,name,phone', 'rider.user:id,name,phone,email', 'order:id,status'];
    }
}
