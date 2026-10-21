<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\AiNotificationDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AnalyticsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_name' => ['required', 'string', 'max:80'],
            'screen_name' => ['nullable', 'string', 'max:120'],
            'session_id' => ['nullable', 'string', 'max:80'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:40'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'parameters' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $userId = $request->user()?->id;
        if (!$userId && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            $userId = $token?->tokenable?->id;
        }

        AnalyticsEvent::query()->create([
            'user_id' => $userId,
            'event_name' => $data['event_name'],
            'screen_name' => $data['screen_name'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'device_id' => $data['device_id'] ?? null,
            'platform' => $data['platform'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'parameters' => $data['parameters'] ?? [],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'occurred_at' => isset($data['occurred_at'])
                ? Carbon::parse($data['occurred_at'])
                : now(),
        ]);

        $decisionId = (int) data_get($data, 'parameters.ai_decision_id', 0);
        if ($decisionId > 0 && $userId) {
            $decision = AiNotificationDecision::query()
                ->whereKey($decisionId)
                ->where('user_id', $userId)
                ->first();
            if ($decision && $data['event_name'] === 'notification_open') {
                $decision->update(['opened_at' => $decision->opened_at ?: now()]);
            }
        }

        if ($userId && $data['event_name'] === 'purchase') {
            AiNotificationDecision::query()
                ->where('user_id', $userId)
                ->where('status', 'sent')
                ->whereNull('converted_at')
                ->where('sent_at', '>=', now()->subHours(24))
                ->latest('sent_at')
                ->limit(1)
                ->update(['converted_at' => now()]);
        }

        return response()->json(['message' => 'Event logged']);
    }

    public function overview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $hours = min(168, max(1, (int) ($data['hours'] ?? 24)));
        $hasDateRange = ! empty($data['date_from']) || ! empty($data['date_to']);
        $rangeStart = $hasDateRange && ! empty($data['date_from'])
            ? Carbon::parse($data['date_from'])->startOfDay()
            : now()->subHours($hours);
        $rangeEnd = $hasDateRange && ! empty($data['date_to'])
            ? Carbon::parse($data['date_to'])->endOfDay()
            : now();
        $today = now()->startOfDay();
        $week = now()->subDays(7);

        $base = AnalyticsEvent::query();
        $window = (clone $base)->whereBetween('occurred_at', [$rangeStart, $rangeEnd]);

        $topEvents = (clone $window)
            ->select('event_name', DB::raw('count(*) as total'))
            ->groupBy('event_name')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $topScreens = (clone $window)
            ->whereNotNull('screen_name')
            ->select('screen_name', DB::raw('count(*) as total'))
            ->groupBy('screen_name')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $trendFormat = $hasDateRange ? '%Y-%m-%d' : '%Y-%m-%d %H:00:00';
        $trend = (clone $window)
            ->selectRaw("DATE_FORMAT(occurred_at, ?) as bucket, count(*) as events, count(distinct session_id) as sessions, count(distinct user_id) as users", [$trendFormat])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $recent = (clone $window)
            ->with('user:id,name,phone,email')
            ->latest('occurred_at')
            ->limit(30)
            ->get(['id', 'user_id', 'event_name', 'screen_name', 'session_id', 'platform', 'app_version', 'parameters', 'occurred_at']);

        return response()->json([
            'summary' => [
                'events_window' => (clone $window)->count(),
                'active_sessions_window' => (clone $window)->distinct('session_id')->count('session_id'),
                'active_users_window' => (clone $window)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'events_today' => (clone $base)->where('occurred_at', '>=', $today)->count(),
                'events_week' => (clone $base)->where('occurred_at', '>=', $week)->count(),
                'range_start' => $rangeStart->toDateTimeString(),
                'range_end' => $rangeEnd->toDateTimeString(),
                'range_mode' => $hasDateRange ? 'date' : 'hours',
            ],
            'top_events' => $topEvents,
            'top_screens' => $topScreens,
            'hourly' => $trend,
            'recent' => $recent,
        ]);
    }
}
