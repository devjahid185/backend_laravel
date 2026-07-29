<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $request->user();
        $preference = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->first();
        if ($preference && ! $preference->push_enabled) {
            return response()->json(['message' => 'Push notifications are disabled.'], 200);
        }

        DeviceToken::query()->updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $user->id,
                'platform' => $validated['platform'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['message' => 'Token saved.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        DeviceToken::query()
            ->where('token', $validated['token'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Token removed.']);
    }
}
