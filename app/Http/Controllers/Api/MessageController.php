<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'after_id' => ['nullable', 'integer'],
        ]);

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
                ->where('receiver_id', $request->user()->id)
                ->where('sender_id', $request->integer('user_id'))
                ->where('seen', false)
                ->update(['seen' => true]);

            return response()->json($rows);
        }

        $paginated = $messages->paginate(50);

        Message::query()
            ->where('receiver_id', $request->user()->id)
            ->where('sender_id', $request->integer('user_id'))
            ->where('seen', false)
            ->update(['seen' => true]);

        return response()->json($paginated);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'exists:users,id'],
            'message' => ['required', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
        ]);

        if ((int) $validated['receiver_id'] === (int) $request->user()->id) {
            return response()->json(['message' => 'Cannot send message to yourself'], 422);
        }

        $msg = Message::query()->create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
            'image' => $validated['image'] ?? null,
        ]);

        return response()->json([
            'message' => 'Message sent',
            'data' => $msg,
        ], 201);
    }
}
