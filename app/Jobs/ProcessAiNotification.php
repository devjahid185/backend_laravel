<?php

namespace App\Jobs;

use App\Models\AiNotificationDecision;
use App\Models\AiNotificationSetting;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Services\AiNotificationContentService;
use App\Services\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessAiNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 90;

    public function __construct(public int $decisionId)
    {
        $this->onConnection('database');
        $this->onQueue('notifications');
    }

    public function handle(AiNotificationContentService $content, FcmService $fcm): void
    {
        $decision = AiNotificationDecision::query()->with('user.notificationPreference')->find($this->decisionId);
        if (! $decision || $decision->status !== 'queued') {
            return;
        }

        $settings = AiNotificationSetting::current();
        $preference = $decision->user?->notificationPreference;
        if (! $settings->is_enabled || ! $decision->user || ($preference && ! $preference->push_enabled)) {
            $decision->update(['status' => 'suppressed', 'failure_message' => 'Automation or user push notifications are disabled.']);
            return;
        }

        if (FoodOrder::query()->where('user_id', $decision->user_id)->whereIn('status', ['pending', 'accepted', 'preparing', 'picked_up', 'on_the_way'])->exists()) {
            $decision->update(['status' => 'suppressed', 'failure_message' => 'User has an active order.']);
            return;
        }

        $itemId = data_get($decision->context, 'food_item_id');
        if ($itemId) {
            $item = FoodItem::query()->with('restaurant')->find($itemId);
            if (! $item || ! $item->is_available || $item->status !== 'active' || ! $item->restaurant?->isOpenNow()) {
                $decision->update(['status' => 'suppressed', 'failure_message' => 'Recommended item or restaurant is unavailable.']);
                return;
            }
        }

        $tokens = DeviceToken::query()->where('user_id', $decision->user_id)->pluck('token')->filter()->unique()->values()->all();
        if ($tokens === []) {
            $decision->update(['status' => 'suppressed', 'failure_message' => 'No active device token.']);
            return;
        }

        try {
            $copy = $content->generate($settings, [
                'campaign' => $decision->campaign,
                'context' => $decision->context,
            ]);
            $data = [
                'type' => 'ai_marketing',
                'campaign' => $decision->campaign,
                'ai_decision_id' => (string) $decision->id,
                'deep_link' => $decision->deep_link,
                'title' => $copy['title'],
                'message' => $copy['message'],
                'image_url' => data_get($decision->context, 'image_url'),
            ];
            $notification = AppNotification::query()->create([
                'user_id' => $decision->user_id,
                'title' => $copy['title'],
                'message' => $copy['message'],
                'image_url' => $data['image_url'],
                'data' => $data,
            ]);
            $results = $fcm->sendToTokens($tokens, [
                'notification' => [
                    'title' => $copy['title'],
                    'body' => $copy['message'],
                    'image' => $data['image_url'],
                ],
                'data' => $data,
            ]);
            $successful = collect($results)->contains(fn (array $result) => ($result['status'] ?? 0) >= 200 && ($result['status'] ?? 0) < 300);
            if (! $successful) {
                throw new \RuntimeException('FCM did not accept the notification for any registered device.');
            }

            $decision->update([
                'app_notification_id' => $notification->id,
                'status' => 'sent',
                'title' => $copy['title'],
                'message' => $copy['message'],
                'copy_source' => $copy['copy_source'],
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $decision->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);
            Log::error('AI notification delivery failed.', ['decision_id' => $decision->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
