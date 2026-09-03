<?php

namespace App\Services;

use App\Models\MedicineOrder;
use App\Models\MedicinePaymentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BkashTokenizedCheckoutService
{
    public function createPayment(MedicineOrder $order, MedicinePaymentSetting $settings): array
    {
        $token = $this->grantToken($settings);
        $callbackUrl = url('/api/medicine/bkash/callback');
        $response = Http::withHeaders($this->authorizedHeaders($settings, $token))
            ->post($settings->bkashBaseUrl().'/create', [
                'mode' => '0011',
                'payerReference' => (string) $order->receiver_phone,
                'callbackURL' => $callbackUrl,
                'amount' => number_format((float) $order->grand_total, 2, '.', ''),
                'currency' => 'BDT',
                'intent' => 'sale',
                'merchantInvoiceNumber' => $order->order_no,
            ]);

        $payload = $this->json($response);
        if (! $response->ok() || empty($payload['paymentID']) || empty($payload['bkashURL'])) {
            Log::error('bKash tokenized create payment failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'payload' => $payload,
            ]);
            throw new \RuntimeException($payload['statusMessage'] ?? 'bKash payment create failed.');
        }

        return $payload;
    }

    public function executePayment(MedicineOrder $order, MedicinePaymentSetting $settings, ?string $paymentId = null): array
    {
        $paymentId = $paymentId ?: $order->bkash_payment_id;
        if (! $paymentId) {
            throw new \RuntimeException('bKash payment ID missing.');
        }

        $token = $this->grantToken($settings);
        $response = Http::withHeaders($this->authorizedHeaders($settings, $token))
            ->post($settings->bkashBaseUrl().'/execute', ['paymentID' => $paymentId]);

        $payload = $this->json($response);
        if (! $response->ok()) {
            Log::error('bKash tokenized execute payment failed', [
                'order_id' => $order->id,
                'payment_id' => $paymentId,
                'status' => $response->status(),
                'payload' => $payload,
            ]);
            throw new \RuntimeException($payload['statusMessage'] ?? 'bKash payment execute failed.');
        }

        return $payload;
    }

    public function queryPayment(MedicineOrder $order, MedicinePaymentSetting $settings, ?string $paymentId = null): array
    {
        $paymentId = $paymentId ?: $order->bkash_payment_id;
        if (! $paymentId) {
            throw new \RuntimeException('bKash payment ID missing.');
        }

        $token = $this->grantToken($settings);
        $response = Http::withHeaders($this->authorizedHeaders($settings, $token))
            ->post($settings->bkashBaseUrl().'/payment/status', ['paymentID' => $paymentId]);

        return $this->json($response);
    }

    private function grantToken(MedicinePaymentSetting $settings): string
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'username' => (string) $settings->safeSecret('bkash_tokenized_username'),
            'password' => (string) $settings->safeSecret('bkash_tokenized_password'),
        ])->post($settings->bkashBaseUrl().'/token/grant', [
            'app_key' => $settings->safeSecret('bkash_tokenized_app_key'),
            'app_secret' => $settings->safeSecret('bkash_tokenized_app_secret'),
        ]);

        $payload = $this->json($response);
        if (! $response->ok() || empty($payload['id_token'])) {
            Log::error('bKash tokenized grant token failed', [
                'status' => $response->status(),
                'payload' => $payload,
            ]);
            throw new \RuntimeException($payload['statusMessage'] ?? 'bKash token grant failed.');
        }

        return $payload['id_token'];
    }

    private function authorizedHeaders(MedicinePaymentSetting $settings, string $token): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $token,
            'X-App-Key' => (string) $settings->safeSecret('bkash_tokenized_app_key'),
        ];
    }

    private function json($response): array
    {
        $payload = $response->json();
        if (is_array($payload)) {
            return $payload;
        }

        return ['raw' => Str::limit((string) $response->body(), 1000)];
    }
}
