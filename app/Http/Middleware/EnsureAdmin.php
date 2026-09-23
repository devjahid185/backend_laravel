<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $userClass = $user ? get_class($user) : null;
        $tokenClass = null;
        if (! $user instanceof Admin) {
            $token = $request->bearerToken();
            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                $tokenable = $accessToken?->tokenable;
                $tokenClass = $tokenable ? get_class($tokenable) : null;
                if ($tokenable instanceof Admin) {
                    return $this->authorizeAdmin($request, $next, $tokenable);
                }
            }

            Log::info('EnsureAdmin denied', [
                'path' => $request->path(),
                'user_class' => $userClass,
                'tokenable_class' => $tokenClass,
            ]);
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $this->authorizeAdmin($request, $next, $user);
    }

    private function authorizeAdmin(Request $request, Closure $next, Admin $admin): Response
    {
        $request->setUserResolver(fn () => $admin);

        if (! $admin->is_active) {
            return response()->json(['message' => 'This staff account is inactive.'], 403);
        }

        [$module, $action] = $this->permissionFor($request);
        if ($module && ! $admin->canAccessAdminModule($module, $action)) {
            return response()->json(['message' => 'You do not have permission for this admin action.'], 403);
        }

        return $next($request);
    }

    private function permissionFor(Request $request): array
    {
        $path = trim($request->path(), '/');
        $method = strtoupper($request->method());
        $action = match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };

        if (in_array($path, ['api/admin/me', 'api/admin/logout', 'api/admin/modules'], true)) {
            return [null, 'view'];
        }
        if ($path === 'api/admin/stats') {
            return ['dashboard', 'view'];
        }
        if (str_starts_with($path, 'api/admin/profile')) {
            return ['profile', $method === 'GET' ? 'view' : 'update'];
        }
        if (str_starts_with($path, 'api/admin/admins')) {
            return ['staff-management', $action];
        }
        if ($path === 'api/admin/notifications/send') {
            return ['notifications', 'create'];
        }
        if ($path === 'api/admin/food-orders/payment-summary') {
            return ['food-orders', 'view'];
        }
        if ($path === 'api/admin/delivery-income-summary') {
            return ['delivery-income', 'view'];
        }
        if (preg_match('#^api/admin/resources/([^/]+)#', $path, $matches)) {
            return [$matches[1], $action];
        }

        $direct = [
            'api/admin/users' => 'users',
            'api/admin/reports' => 'reports',
            'api/admin/reviews' => 'reviews',
            'api/admin/notifications' => 'notifications',
            'api/admin/sms-settings' => 'sms-settings',
            'api/admin/email-settings' => 'email-settings',
            'api/admin/food-delivery-settings' => 'food-delivery-settings',
            'api/admin/medicine-payment-settings' => 'medicine-payment-settings',
            'api/admin/app-version-settings' => 'app-version-settings',
            'api/admin/map-settings' => 'map-settings',
            'api/admin/rider-settings' => 'rider-settings',
            'api/admin/support-settings' => 'support-settings',
            'api/admin/home-banners' => 'home-banners',
            'api/admin/home-service-shortcuts' => 'home-service-shortcuts',
        ];

        foreach ($direct as $prefix => $module) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return [$module, $action];
            }
        }

        return [null, 'view'];
    }
}
