<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MapSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMapSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => $this->serialize(MapSetting::current()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'provider' => ['required', 'in:google'],
            'browser_api_key' => ['nullable', 'string', 'max:500'],
            'maps_javascript_enabled' => ['required', 'boolean'],
            'embed_enabled' => ['required', 'boolean'],
            'places_enabled' => ['required', 'boolean'],
            'directions_enabled' => ['required', 'boolean'],
            'client_cache_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (blank($validated['browser_api_key'] ?? null)) {
            unset($validated['browser_api_key']);
        }

        $settings = MapSetting::current();
        $settings->update($validated);

        return response()->json([
            'message' => 'Map settings updated successfully.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    private function serialize(MapSetting $settings): array
    {
        return [
            'is_enabled' => (bool) $settings->is_enabled,
            'provider' => $settings->provider,
            'has_browser_api_key' => $settings->hasBrowserApiKey(),
            'browser_api_key_masked' => $settings->maskedBrowserApiKey(),
            'maps_javascript_enabled' => (bool) $settings->maps_javascript_enabled,
            'embed_enabled' => (bool) $settings->embed_enabled,
            'places_enabled' => (bool) $settings->places_enabled,
            'directions_enabled' => (bool) $settings->directions_enabled,
            'client_cache_minutes' => (int) $settings->client_cache_minutes,
            'note' => $settings->note,
        ];
    }
}
