<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersionSetting extends Model
{
    protected $fillable = [
        'platform',
        'is_enabled',
        'latest_version',
        'latest_build',
        'minimum_supported_version',
        'minimum_supported_build',
        'update_type',
        'update_title',
        'update_message',
        'store_url',
        'direct_apk_url',
        'maintenance_mode',
        'maintenance_title',
        'maintenance_message',
        'maintenance_until',
        'blocked_versions',
        'changelog',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'latest_build' => 'integer',
            'minimum_supported_build' => 'integer',
            'maintenance_mode' => 'boolean',
            'maintenance_until' => 'datetime',
            'blocked_versions' => 'array',
        ];
    }

    public static function current(string $platform = 'android'): self
    {
        return static::query()->firstOrCreate(
            ['platform' => $platform],
            [
                'is_enabled' => true,
                'latest_version' => '1.0.0',
                'latest_build' => 1,
                'minimum_supported_version' => '1.0.0',
                'minimum_supported_build' => 1,
                'update_type' => 'none',
                'update_title' => 'নতুন আপডেট এসেছে',
                'update_message' => 'আরও ভালো অভিজ্ঞতার জন্য অ্যাপ আপডেট করুন।',
                'maintenance_title' => 'সার্ভিস আপডেট চলছে',
                'maintenance_message' => 'আমরা সিস্টেম উন্নত করছি। কিছুক্ষণ পর আবার চেষ্টা করুন।',
                'store_url' => 'https://play.google.com/store/apps/details?id=com.sohojit.frontend_flutter',
            ]
        );
    }
}
