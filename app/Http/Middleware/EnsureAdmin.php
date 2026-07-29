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
                    return $next($request);
                }
            }

            Log::info('EnsureAdmin denied', [
                'path' => $request->path(),
                'user_class' => $userClass,
                'tokenable_class' => $tokenClass,
            ]);
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
