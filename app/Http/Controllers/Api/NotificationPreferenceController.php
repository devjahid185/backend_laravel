<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $pref = NotificationPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'push_enabled' => true,
                'sms_enabled' => false,
                'email_enabled' => false,
                'marketing_enabled' => false,
            ],
        );

        return response()->json($pref);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'push_enabled' => ['sometimes', 'boolean'],
            'sms_enabled' => ['sometimes', 'boolean'],
            'email_enabled' => ['sometimes', 'boolean'],
            'marketing_enabled' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        $pref = NotificationPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'push_enabled' => true,
                'sms_enabled' => false,
                'email_enabled' => false,
                'marketing_enabled' => false,
            ],
        );

        $pref->update($validated);

        return response()->json([
            'message' => 'Notification preference updated.',
            'preference' => $pref->fresh(),
        ]);
    }
}
