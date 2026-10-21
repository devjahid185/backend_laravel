<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramAdminSetting extends Model
{
    public const EVENT_TYPES = [
        'orders',
        'support',
        'registrations',
        'submissions',
        'messages_reviews',
    ];

    protected $fillable = [
        'is_enabled',
        'bot_token',
        'chat_ids',
        'message_thread_id',
        'event_types',
        'include_details',
        'portal_url',
        'last_tested_at',
        'last_test_result',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'bot_token' => 'encrypted',
            'chat_ids' => 'array',
            'message_thread_id' => 'integer',
            'event_types' => 'array',
            'include_details' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'is_enabled' => false,
                'chat_ids' => [],
                'event_types' => self::EVENT_TYPES,
                'include_details' => true,
                'portal_url' => 'https://portal.bholavashi.site',
            ]
        );
    }

    public function hasToken(): bool
    {
        return filled($this->bot_token);
    }

    public function maskedToken(): ?string
    {
        if (! $this->bot_token) {
            return null;
        }

        $token = (string) $this->bot_token;
        if (strlen($token) < 14) {
            return str_repeat('*', strlen($token));
        }

        return substr($token, 0, 6).str_repeat('*', 12).substr($token, -5);
    }

    public function eventEnabled(string $eventType): bool
    {
        return in_array($eventType, $this->event_types ?: [], true);
    }
}
