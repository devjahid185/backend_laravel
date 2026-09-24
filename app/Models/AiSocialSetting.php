<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AiSocialSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'approval_required' => 'boolean',
        'daily_post_limit' => 'integer',
        'last_openai_checked_at' => 'datetime',
        'last_facebook_checked_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'brand_voice' => 'Bholavashi is a trusted local service app for Bhola. Write clear, warm Bangla copy. Avoid fake claims, medical guarantees, political content, and personal data.',
        ]);
    }

    public function setOpenaiApiKeyAttribute(?string $value): void
    {
        $this->attributes['openai_api_key'] = filled($value) ? Crypt::encryptString($value) : null;
    }

    public function getOpenaiApiKeyPlainAttribute(): ?string
    {
        return $this->decryptSecret($this->openai_api_key);
    }

    public function setFacebookPageAccessTokenAttribute(?string $value): void
    {
        $this->attributes['facebook_page_access_token'] = filled($value) ? Crypt::encryptString($value) : null;
    }

    public function getFacebookPageAccessTokenPlainAttribute(): ?string
    {
        return $this->decryptSecret($this->facebook_page_access_token);
    }

    public function hasOpenaiKey(): bool
    {
        return filled($this->openai_api_key_plain);
    }

    public function hasFacebookCredentials(): bool
    {
        return filled($this->facebook_page_id) && filled($this->facebook_page_access_token_plain);
    }

    public function maskedOpenaiKey(): ?string
    {
        return $this->mask($this->openai_api_key_plain);
    }

    public function maskedFacebookToken(): ?string
    {
        return $this->mask($this->facebook_page_access_token_plain);
    }

    private function decryptSecret(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function mask(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4).str_repeat('*', max(0, $length - 8)).substr($value, -4);
    }
}
