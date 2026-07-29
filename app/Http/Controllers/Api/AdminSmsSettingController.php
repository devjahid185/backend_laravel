<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsSetting;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSmsSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => $this->serialize(SmsSetting::current()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'provider' => ['required', 'in:mram'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'sender_id' => ['nullable', 'string', 'max:100'],
            'label' => ['required', 'in:transactional,promotional'],
            'message_type' => ['required', 'in:text,unicode'],
            'api_url' => ['required', 'url', 'max:255'],
        ]);

        $settings = SmsSetting::current();
        if (blank($validated['api_key'] ?? null)) {
            unset($validated['api_key']);
        }

        $settings->update($validated);

        return response()->json([
            'message' => 'SMS settings updated successfully.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    public function test(Request $request, SmsService $sms): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:320'],
        ]);

        $settings = SmsSetting::current();
        $message = $validated['message'] ?: 'Bholabashi SMS settings test message.';

        try {
            $sms->send($validated['phone'], $message);
            $settings->update([
                'last_tested_at' => now(),
                'last_test_result' => 'Success',
            ]);

            return response()->json([
                'message' => 'Test SMS sent successfully.',
                'settings' => $this->serialize($settings->fresh()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin SMS test failed', [
                'error' => $e->getMessage(),
                'phone' => $this->maskPhone($validated['phone']),
            ]);

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

    private function serialize(SmsSetting $settings): array
    {
        return [
            'is_enabled' => (bool) $settings->is_enabled,
            'provider' => $settings->provider,
            'api_key_masked' => $settings->maskedApiKey(),
            'has_api_key' => filled($settings->api_key),
            'sender_id' => $settings->sender_id,
            'label' => $settings->label,
            'message_type' => $settings->message_type,
            'api_url' => $settings->api_url,
            'last_tested_at' => $settings->last_tested_at,
            'last_test_result' => $settings->last_test_result,
        ];
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 4) {
            return $digits;
        }

        return substr($digits, 0, 2).str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -2);
    }
}
