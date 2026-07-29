<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $query = AppNotification::query()->with('user');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $notifications = $query->orderByDesc('id')->paginate($perPage);

        return response()->json($notifications);
    }

    public function send(Request $request, FcmService $fcm): JsonResponse
    {
        $validated = $request->validate([
            'target' => ['required', 'in:all,user'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['nullable', 'string', 'max:200'],
            'message' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'data' => ['nullable', 'array'],
        ]);

        if ($validated['target'] === 'user' && empty($validated['user_id'])) {
            throw ValidationException::withMessages(['user_id' => ['User is required for user target.']]);
        }

        $hasContent = ! empty($validated['title']) || ! empty($validated['message']) || $request->hasFile('image');
        if (! $hasContent) {
            throw ValidationException::withMessages(['title' => ['Title, message, or image is required.']]);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('notifications/' . now()->format('Y/m'), 'public');
            $baseUrl = $this->notificationPublicBaseUrl($request);
            $imageUrl = $baseUrl ? $baseUrl . '/storage/' . $path : asset('storage/' . $path);
        }

        $data = $validated['data'] ?? [];
        if ($imageUrl) {
            $data['image_url'] = $imageUrl;
        }

        $notificationPayload = [
            'priority' => 'high',
            'data' => [
                'title' => $validated['title'] ?? '',
                'message' => $validated['message'] ?? '',
                ...$data,
            ],
        ];

        if (! empty($validated['title']) || ! empty($validated['message']) || $imageUrl) {
            $notificationPayload['notification'] = [
                'title' => $validated['title'] ?? '',
                'body' => $validated['message'] ?? '',
                'image' => $imageUrl,
            ];
        }

        \Log::info('Admin notification payload', [
            'image_url' => $imageUrl,
            'payload' => $notificationPayload,
        ]);

        $admin = $request->user();
        $userQuery = User::query();
        if ($validated['target'] === 'user') {
            $userQuery->whereKey($validated['user_id']);
        }

        $users = $userQuery->get(['id']);
        $tokens = DeviceToken::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->pluck('token')
            ->all();

        $results = [];
        if (! $tokens) {
            return response()->json([
                'message' => 'No device tokens found for the selected users.',
                'sent_to' => 0,
                'tokens' => 0,
            ], 200);
        }

        try {
            $results = $fcm->sendToTokens($tokens, $notificationPayload);
            \Log::info('Admin notification FCM results', [
                'tokens' => count($tokens),
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Admin notification send failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Notification send failed.',
                'error' => $e->getMessage(),
            ], 500);
        }

        $records = [];
        $dataPayload = $data ? json_encode($data) : null;
        foreach ($users as $user) {
            $records[] = [
                'user_id' => $user->id,
                'sent_by_admin_id' => $admin instanceof Admin ? $admin->id : null,
                'title' => $validated['title'] ?? null,
                'message' => $validated['message'] ?? null,
                'image_url' => $imageUrl,
                'data' => $dataPayload,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($records) {
            AppNotification::query()->insert($records);
        }

        return response()->json([
            'message' => 'Notification sent.',
            'sent_to' => $users->count(),
            'tokens' => count($tokens),
            'results' => $results,
        ]);
    }

    private function notificationPublicBaseUrl(Request $request): string
    {
        $baseUrl = env('NOTIFICATION_PUBLIC_URL')
            ?: env('PUBLIC_APP_URL')
            ?: env('APP_URL')
            ?: config('app.url')
            ?: $request->getSchemeAndHttpHost();

        $baseUrl = rtrim((string) $baseUrl, '/');

        if (str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '127.0.0.1')) {
            \Log::warning('Notification image URL is not public. Set NOTIFICATION_PUBLIC_URL or APP_URL to an HTTPS URL.', [
                'base_url' => $baseUrl,
            ]);
        }

        return $baseUrl;
    }
}
