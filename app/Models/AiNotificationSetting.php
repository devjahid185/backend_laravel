<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiNotificationSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'use_ai_copy' => 'boolean',
        'max_promotional_per_day' => 'integer',
        'max_promotional_per_week' => 'integer',
        'minimum_gap_hours' => 'integer',
        'batch_limit' => 'integer',
        'campaigns' => 'array',
        'last_run_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled' => true,
            'use_ai_copy' => true,
            'campaigns' => self::defaultCampaigns(),
        ]);
    }

    public static function defaultCampaigns(): array
    {
        return [
            'new_user' => true,
            'cart_abandoned' => true,
            'checkout_abandoned' => true,
            'rejected_recovery' => true,
            'reorder' => true,
            'inactive_user' => true,
            'personalized' => true,
            'free_delivery' => true,
            'discovery' => true,
        ];
    }

    public function campaignEnabled(string $campaign): bool
    {
        return (bool) (($this->campaigns ?: self::defaultCampaigns())[$campaign] ?? true);
    }
}
