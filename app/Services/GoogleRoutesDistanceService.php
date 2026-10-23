<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleRoutesDistanceService
{
    private const ENDPOINT = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    public function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): ?float
    {
        $apiKey = trim((string) config('services.google_routes.api_key'));
        if ($apiKey === '') {
            return null;
        }

        $routeKey = sha1(implode(',', array_map(
            fn (float $value): string => number_format($value, 6, '.', ''),
            [$fromLat, $fromLng, $toLat, $toLng],
        )));
        $cacheKey = "google-routes:distance:{$routeKey}";
        $failureKey = "google-routes:failure:{$routeKey}";
        $cached = Cache::get($cacheKey);

        if (is_numeric($cached)) {
            return (float) $cached;
        }
        if (Cache::get($failureKey)) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                    'X-Goog-FieldMask' => 'routes.distanceMeters',
                ])
                ->connectTimeout((int) config('services.google_routes.connect_timeout_seconds', 2))
                ->timeout((int) config('services.google_routes.timeout_seconds', 4))
                ->retry(1, 200)
                ->post(self::ENDPOINT, [
                    'origin' => $this->waypoint($fromLat, $fromLng),
                    'destination' => $this->waypoint($toLat, $toLng),
                    'travelMode' => 'DRIVE',
                    'routingPreference' => 'TRAFFIC_UNAWARE',
                    'computeAlternativeRoutes' => false,
                    'languageCode' => 'en-US',
                    'units' => 'METRIC',
                ]);

            $distanceMeters = $response->json('routes.0.distanceMeters');
            if ($response->successful() && is_numeric($distanceMeters) && (float) $distanceMeters > 0) {
                $distanceKm = round((float) $distanceMeters / 1000, 3);
                Cache::put(
                    $cacheKey,
                    $distanceKm,
                    now()->addHours((int) config('services.google_routes.cache_hours', 24)),
                );

                return $distanceKm;
            }

            Cache::put($failureKey, true, now()->addMinutes(5));
            Log::warning('Google Routes distance request failed; using straight-line fallback.', [
                'status' => $response->status(),
                'error_status' => $response->json('error.status'),
                'error_message' => $response->json('error.message'),
            ]);
        } catch (Throwable $exception) {
            Cache::put($failureKey, true, now()->addMinutes(5));
            Log::warning('Google Routes distance request failed; using straight-line fallback.', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    private function waypoint(float $lat, float $lng): array
    {
        return [
            'location' => [
                'latLng' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ],
            ],
        ];
    }
}
