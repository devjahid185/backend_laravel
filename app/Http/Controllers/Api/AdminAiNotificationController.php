<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiNotificationDecision;
use App\Models\AiNotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AdminAiNotificationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'campaign' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:30'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $settings = AiNotificationSetting::current();
        $week = now()->subDays(7);
        $stats = AiNotificationDecision::query()
            ->where('created_at', '>=', $week)
            ->selectRaw("count(*) as total, sum(status = 'sent') as sent, sum(opened_at is not null) as opened, sum(converted_at is not null) as converted, sum(status = 'failed') as failed")
            ->first();
        $campaigns = AiNotificationDecision::query()
            ->where('created_at', '>=', $week)
            ->select('campaign', DB::raw('count(*) as total'), DB::raw("sum(status = 'sent') as sent"), DB::raw('sum(opened_at is not null) as opened'), DB::raw('sum(converted_at is not null) as converted'))
            ->groupBy('campaign')
            ->orderByDesc('total')
            ->get();
        $decisions = AiNotificationDecision::query()
            ->with('user:id,name,phone,email')
            ->when($filters['campaign'] ?? null, fn ($query, $campaign) => $query->where('campaign', $campaign))
            ->when($filters['status'] ?? null, function ($query, $status): void {
                match ($status) {
                    'opened' => $query->whereNotNull('opened_at')->whereNull('converted_at'),
                    'converted' => $query->whereNotNull('converted_at'),
                    default => $query->where('status', $status),
                };
            })
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $term = '%'.trim($search).'%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('title', 'like', $term)
                        ->orWhere('message', 'like', $term)
                        ->orWhere('deep_link', 'like', $term)
                        ->orWhereHas('user', function ($user) use ($term): void {
                            $user->where('name', 'like', $term)
                                ->orWhere('phone', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 20));

        return response()->json([
            'settings' => $settings,
            'stats' => $stats,
            'campaigns' => $campaigns,
            'decisions' => $decisions,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'use_ai_copy' => ['required', 'boolean'],
            'timezone' => ['required', 'timezone'],
            'quiet_hours_start' => ['required', 'date_format:H:i'],
            'quiet_hours_end' => ['required', 'date_format:H:i'],
            'max_promotional_per_day' => ['required', 'integer', 'min:1', 'max:3'],
            'max_promotional_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'minimum_gap_hours' => ['required', 'integer', 'min:6', 'max:168'],
            'batch_limit' => ['required', 'integer', 'min:1', 'max:200'],
            'campaigns' => ['required', 'array'],
            'campaigns.*' => ['boolean'],
        ]);
        $settings = AiNotificationSetting::current();
        $settings->update($data);

        return response()->json(['message' => 'AI notification automation settings saved.', 'settings' => $settings->fresh()]);
    }

    public function run(): JsonResponse
    {
        Artisan::call('ai-notifications:run', ['--force' => true]);

        return response()->json(['message' => 'AI notification evaluation completed.', 'output' => trim(Artisan::output())]);
    }
}
