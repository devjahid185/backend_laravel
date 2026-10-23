<?php

namespace Tests\Unit;

use App\Services\OsrmDistanceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OsrmDistanceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'services.osrm.enabled' => true,
            'services.osrm.base_url' => 'http://127.0.0.1:5000',
            'services.osrm.coverage_bbox' => '90.45,21.65,91.20,22.95',
            'services.osrm.connect_timeout_seconds' => 1,
            'services.osrm.timeout_seconds' => 1,
            'services.osrm.cache_hours' => 24,
            'services.osrm.failure_cache_seconds' => 120,
        ]);
        Cache::clear();
    }

    public function test_it_returns_and_caches_osrm_driving_distance(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 19700.4]],
            ]),
        ]);

        $service = app(OsrmDistanceService::class);

        $this->assertSame(19.7, $service->distanceKm(22.6884535, 90.6402483, 22.5990189, 90.7469898));
        $this->assertSame(19.7, $service->distanceKm(22.6884535, 90.6402483, 22.5990189, 90.7469898));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => str_contains(
            $request->url(),
            '/route/v1/driving/90.640248,22.688454;90.746990,22.599019',
        ));
    }

    public function test_it_throttles_failed_requests_for_fallback_usage(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/*' => Http::response([
                'code' => 'NoRoute',
                'message' => 'Impossible route between points',
            ], 400),
        ]);

        $service = app(OsrmDistanceService::class);

        $this->assertNull($service->distanceKm(22.6884535, 90.6402483, 22.5990189, 90.7469898));
        $this->assertNull($service->distanceKm(22.6884535, 90.6402483, 22.5990189, 90.7469898));
        Http::assertSentCount(1);
    }

    public function test_it_does_not_call_osrm_when_disabled(): void
    {
        config(['services.osrm.enabled' => false]);
        Http::fake();

        $this->assertNull(app(OsrmDistanceService::class)->distanceKm(22.6884535, 90.6402483, 22.5990189, 90.7469898));
        Http::assertNothingSent();
    }

    public function test_it_does_not_snap_locations_from_outside_the_loaded_map(): void
    {
        Http::fake();

        $this->assertNull(app(OsrmDistanceService::class)->distanceKm(23.8103, 90.4125, 22.5990189, 90.7469898));
        Http::assertNothingSent();
    }
}
