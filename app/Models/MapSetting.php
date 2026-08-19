<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'provider',
        'mobile_map_mode',
        'browser_api_key',
        'maps_javascript_enabled',
        'embed_enabled',
        'places_enabled',
        'directions_enabled',
        'client_cache_minutes',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'browser_api_key' => 'encrypted',
            'maps_javascript_enabled' => 'boolean',
            'embed_enabled' => 'boolean',
            'places_enabled' => 'boolean',
            'directions_enabled' => 'boolean',
            'client_cache_minutes' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'is_enabled' => false,
                'provider' => 'google',
                'mobile_map_mode' => 'webview',
                'maps_javascript_enabled' => true,
                'embed_enabled' => true,
                'places_enabled' => false,
                'directions_enabled' => false,
                'client_cache_minutes' => 1440,
            ]
        );
    }

    public function hasBrowserApiKey(): bool
    {
        return filled($this->browser_api_key);
    }

    public function maskedBrowserApiKey(): ?string
    {
        if (! $this->browser_api_key) {
            return null;
        }

        $key = $this->browser_api_key;
        if (strlen($key) <= 10) {
            return str_repeat('*', strlen($key));
        }

        return substr($key, 0, 6).str_repeat('*', max(0, strlen($key) - 12)).substr($key, -6);
    }
}
