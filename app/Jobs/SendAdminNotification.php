<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Services\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAdminNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public array $tokens,
        public array $payload,
    ) {
        $this->onConnection('database');
        $this->onQueue('notifications');
    }

    public function handle(FcmService $fcm): void
    {
        $results = $fcm->sendToTokens($this->tokens, $this->payload);
        $successful = collect($results)->filter(
            fn (array $result) => ($result['status'] ?? 0) >= 200 && ($result['status'] ?? 0) < 300
        )->count();

        $expiredTokens = collect($results)
            ->filter(function (array $result): bool {
                $error = data_get($result, 'body.error');
                $errorCode = collect($error['details'] ?? [])->firstWhere('@type', 'type.googleapis.com/google.firebase.fcm.v1.FcmError')['errorCode'] ?? null;

                return ($result['status'] ?? 0) === 404
                    && ($errorCode === 'UNREGISTERED' || ($error['message'] ?? null) === 'NotRegistered');
            })
            ->pluck('token')
            ->filter()
            ->unique()
            ->values();

        if ($expiredTokens->isNotEmpty()) {
            DeviceToken::query()->whereIn('token', $expiredTokens)->delete();
        }

        Log::info('Admin notification delivery completed.', [
            'tokens' => count($results),
            'successful' => $successful,
            'failed' => count($results) - $successful,
            'expired_tokens_removed' => $expiredTokens->count(),
        ]);
    }
}
