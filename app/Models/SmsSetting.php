<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;

class SmsSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'provider',
        'api_key',
        'sender_id',
        'label',
        'message_type',
        'api_url',
        'last_tested_at',
        'last_test_result',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'api_key' => 'encrypted',
            'last_tested_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'is_enabled' => (bool) config('services.mram_sms.enabled', false),
                'provider' => 'mram',
                'api_key' => config('services.mram_sms.api_key'),
                'sender_id' => config('services.mram_sms.sender_id'),
                'label' => config('services.mram_sms.label', 'transactional'),
                'message_type' => config('services.mram_sms.type', 'unicode'),
                'api_url' => config('services.mram_sms.api_url', 'https://sms.mram.com.bd/smsapi'),
            ]
        );
    }

    public function maskedApiKey(): ?string
    {
        $apiKey = $this->safeApiKey();
        if (! $apiKey) {
            return null;
        }

        $length = strlen($apiKey);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($apiKey, 0, 4).str_repeat('*', max(0, $length - 8)).substr($apiKey, -4);
    }

    public function hasValidApiKey(): bool
    {
        return filled($this->safeApiKey());
    }

    public function safeApiKey(): ?string
    {
        try {
            return $this->api_key;
        } catch (DecryptException) {
            return null;
        }
    }
}
