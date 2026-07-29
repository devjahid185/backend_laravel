<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FcmService
{
    private ?array $serviceAccount = null;
    private ?string $accessToken = null;
    private ?int $accessTokenExpiresAt = null;

    public function sendToTokens(array $tokens, array $payload): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if (! $tokens) {
            return [];
        }

        $accessToken = $this->getAccessToken();
        $projectId = $this->getProjectId();

        $results = [];
        foreach ($tokens as $token) {
            $message = $this->buildMessage($token, $payload);
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => $message,
                ]);

            $results[] = [
                'token' => $token,
                'status' => $response->status(),
                'body' => $response->json(),
            ];
        }

        return $results;
    }

    private function buildMessage(string $token, array $payload): array
    {
        $data = $payload['data'] ?? [];
        $notification = $payload['notification'] ?? [];
        $image = $notification['image'] ?? $data['image_url'] ?? null;

        $notificationPayload = [
            'title' => $notification['title'] ?? $data['title'] ?? '',
            'body' => $notification['body'] ?? $data['message'] ?? '',
        ];
        if ($image) {
            $notificationPayload['image'] = $image;
        }

        return [
            'token' => $token,
            'notification' => $notificationPayload,
            'data' => $this->stringifyData($data),
            'android' => [
                'priority' => 'HIGH',
                'notification' => array_filter([
                    'image' => $image,
                ]),
            ],
            'apns' => [
                'fcm_options' => array_filter([
                    'image' => $image,
                ]),
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                    ],
                ],
            ],
        ];
    }

    private function stringifyData(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            $result[$key] = is_scalar($value) ? (string) $value : json_encode($value);
        }
        return $result;
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && $this->accessTokenExpiresAt && time() < $this->accessTokenExpiresAt - 60) {
            return $this->accessToken;
        }

        $account = $this->getServiceAccount();
        $jwt = $this->makeJwt($account);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        $data = $response->json();
        if (! $response->ok() || empty($data['access_token'])) {
            throw new \RuntimeException('Unable to fetch FCM access token.');
        }

        $this->accessToken = $data['access_token'];
        $this->accessTokenExpiresAt = time() + ((int) ($data['expires_in'] ?? 3600));

        return $this->accessToken;
    }

    private function makeJwt(array $account): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();
        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = "{$header}.{$payload}";
        $signature = '';
        $privateKey = openssl_pkey_get_private($account['private_key']);
        if (! $privateKey) {
            throw new \RuntimeException('Invalid FCM private key.');
        }
        openssl_sign($signatureInput, $signature, $privateKey, 'sha256');

        return "{$signatureInput}.{$this->base64UrlEncode($signature)}";
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function getProjectId(): string
    {
        $account = $this->getServiceAccount();
        $projectId = $account['project_id'] ?? null;
        $fallback = config('services.fcm.project_id') ?? env('FCM_PROJECT_ID');
        $projectId = $projectId ?: $fallback;

        if (! $projectId) {
            throw new \RuntimeException('FCM project id missing.');
        }

        return $projectId;
    }

    private function getServiceAccount(): array
    {
        if ($this->serviceAccount) {
            return $this->serviceAccount;
        }

        $path = config('services.fcm.service_account_path') ?? env('FCM_SERVICE_ACCOUNT_PATH');
        if (! $path) {
            throw new \RuntimeException('FCM service account path missing.');
        }

        $fullPath = file_exists($path) ? $path : base_path($path);
        if (! file_exists($fullPath)) {
            throw new \RuntimeException('FCM service account file not found.');
        }

        $json = json_decode(file_get_contents($fullPath), true);
        if (! is_array($json)) {
            throw new \RuntimeException('Invalid FCM service account JSON.');
        }

        $this->serviceAccount = $json;

        return $this->serviceAccount;
    }
}
