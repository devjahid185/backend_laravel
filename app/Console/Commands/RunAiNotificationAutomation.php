<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAiNotification;
use App\Models\AiNotificationDecision;
use App\Models\AiNotificationSetting;
use App\Models\User;
use App\Services\AiNotificationDecisionService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RunAiNotificationAutomation extends Command
{
    protected $signature = 'ai-notifications:run
        {--force : Ignore quiet hours for an administrator-triggered run}
        {--dry-run : Evaluate eligible users without creating or sending notifications}';
    protected $description = 'Evaluate users and queue safe personalized AI push notifications';

    public function handle(AiNotificationDecisionService $engine): int
    {
        $lock = Cache::lock('ai-notifications:run', 540);
        if (! $lock->get()) {
            $this->warn('AI notification automation is already running.');
            return self::SUCCESS;
        }

        try {
            $settings = AiNotificationSetting::current();
            if (! $settings->is_enabled) {
                $this->info('AI notification automation is disabled.');
                return self::SUCCESS;
            }

            $now = CarbonImmutable::now($settings->timezone ?: 'Asia/Dhaka');
            if (! $this->option('force') && $this->isQuietHours($settings, $now)) {
                $this->info('Quiet hours are active.');
                return self::SUCCESS;
            }

            $evaluated = 0;
            $queued = 0;
            $eligible = 0;
            $campaigns = [];
            $selectedUserIds = [];
            $users = User::query()
                ->where('is_blocked', false)
                ->whereHas('deviceTokens')
                ->where(function ($query): void {
                    $query->whereDoesntHave('notificationPreference')
                        ->orWhereHas('notificationPreference', fn ($q) => $q->where('push_enabled', true));
                })
                ->withMax([
                    'aiNotificationDecisions as last_ai_notification_at' => fn ($query) => $query->where('status', 'sent'),
                ], 'sent_at')
                ->inRandomOrder()
                ->limit(max(10, min(1000, $settings->batch_limit * 8)))
                ->get();

            $users->each(function (User $user) use ($engine, $settings, $now, &$evaluated, &$queued, &$eligible, &$campaigns, &$selectedUserIds): void {
                $evaluated++;
                $candidate = $engine->decide($user, $settings, $now);
                if (! $candidate || $queued >= $settings->batch_limit) {
                    return;
                }

                $eligible++;
                $campaigns[$candidate['campaign']] = ($campaigns[$candidate['campaign']] ?? 0) + 1;
                $selectedUserIds[(int) $user->id] = true;
                if ($this->option('dry-run')) {
                    return;
                }

                $decision = AiNotificationDecision::query()->firstOrCreate(
                    ['dedupe_key' => $candidate['dedupe_key']],
                    [
                        'user_id' => $user->id,
                        'campaign' => $candidate['campaign'],
                        'status' => 'queued',
                        'score' => $candidate['score'],
                        'deep_link' => $candidate['deep_link'],
                        'context' => $candidate['context'],
                        'scheduled_for' => $candidate['scheduled_for'],
                    ],
                );
                if ($decision->wasRecentlyCreated) {
                    ProcessAiNotification::dispatch($decision->id);
                    $queued++;
                }
            });

            $campaignQueued = 0;
            $campaignTarget = min(2, max(0, $settings->batch_limit - $queued));
            if ($campaignTarget > 0) {
                $campaignUsers = $users->sortBy(
                    fn (User $user) => $user->last_ai_notification_at
                        ? strtotime((string) $user->last_ai_notification_at)
                        : 0,
                );

                foreach ($campaignUsers as $user) {
                    if (isset($selectedUserIds[(int) $user->id])) {
                        continue;
                    }

                    $candidate = $engine->freeDeliveryCampaign($user, $settings, $now);
                    if (! $candidate) {
                        continue;
                    }

                    $eligible++;
                    $campaigns[$candidate['campaign']] = ($campaigns[$candidate['campaign']] ?? 0) + 1;
                    $selectedUserIds[(int) $user->id] = true;
                    if ($this->option('dry-run')) {
                        $campaignQueued++;
                        if ($campaignQueued >= $campaignTarget) {
                            break;
                        }
                        continue;
                    }

                    $decision = AiNotificationDecision::query()->firstOrCreate(
                        ['dedupe_key' => $candidate['dedupe_key']],
                        [
                            'user_id' => $user->id,
                            'campaign' => $candidate['campaign'],
                            'status' => 'queued',
                            'score' => $candidate['score'],
                            'deep_link' => $candidate['deep_link'],
                            'context' => $candidate['context'],
                            'scheduled_for' => $candidate['scheduled_for'],
                        ],
                    );
                    if (! $decision->wasRecentlyCreated) {
                        continue;
                    }

                    ProcessAiNotification::dispatch($decision->id);
                    $queued++;
                    $campaignQueued++;
                    if ($campaignQueued >= $campaignTarget) {
                        break;
                    }
                }
            }

            $needsFallback = $this->option('dry-run') ? $eligible === 0 : $queued === 0;
            if ($needsFallback) {
                $fallbackUsers = $users->sortBy(
                    fn (User $user) => $user->last_ai_notification_at
                        ? strtotime((string) $user->last_ai_notification_at)
                        : 0,
                );

                foreach ($fallbackUsers as $user) {
                    $candidate = $engine->fallback($user, $settings, $now);
                    if (! $candidate) {
                        continue;
                    }

                    $eligible++;
                    $campaigns[$candidate['campaign']] = ($campaigns[$candidate['campaign']] ?? 0) + 1;
                    if ($this->option('dry-run')) {
                        break;
                    }

                    $decision = AiNotificationDecision::query()->firstOrCreate(
                        ['dedupe_key' => $candidate['dedupe_key']],
                        [
                            'user_id' => $user->id,
                            'campaign' => $candidate['campaign'],
                            'status' => 'queued',
                            'score' => $candidate['score'],
                            'deep_link' => $candidate['deep_link'],
                            'context' => $candidate['context'],
                            'scheduled_for' => $candidate['scheduled_for'],
                        ],
                    );
                    if (! $decision->wasRecentlyCreated) {
                        continue;
                    }

                    ProcessAiNotification::dispatch($decision->id);
                    $queued++;
                    break;
                }
            }

            $campaignSummary = collect($campaigns)->map(fn ($count, $campaign) => "{$campaign}: {$count}")->implode(', ');
            $summary = "Evaluated {$evaluated} users; eligible {$eligible}; queued {$queued} notifications."
                .($campaignSummary ? " Campaigns: {$campaignSummary}." : '');
            if (! $this->option('dry-run')) {
                $settings->update(['last_run_at' => now(), 'last_run_summary' => $summary]);
            }
            $this->info($summary);
            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function isQuietHours(AiNotificationSetting $settings, CarbonImmutable $now): bool
    {
        $current = $now->format('H:i:s');
        $start = (string) $settings->quiet_hours_start;
        $end = (string) $settings->quiet_hours_end;
        if ($start === $end) {
            return false;
        }

        return $start < $end
            ? $current >= $start && $current < $end
            : $current >= $start || $current < $end;
    }
}
