<?php

namespace App\Services;

use App\Models\AiNotificationDecision;
use App\Models\AiNotificationSetting;
use App\Models\AnalyticsEvent;
use App\Models\FoodCart;
use App\Models\FoodItem;
use App\Models\FoodOrder;
use App\Models\User;
use Carbon\CarbonImmutable;

class AiNotificationDecisionService
{
    private const ACTIVE_ORDER_STATUSES = ['pending', 'accepted', 'preparing', 'picked_up', 'on_the_way'];

    public function decide(User $user, AiNotificationSetting $settings, CarbonImmutable $now): ?array
    {
        if ($this->suppressed($user, $settings, $now)) {
            return null;
        }

        $orders = FoodOrder::query()
            ->with(['items', 'restaurant'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(12)
            ->get();
        $lastOrder = $orders->first();
        $lastDelivered = $orders->firstWhere('status', 'delivered');
        $lastRejected = $orders->firstWhere('status', 'rejected');
        $cart = FoodCart::query()
            ->with(['items.foodItem.restaurant', 'restaurant'])
            ->where('user_id', $user->id)
            ->first();
        $lastCheckout = AnalyticsEvent::query()
            ->where('user_id', $user->id)
            ->where('event_name', 'checkout_started')
            ->latest('occurred_at')
            ->first();

        $candidates = [];

        if ($settings->campaignEnabled('rejected_recovery') && $lastRejected?->rejected_at?->between($now->subHours(24), $now)) {
            $item = $this->recommendedItem((int) $lastRejected->restaurant_id);
            if ($item) {
                $candidates[] = $this->candidate(
                    'rejected_recovery', 98, 'rejected-'.$lastRejected->id, $item,
                    'আপনার আগের অর্ডারটি সম্পন্ন হয়নি। কাছাকাছি খোলা অন্য রেস্টুরেন্ট থেকে খাবার দেখতে পারেন।',
                    $now, true,
                );
            }
        }

        if ($cart && $cart->items->isNotEmpty()) {
            $cartUpdatedAt = $cart->items->max('updated_at') ?? $cart->updated_at;
            $minutes = $cartUpdatedAt ? $cartUpdatedAt->diffInMinutes($now) : 0;
            $item = $cart->items->sortByDesc('updated_at')->first()?->foodItem;
            if ($item && $this->itemCanReceiveOrders($item)) {
                if ($settings->campaignEnabled('checkout_abandoned') && $lastCheckout && $lastCheckout->occurred_at->greaterThanOrEqualTo($cartUpdatedAt) && $lastCheckout->occurred_at->between($now->subHours(3), $now->subMinutes(20))) {
                    $candidates[] = $this->candidate(
                        'checkout_abandoned', 96, 'checkout-'.$lastCheckout->id, $item,
                        'আপনার অর্ডারটি এখনো নিশ্চিত হয়নি। কার্ট ও ডেলিভারি তথ্য দেখে অর্ডারটি সম্পন্ন করুন।',
                        $now, true, 'bholavashi://food/cart',
                    );
                } elseif ($settings->campaignEnabled('cart_abandoned') && $minutes >= 30 && $minutes <= 360) {
                    $candidates[] = $this->candidate(
                        'cart_abandoned', 92, 'cart-'.$cart->id.'-'.$cartUpdatedAt?->timestamp, $item,
                        'আপনার পছন্দের খাবারটি এখনো কার্টে আছে। রেস্টুরেন্ট খোলা থাকতেই অর্ডারটি সম্পন্ন করুন।',
                        $now, true, 'bholavashi://food/cart',
                    );
                }
            }
        }

        if ($settings->campaignEnabled('new_user') && $orders->isEmpty() && $user->created_at?->between($now->subHours(72), $now->subHour())) {
            $item = $this->recommendedItem();
            if ($item) {
                $candidates[] = $this->candidate(
                    'new_user', 82, 'new-user-'.$user->id, $item,
                    'ভোলাবাসী ফুড ডেলিভারিতে পছন্দের খাবার বেছে খুব সহজে অর্ডার করতে পারবেন।',
                    $now, false,
                );
            }
        }

        if ($settings->campaignEnabled('reorder') && $lastDelivered?->delivered_at?->between($now->subDays(14), $now->subDays(3))) {
            $item = $this->orderItemTarget($lastDelivered) ?: $this->recommendedItem();
            if ($item) {
                $candidates[] = $this->candidate(
                    'reorder', 78, 'reorder-'.$lastDelivered->id.'-'.$now->format('o-W'), $item,
                    'আগের পছন্দের খাবারটি আবার অর্ডার করতে পারেন। দাম ও প্রাপ্যতা দেখে নিন।',
                    $now, false,
                );
            }
        }

        $lastActivity = AnalyticsEvent::query()
            ->where('user_id', $user->id)
            ->whereIn('event_name', ['app_open', 'screen_view', 'add_to_cart', 'purchase'])
            ->latest('occurred_at')
            ->first()?->occurred_at;
        if ($settings->campaignEnabled('inactive_user') && $lastActivity && $lastActivity->lessThanOrEqualTo($now->subDays(15))) {
            $item = $this->preferredItem($orders) ?: $this->recommendedItem();
            if ($item) {
                $candidates[] = $this->candidate(
                    'inactive_user', 72, 'inactive-'.$user->id.'-'.$now->format('o-W'), $item,
                    'অনেক দিন ভোলাবাসীতে খাবার দেখা হয়নি। এখন খোলা রেস্টুরেন্টের পছন্দের খাবারগুলো দেখে নিন।',
                    $now, false,
                );
            }
        }

        if ($settings->campaignEnabled('personalized') && $lastDelivered?->delivered_at?->lessThanOrEqualTo($now->subDays(7))) {
            $item = $this->preferredItem($orders);
            if ($item) {
                $candidates[] = $this->candidate(
                    'personalized', 66, 'personalized-'.$user->id.'-'.$now->format('Y-m-d'), $item,
                    'আপনার আগের পছন্দ অনুযায়ী একটি খাবার পাওয়া যাচ্ছে। অর্ডারের আগে বর্তমান দাম ও তথ্য দেখে নিন।',
                    $now, false,
                );
            }
        }

        return collect($candidates)
            ->filter(fn (array $candidate) => $candidate['score'] >= 65
                && $this->isSendTime($user, $candidate, $now)
                && ! $this->decisionExists($candidate['dedupe_key']))
            ->sortByDesc('score')
            ->first();
    }

    public function fallback(User $user, AiNotificationSetting $settings, CarbonImmutable $now): ?array
    {
        if (! $settings->campaignEnabled('discovery') || $this->suppressed($user, $settings, $now)) {
            return null;
        }

        $item = $this->recommendedItem();
        if (! $item) {
            return null;
        }

        $candidate = $this->candidate(
            'discovery',
            65,
            'discovery-'.$user->id.'-'.$now->format('Y-m-d'),
            $item,
            'আজ খোলা রেস্টুরেন্ট থেকে আপনার পছন্দের খাবারটি দেখে নিতে পারেন।',
            $now,
            true,
        );

        return $this->decisionExists($candidate['dedupe_key']) ? null : $candidate;
    }

    public function freeDeliveryCampaign(User $user, AiNotificationSetting $settings, CarbonImmutable $now): ?array
    {
        if (! $settings->campaignEnabled('free_delivery') || $this->suppressed($user, $settings, $now)) {
            return null;
        }

        $item = $this->recommendedItem();
        if (! $item) {
            return null;
        }

        $candidate = $this->candidate(
            'free_delivery',
            90,
            'free-delivery-'.$user->id.'-'.$now->format('o-W'),
            $item,
            'ভোলা পৌরসভার ভিতরে ডেলিভারি চার্জ ফ্রি। পৌরসভার বাইরে প্রতি অর্ডারের ডেলিভারি চার্জ থেকে ৪০ টাকা ছাড় পাবেন।',
            $now,
            true,
            'bholavashi://food',
        );
        $candidate['context']['offer_inside_pourashava'] = 'ডেলিভারি চার্জ ফ্রি';
        $candidate['context']['offer_outside_pourashava'] = 'ডেলিভারি চার্জ থেকে ৪০ টাকা ছাড়';

        return $this->decisionExists($candidate['dedupe_key']) ? null : $candidate;
    }

    private function suppressed(User $user, AiNotificationSetting $settings, CarbonImmutable $now): bool
    {
        if (FoodOrder::query()->where('user_id', $user->id)->whereIn('status', self::ACTIVE_ORDER_STATUSES)->exists()) {
            return true;
        }

        $sent = AiNotificationDecision::query()->where('user_id', $user->id)->where('status', 'sent');
        if ((clone $sent)->where('sent_at', '>=', $now->startOfDay())->count() >= $settings->max_promotional_per_day) {
            return true;
        }
        if ((clone $sent)->where('sent_at', '>=', $now->startOfWeek())->count() >= $settings->max_promotional_per_week) {
            return true;
        }

        return (clone $sent)->where('sent_at', '>=', $now->subHours($settings->minimum_gap_hours))->exists();
    }

    private function candidate(string $campaign, int $score, string $anchor, FoodItem $item, string $fallback, CarbonImmutable $now, bool $urgent, ?string $deepLink = null): array
    {
        return [
            'campaign' => $campaign,
            'score' => $score,
            'dedupe_key' => $campaign.'-'.$anchor,
            'scheduled_for' => $now,
            'deep_link' => $deepLink ?: 'bholavashi://food/items/'.$item->id,
            'urgent' => $urgent,
            'context' => [
                'fallback_message' => $fallback,
                'food_item_id' => $item->id,
                'food_item_name' => $item->name,
                'restaurant_id' => $item->restaurant_id,
                'restaurant_name' => $item->restaurant?->name,
                'price' => (float) ($item->discount_price ?: $item->price),
                'has_discount' => $item->discount_price !== null && (float) $item->discount_price < (float) $item->price,
                'image_url' => $this->imageUrl($item->image_url),
            ],
        ];
    }

    private function decisionExists(string $dedupeKey): bool
    {
        return AiNotificationDecision::query()->where('dedupe_key', $dedupeKey)->exists();
    }

    private function isSendTime(User $user, array $candidate, CarbonImmutable $now): bool
    {
        if ($candidate['urgent']) {
            return true;
        }

        $preferredHour = FoodOrder::query()
            ->where('user_id', $user->id)
            ->where('status', 'delivered')
            ->selectRaw('HOUR(created_at) as order_hour, COUNT(*) as total')
            ->groupBy('order_hour')
            ->orderByDesc('total')
            ->value('order_hour');
        $targetHour = $preferredHour === null
            ? (in_array($candidate['campaign'], ['new_user', 'reorder'], true) ? 18 : 11)
            : max(10, min(20, (int) $preferredHour - 1));

        return $now->hour === $targetHour;
    }

    private function recommendedItem(?int $excludeRestaurantId = null): ?FoodItem
    {
        return FoodItem::query()
            ->with('restaurant')
            ->where('status', 'active')
            ->where('is_available', true)
            ->when($excludeRestaurantId, fn ($q) => $q->where('restaurant_id', '!=', $excludeRestaurantId))
            ->whereHas('restaurant', fn ($q) => $q->where('status', 'active')->where('accepts_food_orders', true))
            ->orderByDesc('is_promoted')
            ->orderByDesc('is_popular')
            ->orderByDesc('rating')
            ->limit(30)
            ->get()
            ->filter(fn (FoodItem $item) => $this->itemCanReceiveOrders($item))
            ->shuffle()
            ->first();
    }

    private function preferredItem($orders): ?FoodItem
    {
        $itemId = $orders->where('status', 'delivered')->flatMap->items
            ->groupBy('food_item_id')
            ->sortByDesc(fn ($items) => $items->count())
            ->keys()
            ->filter()
            ->first();
        $item = $itemId ? FoodItem::query()->with('restaurant')->find($itemId) : null;

        return $item && $this->itemCanReceiveOrders($item) ? $item : null;
    }

    private function orderItemTarget(FoodOrder $order): ?FoodItem
    {
        $itemId = $order->items->pluck('food_item_id')->filter()->first();
        $item = $itemId ? FoodItem::query()->with('restaurant')->find($itemId) : null;

        return $item && $this->itemCanReceiveOrders($item) ? $item : null;
    }

    private function itemCanReceiveOrders(FoodItem $item): bool
    {
        return $item->status === 'active'
            && (bool) $item->is_available
            && $item->restaurant
            && $item->restaurant->isOpenNow();
    }

    private function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $base = rtrim((string) (env('NOTIFICATION_PUBLIC_URL') ?: config('app.url')), '/');
        $normalized = ltrim($path, '/');

        return $base.'/'.(str_starts_with($normalized, 'storage/') ? $normalized : 'storage/'.$normalized);
    }
}
