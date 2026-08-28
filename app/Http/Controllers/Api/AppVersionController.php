<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersionSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppVersionController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['nullable', Rule::in(['android', 'ios'])],
            'version' => ['nullable', 'string', 'max:30'],
            'build' => ['nullable', 'integer', 'min:0'],
        ]);

        $platform = $validated['platform'] ?? 'android';
        $currentVersion = $validated['version'] ?? '0.0.0';
        $currentBuild = (int) ($validated['build'] ?? 0);
        $settings = AppVersionSetting::current($platform);

        $blockedVersions = $settings->blocked_versions ?: [];
        $isBlocked = in_array($currentVersion, $blockedVersions, true);
        $belowMinimum = $this->isOlder(
            $currentVersion,
            $currentBuild,
            $settings->minimum_supported_version,
            (int) $settings->minimum_supported_build
        );
        $updateAvailable = $this->isOlder(
            $currentVersion,
            $currentBuild,
            $settings->latest_version,
            (int) $settings->latest_build
        );

        $forceUpdate = $settings->is_enabled
            && ($settings->update_type === 'force' || $belowMinimum || $isBlocked);

        return response()->json([
            'settings_enabled' => (bool) $settings->is_enabled,
            'platform' => $platform,
            'current_version' => $currentVersion,
            'current_build' => $currentBuild,
            'latest_version' => $settings->latest_version,
            'latest_build' => (int) $settings->latest_build,
            'minimum_supported_version' => $settings->minimum_supported_version,
            'minimum_supported_build' => (int) $settings->minimum_supported_build,
            'update_available' => (bool) ($settings->is_enabled && $updateAvailable),
            'force_update' => (bool) $forceUpdate,
            'update_type' => $forceUpdate ? 'force' : ($updateAvailable ? $settings->update_type : 'none'),
            'title' => $settings->update_title,
            'message' => $settings->update_message,
            'store_url' => $settings->store_url,
            'direct_apk_url' => $settings->direct_apk_url,
            'maintenance' => (bool) $settings->maintenance_mode,
            'maintenance_title' => $settings->maintenance_title,
            'maintenance_message' => $settings->maintenance_message,
            'maintenance_until' => optional($settings->maintenance_until)->toIso8601String(),
            'blocked' => (bool) $isBlocked,
            'changelog' => $settings->changelog,
        ])->header('Cache-Control', 'no-store, private');
    }

    private function isOlder(string $version, int $build, string $targetVersion, int $targetBuild): bool
    {
        if ($build > 0 && $targetBuild > 0 && $build !== $targetBuild) {
            return $build < $targetBuild;
        }

        return version_compare($version, $targetVersion, '<');
    }
}
