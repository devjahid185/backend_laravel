<?php

namespace Tests\Unit;

use App\Services\GoogleRoutesDistanceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleRoutesDistanceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'services.google_routes.api_key' => 'test-key',
            'services.google_routes.connect_timeout_seconds' => 1,
            'services.google_routes.timeout_seconds' => 1,
            'services.google_routes.cache_hours' => 24,
        ]);
        Cache::clear();
    }

    public function test_it_returns_and_caches_google_driving_distance(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response([
                'routes' => [['distanceMeters' => 19700]],
            ]),
        ]);

        $service = app(GoogleRoutesDistanceService::class);

        $this->assertSame(19.7, $service->distanceKm(22.6884535, 90.6402483, 22.5990189, 90.7469898));
        $this->assertSame(19.7, $service->distanceKm(22.6884535, 90.6402483, 22.5990189, 90.7469898));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request->hasHeader('X-Goog-FieldMask', 'routes.distanceMeters')
            && $request['travelMode'] === 'DRIVE'
            && $request['routingPreference'] === 'TRAFFIC_UNAWARE');
    }

    public function test_it_throttles_failed_requests_for_fallback_usage(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response([
                'error' => ['status' => 'PERMISSION_DENIED', 'message' => 'Blocked'],
            ], 403),
        ]);

        $service = app(GoogleRoutesDistanceService::class);

        $this->assertNull($service->distanceKm(22.6884535, 90.6402483, 22.5990189, 90.7469898));
        $this->assertNull($service->distanceKm(22.6884535, 90.6402483, 22.5990189, 90.7469898));
        Http::assertSentCount(1);
    }
}
