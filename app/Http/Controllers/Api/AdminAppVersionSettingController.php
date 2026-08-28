<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersionSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminAppVersionSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'settings' => $this->serialize(AppVersionSetting::current($request->query('platform', 'android'))),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', Rule::in(['android', 'ios'])],
            'is_enabled' => ['required', 'boolean'],
            'latest_version' => ['required', 'string', 'max:30'],
            'latest_build' => ['required', 'integer', 'min:1'],
            'minimum_supported_version' => ['required', 'string', 'max:30'],
            'minimum_supported_build' => ['required', 'integer', 'min:1'],
            'update_type' => ['required', Rule::in(['none', 'recommended', 'force'])],
            'update_title' => ['required', 'string', 'max:120'],
            'update_message' => ['nullable', 'string', 'max:1500'],
            'store_url' => ['nullable', 'url', 'max:500'],
            'direct_apk_url' => ['nullable', 'url', 'max:500'],
            'maintenance_mode' => ['required', 'boolean'],
            'maintenance_title' => ['required', 'string', 'max:120'],
            'maintenance_message' => ['nullable', 'string', 'max:1500'],
            'maintenance_until' => ['nullable', 'date'],
            'blocked_versions_text' => ['nullable', 'string', 'max:1000'],
            'changelog' => ['nullable', 'string', 'max:4000'],
        ]);

        $blockedText = $validated['blocked_versions_text'] ?? '';
        unset($validated['blocked_versions_text']);
        $validated['blocked_versions'] = collect(preg_split('/[\s,]+/', $blockedText) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        $settings = AppVersionSetting::current($validated['platform']);
        $settings->update($validated);

        return response()->json([
            'message' => 'App version settings updated successfully.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    private function serialize(AppVersionSetting $settings): array
    {
        return [
            'platform' => $settings->platform,
            'is_enabled' => (bool) $settings->is_enabled,
            'latest_version' => $settings->latest_version,
            'latest_build' => (int) $settings->latest_build,
            'minimum_supported_version' => $settings->minimum_supported_version,
            'minimum_supported_build' => (int) $settings->minimum_supported_build,
            'update_type' => $settings->update_type,
            'update_title' => $settings->update_title,
            'update_message' => $settings->update_message,
            'store_url' => $settings->store_url,
            'direct_apk_url' => $settings->direct_apk_url,
            'maintenance_mode' => (bool) $settings->maintenance_mode,
            'maintenance_title' => $settings->maintenance_title,
            'maintenance_message' => $settings->maintenance_message,
            'maintenance_until' => optional($settings->maintenance_until)->format('Y-m-d\TH:i'),
            'blocked_versions' => $settings->blocked_versions ?: [],
            'blocked_versions_text' => implode(', ', $settings->blocked_versions ?: []),
            'changelog' => $settings->changelog,
            'updated_at' => optional($settings->updated_at)->toIso8601String(),
        ];
    }
}
