<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TelegramAdminSetting;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminTelegramSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['settings' => $this->serialize(TelegramAdminSetting::current())]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'bot_token' => ['nullable', 'string', 'max:255'],
            'chat_ids' => ['required', 'array', 'min:1', 'max:10'],
            'chat_ids.*' => ['required', 'string', 'max:80', 'regex:/^-?\d+$/'],
            'message_thread_id' => ['nullable', 'integer', 'min:1'],
            'event_types' => ['required', 'array', 'min:1'],
            'event_types.*' => ['required', 'in:'.implode(',', TelegramAdminSetting::EVENT_TYPES)],
            'include_details' => ['required', 'boolean'],
            'portal_url' => ['required', 'url:http,https', 'max:255'],
        ]);

        if (blank($validated['bot_token'] ?? null)) {
            unset($validated['bot_token']);
        }
        $validated['chat_ids'] = collect($validated['chat_ids'])->map(fn ($id) => trim((string) $id))->unique()->values()->all();
        $validated['event_types'] = collect($validated['event_types'])->unique()->values()->all();
        $validated['portal_url'] = rtrim($validated['portal_url'], '/');

        $settings = TelegramAdminSetting::current();
        $settings->update($validated);

        return response()->json([
            'message' => 'Telegram alert settings saved.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    public function test(TelegramBotService $telegram): JsonResponse
    {
        $settings = TelegramAdminSetting::current();

        try {
            $results = $telegram->sendConfigured(
                "<b>BHOLAVASHI TELEGRAM TEST</b>\n\nTelegram admin alerts are connected successfully.\n<b>Time:</b> ".now()->timezone('Asia/Dhaka')->format('d M Y, h:i:s A'),
                rtrim($settings->portal_url ?: 'https://portal.bholavashi.site', '/').'/admin',
                'Open Dashboard',
            );
            $settings->update([
                'last_tested_at' => now(),
                'last_test_result' => 'Success - delivered to '.count($results).' chat(s).',
            ]);

            return response()->json([
                'message' => 'Test alert delivered successfully.',
                'settings' => $this->serialize($settings->fresh()),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram admin alert test failed.', ['error' => $e->getMessage()]);
            $settings->update([
                'last_tested_at' => now(),
                'last_test_result' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'settings' => $this->serialize($settings->fresh()),
            ], 422);
        }
    }

    private function serialize(TelegramAdminSetting $settings): array
    {
        return [
            'is_enabled' => (bool) $settings->is_enabled,
            'has_bot_token' => $settings->hasToken(),
            'bot_token_masked' => $settings->maskedToken(),
            'chat_ids' => $settings->chat_ids ?: [],
            'message_thread_id' => $settings->message_thread_id,
            'event_types' => $settings->event_types ?: TelegramAdminSetting::EVENT_TYPES,
            'include_details' => (bool) $settings->include_details,
            'portal_url' => $settings->portal_url,
            'last_tested_at' => $settings->last_tested_at,
            'last_test_result' => $settings->last_test_result,
        ];
    }
}
