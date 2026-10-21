<?php

namespace App\Jobs;

use App\Services\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTelegramAdminAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 45;

    public function __construct(
        public string $message,
        public ?string $buttonUrl = null,
        public string $buttonText = 'Open Admin Portal',
    ) {
        $this->onConnection('database');
        $this->onQueue('notifications');
    }

    public function backoff(): array
    {
        return [10, 30, 120];
    }

    public function handle(TelegramBotService $telegram): void
    {
        $results = $telegram->sendConfigured($this->message, $this->buttonUrl, $this->buttonText);
        Log::info('Telegram admin alert delivered.', ['chats' => count($results)]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Telegram admin alert failed permanently.', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
