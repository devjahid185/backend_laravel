<?php

namespace App\Services;

use App\Models\TelegramAdminSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TelegramBotService
{
    public function sendConfigured(string $message, ?string $buttonUrl = null, string $buttonText = 'Open Admin Portal'): array
    {
        $settings = TelegramAdminSetting::current();

        if (! $settings->is_enabled) {
            throw new \RuntimeException('Telegram alerts are disabled.');
        }
        if (! $settings->hasToken()) {
            throw new \RuntimeException('Telegram bot token is not configured.');
        }

        $chatIds = collect($settings->chat_ids ?: [])->map(fn ($id) => trim((string) $id))->filter()->unique()->values();
        if ($chatIds->isEmpty()) {
            throw new \RuntimeException('At least one Telegram chat ID is required.');
        }

        $results = [];
        $errors = [];
        foreach ($chatIds as $chatId) {
            try {
                $results[] = $this->send(
                    (string) $settings->bot_token,
                    $chatId,
                    $message,
                    $settings->message_thread_id,
                    $buttonUrl,
                    $buttonText,
                );
            } catch (\Throwable $e) {
                $errors[] = $chatId.': '.$e->getMessage();
            }
        }

        if ($errors) {
            throw new \RuntimeException(implode(' | ', $errors));
        }

        return $results;
    }

    public function send(
        string $token,
        string $chatId,
        string $message,
        ?int $messageThreadId = null,
        ?string $buttonUrl = null,
        string $buttonText = 'Open Admin Portal',
    ): array {
        $payload = [
            'chat_id' => $chatId,
            'text' => mb_substr($message, 0, 3900),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($messageThreadId) {
            $payload['message_thread_id'] = $messageThreadId;
        }
        if ($buttonUrl) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => [[[
                    'text' => $buttonText,
                    'url' => $buttonUrl,
                ]]],
            ], JSON_UNESCAPED_SLASHES);
        }

        $response = $this->client()->asForm()->post(
            'https://api.telegram.org/bot'.trim($token).'/sendMessage',
            $payload,
        );

        if (! $response->successful() || ! $response->json('ok')) {
            $description = $response->json('description') ?: 'Telegram API request failed.';
            throw new \RuntimeException((string) $description);
        }

        return (array) $response->json('result', []);
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->timeout(15)->connectTimeout(5)->retry(2, 300, throw: false);
    }
}
