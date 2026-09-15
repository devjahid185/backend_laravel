<?php

namespace App\Services;

use App\Models\SmsSetting;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SmsService
{
    public function sendOtp(string $phone, string $otp): void
    {
        $message = "ভোলাবাসী OTP: {$otp}. ৫ মিনিটের মধ্যে ব্যবহার করুন। কাউকে শেয়ার করবেন না।";
        $this->send($phone, $message, 'otp');
    }

    public function send(string $phone, string $message, string $purpose = 'manual'): void
    {
        $settings = SmsSetting::current();
        $normalizedPhone = $this->normalizeBangladeshPhone($phone);
        $log = SmsLog::query()->create([
            'phone' => $this->maskPhone($normalizedPhone),
            'message' => $message,
            'purpose' => $purpose,
            'provider' => $settings->provider,
            'sender_id' => $settings->sender_id,
            'api_url' => $settings->api_url ?: 'https://sms.mram.com.bd/smsapi',
            'status' => 'pending',
        ]);

        if (! $settings->is_enabled) {
            $this->failLog($log, 'SMS sending is disabled.');
            throw new \RuntimeException('SMS sending is disabled.');
        }

        $apiKey = $settings->safeApiKey();
        if (! $apiKey || ! $settings->sender_id) {
            $this->failLog($log, 'SMS service not configured.');
            throw new \RuntimeException('SMS service not configured.');
        }

        $payload = [
            'api_key' => $apiKey,
            'type' => $settings->message_type ?: 'unicode',
            'contacts' => $normalizedPhone,
            'senderid' => $settings->sender_id,
            'msg' => $message,
            'label' => $settings->label ?: 'transactional',
        ];
        $log->update([
            'request_payload' => [
                ...$payload,
                'api_key' => $settings->maskedApiKey(),
            ],
        ]);

        $response = Http::timeout(20)->get($settings->api_url ?: 'https://sms.mram.com.bd/smsapi', $payload);
        $body = trim((string) $response->body());

        if (! $response->ok()) {
            $this->failLog($log, 'SMS gateway error: HTTP '.$response->status(), $response->status(), $body);
            throw new \RuntimeException('SMS gateway error: HTTP '.$response->status());
        }

        if ($this->isErrorResponse($body)) {
            $this->failLog($log, 'SMS gateway rejected request: '.$body, $response->status(), $body);
            throw new \RuntimeException('SMS gateway rejected request: '.$body);
        }

        $log->update([
            'status' => 'sent',
            'http_status' => $response->status(),
            'gateway_response' => $body,
            'sent_at' => now(),
        ]);
    }

    private function normalizeBangladeshPhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone ?? '');
        $phone = ltrim($phone, '+');

        if (Str::startsWith($phone, '0') && strlen($phone) === 11) {
            return '88'.$phone;
        }

        if (Str::startsWith($phone, '880')) {
            return $phone;
        }

        return $phone;
    }

    private function failLog(SmsLog $log, string $error, ?int $httpStatus = null, ?string $response = null): void
    {
        $log->update([
            'status' => 'failed',
            'http_status' => $httpStatus,
            'gateway_response' => $response,
            'error_message' => $error,
        ]);
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 4) {
            return $digits;
        }

        return substr($digits, 0, 2).str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -2);
    }

    private function isErrorResponse(string $body): bool
    {
        $errorCodes = [
            '1002', '1003', '1004', '1005', '1006', '1007', '1008', '1009',
            '1010', '1011', '1012', '1013', '1014', '1015', '1016', '1019',
        ];

        foreach ($errorCodes as $code) {
            if (Str::contains($body, $code)) {
                return true;
            }
        }

        return false;
    }
}
