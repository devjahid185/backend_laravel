<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\Message;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function inbox(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $messages = Message::query()
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $unreadCounts = Message::query()
            ->where('receiver_id', $userId)
            ->where('seen', false)
            ->selectRaw('sender_id, COUNT(*) as cnt')
            ->groupBy('sender_id')
            ->pluck('cnt', 'sender_id');

        $conversations = [];
        foreach ($messages as $msg) {
            $otherId = $msg->sender_id == $userId ? $msg->receiver_id : $msg->sender_id;
            if (isset($conversations[$otherId])) {
                continue;
            }

            $other = User::query()->find($otherId);
            $conversations[$otherId] = [
                'user_id' => $otherId,
                'name' => $other?->name,
                'photo_url' => $other?->photo_url,
                'last_message' => $msg,
                'unread_count' => (int) ($unreadCounts[$otherId] ?? 0),
            ];
        }

        return response()->json(array_values($conversations));
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'after_id' => ['nullable', 'integer'],
        ]);

        $currentUserId = $request->user()->id;
        $otherUserId = $request->integer('user_id');

        $messages = Message::query()
            ->where(function ($query) use ($request) {
                $query->where('sender_id', $request->user()->id)
                    ->where('receiver_id', $request->integer('user_id'));
            })
            ->orWhere(function ($query) use ($request) {
                $query->where('sender_id', $request->integer('user_id'))
                    ->where('receiver_id', $request->user()->id);
            })
            ->when($request->filled('after_id'), fn ($q) => $q->where('id', '>', $request->integer('after_id')))
            ->orderBy('id');

        if ($request->filled('after_id')) {
            $rows = $messages->get();

            Message::query()
                ->where('receiver_id', $currentUserId)
                ->where('sender_id', $otherUserId)
                ->where('id', '>', $request->integer('after_id'))
                ->whereNull('delivered_at')
                ->update(['delivered_at' => now()]);

            Message::query()
                ->where('receiver_id', $request->user()->id)
                ->where('sender_id', $request->integer('user_id'))
                ->where('seen', false)
                ->update(['seen' => true]);

            return response()->json($rows);
        }

        $paginated = $messages->paginate(50);

        Message::query()
            ->where('receiver_id', $currentUserId)
            ->where('sender_id', $otherUserId)
            ->whereNull('delivered_at')
            ->update(['delivered_at' => now()]);

        Message::query()
            ->where('receiver_id', $request->user()->id)
            ->where('sender_id', $request->integer('user_id'))
            ->where('seen', false)
            ->update(['seen' => true]);

        return response()->json($paginated);
    }

    public function send(Request $request, FcmService $fcm): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'exists:users,id'],
            'message' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'attachment_url' => ['nullable', 'string', 'max:255'],
            'attachment_name' => ['nullable', 'string', 'max:255'],
            'attachment_mime' => ['nullable', 'string', 'max:120'],
        ]);

        if ((int) $validated['receiver_id'] === (int) $request->user()->id) {
            return response()->json(['message' => 'Cannot send message to yourself'], 422);
        }

        $hasText = ! empty(trim((string) ($validated['message'] ?? '')));
        $hasImage = ! empty($validated['image']);
        $hasAttachment = ! empty($validated['attachment_url']);
        if (! $hasText && ! $hasImage && ! $hasAttachment) {
            return response()->json(['message' => 'Message cannot be empty'], 422);
        }

        $msg = Message::query()->create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'] ?? null,
            'image' => $validated['image'] ?? null,
            'attachment_url' => $validated['attachment_url'] ?? null,
            'attachment_name' => $validated['attachment_name'] ?? null,
            'attachment_mime' => $validated['attachment_mime'] ?? null,
        ]);

        $receiver = User::query()->find($validated['receiver_id']);
        if ($receiver) {
            $pref = NotificationPreference::query()
                ->where('user_id', $receiver->id)
                ->first();

            $pushEnabled = $pref ? (bool) $pref->push_enabled : true;
            if ($pushEnabled) {
                $tokens = DeviceToken::query()
                    ->where('user_id', $receiver->id)
                    ->pluck('token')
                    ->all();

                $senderName = $request->user()->name ?: 'New message';
                $body = $hasText ? Str::limit((string) $validated['message'], 120) : 'Attachment';

                $payload = [
                    'priority' => 'high',
                    'data' => [
                        'type' => 'chat',
                        'sender_id' => (string) $request->user()->id,
                        'receiver_id' => (string) $receiver->id,
                        'message_id' => (string) $msg->id,
                        'title' => $senderName,
                        'message' => $body,
                    ],
                    'notification' => [
                        'title' => $senderName,
                        'body' => $body,
                    ],
                ];

                if ($tokens) {
                    try {
                        $fcm->sendToTokens($tokens, $payload);
                    } catch (\Throwable $e) {
                        \Log::error('Chat notification failed', ['error' => $e->getMessage()]);
                    }
                }
            }

            AppNotification::query()->create([
                'user_id' => $receiver->id,
                'sent_by_admin_id' => null,
                'title' => $request->user()->name ?: 'New message',
                'message' => $validated['message'] ?? null,
                'image_url' => null,
                'data' => [
                    'type' => 'chat',
                    'sender_id' => $request->user()->id,
                    'message_id' => $msg->id,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Message sent',
            'data' => $msg,
        ], 201);
    }

    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $validated['file'];
        $path = $file->store('uploads/chat/'.date('Y/m'), 'public');
        $url = asset('storage/'.$path);

        return response()->json([
            'url' => $url,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
        ]);
    }

    public function typing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'exists:users,id'],
            'is_typing' => ['required', 'boolean'],
        ]);

        $key = "typing:{$request->user()->id}:{$validated['receiver_id']}";
        if ($validated['is_typing']) {
            Cache::put($key, true, now()->addSeconds(10));
        } else {
            Cache::forget($key);
        }

        return response()->json(['message' => 'ok']);
    }

    public function typingStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $key = "typing:{$validated['user_id']}:{$request->user()->id}";
        $isTyping = Cache::get($key, false);

        return response()->json(['is_typing' => (bool) $isTyping]);
    }
}
