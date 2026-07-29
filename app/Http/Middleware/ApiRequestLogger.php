<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::info('API Request', [
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => optional($request->user())->id,
        ]);

        return $response;
    }
}
