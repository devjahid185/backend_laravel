<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MapSetting;
use Illuminate\Http\JsonResponse;

class MapSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = MapSetting::current();

        return response()->json([
            'settings' => [
                'is_enabled' => (bool) $settings->is_enabled,
                'provider' => $settings->provider,
                'mobile_map_mode' => $settings->mobile_map_mode ?: 'webview',
                'browser_api_key' => $settings->is_enabled ? $settings->browser_api_key : null,
                'maps_javascript_enabled' => (bool) $settings->maps_javascript_enabled,
                'embed_enabled' => (bool) $settings->embed_enabled,
                'places_enabled' => (bool) $settings->places_enabled,
                'directions_enabled' => (bool) $settings->directions_enabled,
                'client_cache_minutes' => (int) $settings->client_cache_minutes,
            ],
        ])->header('Cache-Control', 'public, max-age='.(60 * max(1, (int) $settings->client_cache_minutes)));
    }
}
