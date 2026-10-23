<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OsrmDistanceService
{
    public function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): ?float
    {
        if (! (bool) config('services.osrm.enabled', false)
            || ! $this->validCoordinates($fromLat, $fromLng, $toLat, $toLng)
            || ! $this->withinCoverage($fromLat, $fromLng, $toLat, $toLng)) {
            return null;
        }

        $baseUrl = rtrim(trim((string) config('services.osrm.base_url')), '/');
        if ($baseUrl === '') {
            return null;
        }

        $coordinates = implode(',', [
            $this->coordinate($fromLng),
            $this->coordinate($fromLat),
        ]).';'.implode(',', [
            $this->coordinate($toLng),
            $this->coordinate($toLat),
        ]);
        $routeKey = sha1($coordinates);
        $cacheKey = "osrm:distance:{$routeKey}";
        $failureKey = "osrm:failure:{$routeKey}";
        $cached = Cache::get($cacheKey);

        if (is_numeric($cached)) {
            return (float) $cached;
        }
        if (Cache::get($failureKey)) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('services.osrm.connect_timeout_seconds', 1))
                ->timeout((int) config('services.osrm.timeout_seconds', 3))
                ->get("{$baseUrl}/route/v1/driving/{$coordinates}", [
                    'overview' => 'false',
                    'steps' => 'false',
                    'alternatives' => 'false',
                ]);

            $distanceMeters = $response->json('routes.0.distance');
            if ($response->successful() && $response->json('code') === 'Ok' && is_numeric($distanceMeters) && (float) $distanceMeters >= 0) {
                $distanceKm = round((float) $distanceMeters / 1000, 3);
                Cache::put(
                    $cacheKey,
                    $distanceKm,
                    now()->addHours((int) config('services.osrm.cache_hours', 24)),
                );

                return $distanceKm;
            }

            $this->rememberFailure($failureKey);
            Log::warning('OSRM distance request failed; using the next distance provider.', [
                'status' => $response->status(),
                'code' => $response->json('code'),
                'message' => $response->json('message'),
            ]);
        } catch (Throwable $exception) {
            $this->rememberFailure($failureKey);
            Log::warning('OSRM distance request failed; using the next distance provider.', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    private function coordinate(float $value): string
    {
        return number_format($value, 6, '.', '');
    }

    private function validCoordinates(float ...$coordinates): bool
    {
        [$fromLat, $fromLng, $toLat, $toLng] = $coordinates;

        return abs($fromLat) <= 90
            && abs($toLat) <= 90
            && abs($fromLng) <= 180
            && abs($toLng) <= 180;
    }

    private function withinCoverage(float ...$coordinates): bool
    {
        $configured = trim((string) config('services.osrm.coverage_bbox', ''));
        if ($configured === '') {
            return true;
        }

        $bbox = array_map('trim', explode(',', $configured));
        if (count($bbox) !== 4 || count(array_filter($bbox, 'is_numeric')) !== 4) {
            Log::warning('OSRM coverage bbox is invalid; skipping OSRM requests.');

            return false;
        }

        [$minLng, $minLat, $maxLng, $maxLat] = array_map('floatval', $bbox);
        [$fromLat, $fromLng, $toLat, $toLng] = $coordinates;

        return $fromLng >= $minLng && $fromLng <= $maxLng
            && $toLng >= $minLng && $toLng <= $maxLng
            && $fromLat >= $minLat && $fromLat <= $maxLat
            && $toLat >= $minLat && $toLat <= $maxLat;
    }

    private function rememberFailure(string $failureKey): void
    {
        Cache::put(
            $failureKey,
            true,
            now()->addSeconds((int) config('services.osrm.failure_cache_seconds', 120)),
        );
    }
}
