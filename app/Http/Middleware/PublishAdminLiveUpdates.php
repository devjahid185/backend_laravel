<?php

namespace App\Http\Middleware;

use App\Services\AdminLiveUpdateService;
use App\Services\TelegramAdminEventService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PublishAdminLiveUpdates
{
    public function __construct(
        private readonly AdminLiveUpdateService $liveUpdates,
        private readonly TelegramAdminEventService $telegramEvents,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true)
            && $response->getStatusCode() >= 200
            && $response->getStatusCode() < 400) {
            try {
                $this->liveUpdates->publishForRequest($request);
            } catch (\Throwable $e) {
                Log::warning('Unable to publish admin live update.', [
                    'path' => $request->path(),
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $this->telegramEvents->notifyForRequest($request, $response);
            } catch (\Throwable $e) {
                Log::warning('Unable to queue Telegram admin alert.', [
                    'path' => $request->path(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }
}
