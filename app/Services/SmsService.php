<?php

namespace App\Services;

use App\Models\SmsSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SmsService
{
    public function sendOtp(string $phone, string $otp): void
    {
        $message = "Bholabashi OTP: {$otp}. Do not share this code.";
        $this->send($phone, $message);
    }

    public function send(string $phone, string $message): void
    {
        $settings = SmsSetting::current();

        if (! $settings->is_enabled) {
            throw new \RuntimeException('SMS sending is disabled.');
        }

        $apiKey = $settings->safeApiKey();
        if (! $apiKey || ! $settings->sender_id) {
            throw new \RuntimeException('SMS service not configured.');
        }

        $response = Http::get($settings->api_url ?: 'https://sms.mram.com.bd/smsapi', [
            'api_key' => $apiKey,
            'type' => $settings->message_type ?: 'unicode',
            'contacts' => $this->normalizeBangladeshPhone($phone),
            'senderid' => $settings->sender_id,
            'msg' => $message,
            'label' => $settings->label ?: 'transactional',
        ]);

        if (! $response->ok()) {
            throw new \RuntimeException('SMS gateway error: HTTP '.$response->status());
        }

        $body = trim((string) $response->body());
        if ($this->isErrorResponse($body)) {
            throw new \RuntimeException('SMS gateway rejected request: '.$body);
        }
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
