<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AdminLiveUpdateService
{
    private const CACHE_KEY = 'admin_live_state_v2';

    public function current(): array
    {
        $state = Cache::get(self::CACHE_KEY, []);

        return [
            'version' => $state['version'] ?? 'bootstrap-v2',
            'time' => $state['time'] ?? now()->toIso8601String(),
            'modules' => array_values($state['modules'] ?? []),
            'module_versions' => $state['module_versions'] ?? [],
            'reason' => $state['reason'] ?? 'bootstrap',
        ];
    }

    public function publish(array $modules, string $reason): void
    {
        $modules = collect($modules)
            ->filter()
            ->push('dashboard')
            ->unique()
            ->values()
            ->all();

        if (! $modules) {
            return;
        }

        $state = Cache::get(self::CACHE_KEY, []);
        $version = now()->format('YmdHisv').'-'.Str::lower(Str::random(6));
        $moduleVersions = $state['module_versions'] ?? [];

        foreach ($modules as $module) {
            $moduleVersions[$module] = $version;
        }

        Cache::forever(self::CACHE_KEY, [
            'version' => $version,
            'time' => now()->toIso8601String(),
            'modules' => $modules,
            'module_versions' => $moduleVersions,
            'reason' => $reason,
        ]);
    }

    public function publishForRequest(Request $request): void
    {
        $modules = $this->modulesForPath(trim($request->path(), '/'));
        if ($modules) {
            $this->publish($modules, strtolower($request->method()).':'.$request->path());
        }
    }

    private function modulesForPath(string $path): array
    {
        $path = preg_replace('#^api/#', '', $path) ?: $path;

        if (preg_match('#^admin/resources/([^/]+)#', $path, $matches)) {
            return [$matches[1]];
        }

        if (preg_match('#^admin/([^/]+)#', $path, $matches)) {
            $slug = $matches[1];
            if (in_array($slug, ['live', 'stats', 'me', 'modules', 'login', 'logout'], true)) {
                return [];
            }

            return [match ($slug) {
                'admins' => 'staff-management',
                'food-menu-imports' => 'food-items',
                'analytics' => 'analytics',
                default => $slug,
            }];
        }

        if (str_starts_with($path, 'analytics/events') || str_starts_with($path, 'app-visit')) {
            return ['analytics'];
        }
        if (str_starts_with($path, 'facebook/webhook')) {
            return ['ai-social'];
        }
        if (str_starts_with($path, 'device-token') || str_starts_with($path, 'notifications/')) {
            return [];
        }
        if (str_starts_with($path, 'messages/typing')) {
            return [];
        }
        if (str_starts_with($path, 'messages') || str_starts_with($path, 'send-message')) {
            return ['messages'];
        }
        if (str_starts_with($path, 'reports')) {
            return ['reports'];
        }
        if (str_starts_with($path, 'reviews')) {
            return ['reviews'];
        }
        if (str_starts_with($path, 'riders')) {
            if (str_contains($path, 'support-tickets')) {
                return ['riders', 'support-tickets'];
            }
            if (str_contains($path, '/orders/')) {
                return ['riders', 'delivery-income', 'food-orders', 'medicine-orders'];
            }
            return ['riders', 'delivery-income'];
        }
        if (str_starts_with($path, 'medicine')) {
            if (str_contains($path, 'cart') || str_contains($path, 'delivery-charge-preview')) {
                return [];
            }
            if (str_contains($path, 'order') || str_contains($path, 'checkout')) {
                return ['medicine-orders', 'delivery-income'];
            }

            return ['medicine-items'];
        }
        if (str_starts_with($path, 'food')) {
            if (str_contains($path, 'cart')
                || str_contains($path, 'delivery-charge-preview')
                || str_contains($path, 'coupon-preview')
                || str_contains($path, 'addresses')
                || str_contains($path, 'favorites')) {
                return [];
            }
            if (str_contains($path, 'support-tickets')) {
                return ['food-orders', 'support-tickets'];
            }
            if (str_contains($path, 'order') || str_contains($path, 'checkout')) {
                return ['food-orders', 'delivery-income'];
            }
            if (str_contains($path, 'review')) {
                return ['food-reviews'];
            }
            if (str_contains($path, 'coupon')) {
                return ['food-coupons'];
            }
            if (str_contains($path, 'restaurant')) {
                return ['restaurants'];
            }

            return ['food-items'];
        }

        $root = Str::before($path, '/');
        $direct = [
            'register' => 'users',
            'register-otp' => 'users',
            'users' => 'users',
            'profile' => 'users',
            'update-profile' => 'users',
            'worker' => 'workers',
            'workers' => 'workers',
            'business' => 'businesses',
            'businesses' => 'businesses',
            'marketplace' => 'marketplace',
            'items' => 'marketplace',
            'jobs' => 'jobs',
            'doctors' => 'doctors',
            'doctor-appointments' => 'doctors',
            'hospitals' => 'hospitals',
            'hotels' => 'hotels',
            'restaurants' => 'restaurants',
            'properties' => 'property',
            'education' => 'education',
            'teachers' => 'education',
            'teacher-requests' => 'education',
            'student-requests' => 'education',
            'blood-donor' => 'blood',
            'blood-requests' => 'blood',
            'blood' => 'blood',
            'couriers' => 'courier',
            'car-rentals' => 'car-rental',
            'launches' => 'launches',
            'electricity' => 'electricity',
            'emergency' => 'emergency',
            'news' => 'news',
            'notices' => 'notices',
            'updates' => 'updates',
            'faqs' => 'faqs',
            'payments' => 'payments',
            'payment' => 'payments',
            'book-service' => 'workers',
            'cancel-booking' => 'workers',
            'support' => 'support-tickets',
        ];

        return isset($direct[$root]) ? [$direct[$root]] : [];
    }
}
