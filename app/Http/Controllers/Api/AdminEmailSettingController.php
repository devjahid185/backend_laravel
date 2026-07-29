<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminEmailSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => $this->serialize(EmailSetting::current()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'mailer' => ['required', 'in:smtp,log,array'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', 'in:tls,ssl,starttls,none'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        if (($validated['encryption'] ?? null) === 'none') {
            $validated['encryption'] = null;
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $settings = EmailSetting::current();
        $settings->update($validated);

        return response()->json([
            'message' => 'Email settings updated successfully.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    public function test(Request $request, EmailService $email): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $settings = EmailSetting::current();
        $subject = $validated['subject'] ?: 'Bholabashi email settings test';
        $message = $validated['message'] ?: 'This is a test email from the Bholabashi admin panel.';

        try {
            $email->sendText($validated['to'], $subject, $message);
            $settings->update([
                'last_tested_at' => now(),
                'last_test_result' => 'Success',
            ]);

            return response()->json([
                'message' => 'Test email sent successfully.',
                'settings' => $this->serialize($settings->fresh()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin email test failed', [
                'to' => $validated['to'],
                'error' => $e->getMessage(),
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

    private function serialize(EmailSetting $settings): array
    {
        return [
            'is_enabled' => (bool) $settings->is_enabled,
            'mailer' => $settings->mailer,
            'host' => $settings->host,
            'port' => $settings->port,
            'username' => $settings->username,
            'password_masked' => $settings->maskedPassword(),
            'has_password' => filled($settings->password),
            'encryption' => $settings->encryption ?: 'none',
            'from_address' => $settings->from_address,
            'from_name' => $settings->from_name,
            'timeout' => $settings->timeout,
            'last_tested_at' => $settings->last_tested_at,
            'last_test_result' => $settings->last_test_result,
        ];
    }
}
