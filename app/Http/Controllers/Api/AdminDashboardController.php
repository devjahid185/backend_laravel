<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\AppVisitLog;
use App\Models\BloodDonor;
use App\Models\Business;
use App\Models\CarRental;
use App\Models\CourierOffice;
use App\Models\DeviceToken;
use App\Models\Doctor;
use App\Models\EducationInstitute;
use App\Models\ElectricityOffice;
use App\Models\EmergencyContact;
use App\Models\FoodItem;
use App\Models\FoodBanner;
use App\Models\FoodCart;
use App\Models\FoodCategory;
use App\Models\FoodCoupon;
use App\Models\FoodOrder;
use App\Models\FoodOrderSupportTicket;
use App\Models\FoodReview;
use App\Models\Faq;
use App\Models\Hospital;
use App\Models\Hotel;
use App\Models\HomeBanner;
use App\Models\HomeServiceShortcut;
use App\Models\JobPost;
use App\Models\LaunchService;
use App\Models\MarketplaceItem;
use App\Models\MedicineCart;
use App\Models\MedicineItem;
use App\Models\MedicineOrder;
use App\Models\Message;
use App\Models\News;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Report;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\Rider;
use App\Models\RiderOrderRequest;
use App\Models\RiderSupportTicket;
use App\Models\RiderWalletEntry;
use App\Models\SmsLog;
use App\Models\UpdatePost;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $now = now();
        $today = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();
        $monthStart = $now->copy()->startOfMonth();

        $stats = [
            'users' => User::query()->count(),
            'new_users_today' => User::query()->where('created_at', '>=', $today)->count(),
            'new_users_week' => User::query()->where('created_at', '>=', $weekStart)->count(),
            'new_users_month' => User::query()->where('created_at', '>=', $monthStart)->count(),
            'workers' => Worker::query()->count(),
            'businesses' => Business::query()->count(),
            'marketplace_items' => MarketplaceItem::query()->count(),
            'jobs' => JobPost::query()->count(),
            'doctors' => Doctor::query()->count(),
            'hospitals' => Hospital::query()->count(),
            'hotels' => Hotel::query()->count(),
            'restaurants' => Restaurant::query()->count(),
            'properties' => Property::query()->count(),
            'education' => EducationInstitute::query()->count(),
            'launches' => LaunchService::query()->count(),
            'blood_donors' => BloodDonor::query()->count(),
            'couriers' => CourierOffice::query()->count(),
            'car_rentals' => CarRental::query()->count(),
            'electricity_offices' => ElectricityOffice::query()->count(),
            'emergency_contacts' => EmergencyContact::query()->count(),
            'news' => News::query()->count(),
            'notices' => Notice::query()->count(),
            'updates' => UpdatePost::query()->count(),
            'home_banners' => HomeBanner::query()->count(),
            'home_banners_active' => HomeBanner::query()->where('is_active', true)->count(),
            'food_items' => FoodItem::query()->count(),
            'food_items_available' => FoodItem::query()->where('is_available', true)->count(),
            'food_categories' => FoodCategory::query()->count(),
            'food_categories_active' => FoodCategory::query()->where('is_active', true)->count(),
            'food_carts' => FoodCart::query()->count(),
            'food_coupons' => FoodCoupon::query()->count(),
            'food_coupons_active' => FoodCoupon::query()->where('is_active', true)->count(),
            'food_reviews' => FoodReview::query()->count(),
            'food_banners' => FoodBanner::query()->count(),
            'food_banners_active' => FoodBanner::query()->where('is_active', true)->count(),
            'food_orders' => FoodOrder::query()->count(),
            'food_orders_today' => FoodOrder::query()->where('created_at', '>=', $today)->count(),
            'food_orders_week' => FoodOrder::query()->where('created_at', '>=', $weekStart)->count(),
            'food_orders_month' => FoodOrder::query()->where('created_at', '>=', $monthStart)->count(),
            'food_orders_pending' => FoodOrder::query()->whereIn('status', ['pending', 'accepted', 'preparing'])->count(),
            'food_orders_delivered' => FoodOrder::query()->where('status', 'delivered')->count(),
            'food_orders_cancelled' => FoodOrder::query()->whereIn('status', ['cancelled', 'rejected'])->count(),
            'food_revenue_total' => (float) FoodOrder::query()->where('status', 'delivered')->sum('grand_total'),
            'food_revenue_today' => (float) FoodOrder::query()->where('status', 'delivered')->where('created_at', '>=', $today)->sum('grand_total'),
            'food_delivery_fees' => (float) FoodOrder::query()->where('status', 'delivered')->sum('delivery_fee'),
            'food_discount_total' => (float) FoodOrder::query()->sum('discount_amount'),
            'food_unassigned_orders' => FoodOrder::query()->whereNull('rider_id')->whereIn('status', ['accepted', 'preparing', 'picked_up', 'on_the_way'])->count(),
            'food_support_open' => FoodOrderSupportTicket::query()->whereIn('status', ['open', 'reviewing'])->count(),
            'medicine_items' => MedicineItem::query()->count(),
            'medicine_items_available' => MedicineItem::query()->where('is_available', true)->count(),
            'medicine_items_promoted' => MedicineItem::query()->where('is_promoted', true)->count(),
            'medicine_prescription_required' => MedicineItem::query()->where('prescription_required', true)->count(),
            'medicine_carts' => MedicineCart::query()->count(),
            'medicine_orders' => MedicineOrder::query()->where('status', '!=', 'payment_pending')->count(),
            'medicine_orders_today' => MedicineOrder::query()->where('status', '!=', 'payment_pending')->where('created_at', '>=', $today)->count(),
            'medicine_orders_week' => MedicineOrder::query()->where('status', '!=', 'payment_pending')->where('created_at', '>=', $weekStart)->count(),
            'medicine_orders_month' => MedicineOrder::query()->where('status', '!=', 'payment_pending')->where('created_at', '>=', $monthStart)->count(),
            'medicine_orders_pending' => MedicineOrder::query()->whereIn('status', ['pending', 'accepted', 'preparing'])->count(),
            'medicine_orders_delivered' => MedicineOrder::query()->where('status', 'delivered')->count(),
            'medicine_orders_cancelled' => MedicineOrder::query()->whereIn('status', ['cancelled', 'rejected'])->count(),
            'medicine_revenue_total' => (float) MedicineOrder::query()->where('status', 'delivered')->sum('grand_total'),
            'medicine_revenue_today' => (float) MedicineOrder::query()->where('status', 'delivered')->where('created_at', '>=', $today)->sum('grand_total'),
            'medicine_delivery_fees' => (float) MedicineOrder::query()->where('status', 'delivered')->sum('delivery_fee'),
            'medicine_unassigned_orders' => MedicineOrder::query()->whereNull('rider_id')->whereIn('status', ['accepted', 'preparing', 'on_the_way'])->count(),
            'riders' => Rider::query()->count(),
            'riders_active' => Rider::query()->where('account_status', 'active')->count(),
            'riders_online' => Rider::query()->where('availability_status', 'online')->count(),
            'riders_busy' => Rider::query()->where('availability_status', 'busy')->count(),
            'riders_kyc_pending' => Rider::query()->where('kyc_status', 'pending')->count(),
            'riders_suspended' => Rider::query()->whereIn('account_status', ['suspended', 'blocked'])->count(),
            'rider_requests' => RiderOrderRequest::query()->count(),
            'rider_requests_pending' => RiderOrderRequest::query()->where('status', 'pending')->count(),
            'rider_requests_today' => RiderOrderRequest::query()->where('created_at', '>=', $today)->count(),
            'rider_wallet_balance' => (float) Rider::query()->sum('wallet_balance'),
            'rider_pending_payout' => (float) Rider::query()->sum('pending_payout'),
            'rider_cash_in_hand' => (float) Rider::query()->sum('cash_in_hand'),
            'rider_earnings_total' => (float) RiderWalletEntry::query()->where('type', 'earning')->sum('amount'),
            'rider_support_open' => RiderSupportTicket::query()->whereIn('status', ['open', 'reviewing'])->count(),
            'messages_total' => Message::query()->count(),
            'messages_today' => Message::query()->where('created_at', '>=', $today)->count(),
            'notifications_total' => AppNotification::query()->count(),
            'device_tokens' => DeviceToken::query()->count(),
            'device_tokens_active_week' => DeviceToken::query()->where('last_seen_at', '>=', $weekStart)->count(),
            'payments' => Payment::query()->count(),
            'payments_paid' => Payment::query()->where('status', 'success')->count(),
            'payments_pending' => Payment::query()->where('status', 'pending')->count(),
            'payments_total_amount' => (float) Payment::query()->where('status', 'success')->sum('amount'),
            'sms_total' => SmsLog::query()->count(),
            'sms_today' => SmsLog::query()->where('created_at', '>=', $today)->count(),
            'sms_sent' => SmsLog::query()->where('status', 'sent')->count(),
            'sms_failed' => SmsLog::query()->where('status', 'failed')->count(),
            'reports_pending' => Report::query()->where('status', 'pending')->count(),
            'reviews_total' => Review::query()->count(),
            'faqs' => Faq::query()->count(),
            'faqs_active' => Faq::query()->where('is_active', true)->count(),
            'home_shortcuts' => HomeServiceShortcut::query()->count(),
            'home_shortcuts_active' => HomeServiceShortcut::query()->where('is_active', true)->count(),
            'visits_today' => AppVisitLog::query()->where('visited_at', '>=', $today)->count(),
            'visits_week' => AppVisitLog::query()->where('visited_at', '>=', $weekStart)->count(),
            'visits_month' => AppVisitLog::query()->where('visited_at', '>=', $monthStart)->count(),
            'unique_visitors_today' => AppVisitLog::query()
                ->where('visited_at', '>=', $today)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
        ];

        $dailyVisits = collect(range(13, 0))->map(function (int $daysAgo) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('M d'),
                'date' => $date->toDateString(),
                'visits' => AppVisitLog::query()->whereDate('visited_at', $date)->count(),
                'users' => User::query()->whereDate('created_at', $date)->count(),
                'messages' => Message::query()->whereDate('created_at', $date)->count(),
                'orders' => FoodOrder::query()->whereDate('created_at', $date)->count(),
                'medicine_orders' => MedicineOrder::query()->where('status', '!=', 'payment_pending')->whereDate('created_at', $date)->count(),
                'revenue' => (float) FoodOrder::query()->where('status', 'delivered')->whereDate('created_at', $date)->sum('grand_total')
                    + (float) MedicineOrder::query()->where('status', 'delivered')->whereDate('created_at', $date)->sum('grand_total'),
            ];
        })->values();

        $monthlyVisits = collect(range(5, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            return [
                'label' => $date->format('M Y'),
                'visits' => AppVisitLog::query()->whereBetween('visited_at', [$start, $end])->count(),
                'users' => User::query()->whereBetween('created_at', [$start, $end])->count(),
                'orders' => FoodOrder::query()->whereBetween('created_at', [$start, $end])->count(),
                'medicine_orders' => MedicineOrder::query()->where('status', '!=', 'payment_pending')->whereBetween('created_at', [$start, $end])->count(),
                'revenue' => (float) FoodOrder::query()->where('status', 'delivered')->whereBetween('created_at', [$start, $end])->sum('grand_total')
                    + (float) MedicineOrder::query()->where('status', 'delivered')->whereBetween('created_at', [$start, $end])->sum('grand_total'),
            ];
        })->values();

        $serviceTotals = [
            ['label' => 'Users', 'slug' => 'users', 'value' => $stats['users']],
            ['label' => 'Workers', 'slug' => 'workers', 'value' => $stats['workers']],
            ['label' => 'Businesses', 'slug' => 'businesses', 'value' => $stats['businesses']],
            ['label' => 'Marketplace', 'slug' => 'marketplace', 'value' => $stats['marketplace_items']],
            ['label' => 'Jobs', 'slug' => 'jobs', 'value' => $stats['jobs']],
            ['label' => 'Doctors', 'slug' => 'doctors', 'value' => $stats['doctors']],
            ['label' => 'Hospitals', 'slug' => 'hospitals', 'value' => $stats['hospitals']],
            ['label' => 'Food Orders', 'slug' => 'food-orders', 'value' => $stats['food_orders']],
            ['label' => 'Medicine Orders', 'slug' => 'medicine-orders', 'value' => $stats['medicine_orders']],
            ['label' => 'Medicine Items', 'slug' => 'medicine-items', 'value' => $stats['medicine_items']],
            ['label' => 'Riders', 'slug' => 'riders', 'value' => $stats['riders']],
            ['label' => 'Restaurants', 'slug' => 'restaurants', 'value' => $stats['restaurants']],
            ['label' => 'Property', 'slug' => 'property', 'value' => $stats['properties']],
            ['label' => 'Education', 'slug' => 'education', 'value' => $stats['education']],
            ['label' => 'Launch', 'slug' => 'launches', 'value' => $stats['launches']],
        ];

        $statusBreakdowns = [
            'food_orders' => $this->statusCounts(FoodOrder::query(), 'status'),
            'medicine_orders' => $this->statusCounts(MedicineOrder::query()->where('status', '!=', 'payment_pending'), 'status'),
            'riders_by_status' => $this->statusCounts(Rider::query(), 'account_status'),
            'riders_by_availability' => $this->statusCounts(Rider::query(), 'availability_status'),
            'sms' => $this->statusCounts(SmsLog::query(), 'status'),
            'payments' => $this->statusCounts(Payment::query(), 'status'),
            'rider_requests' => $this->statusCounts(RiderOrderRequest::query(), 'status'),
        ];

        $recent = [
            'users' => User::query()
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'name', 'email', 'phone', 'created_at']),
            'reports' => Report::query()
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'reason', 'status', 'target_type', 'target_id', 'created_at']),
            'reviews' => Review::query()
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'type', 'rating', 'comment', 'user_id', 'target_id', 'created_at']),
            'visits' => AppVisitLog::query()
                ->with('user:id,name,email,phone')
                ->orderByDesc('id')
                ->limit(6)
                ->get(['id', 'user_id', 'source', 'path', 'visited_at']),
            'food_orders' => FoodOrder::query()
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'order_no', 'status', 'grand_total', 'payment_status', 'created_at']),
            'medicine_orders' => MedicineOrder::query()
                ->where('status', '!=', 'payment_pending')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'order_no', 'status', 'grand_total', 'payment_status', 'created_at']),
            'riders' => Rider::query()
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'name', 'phone', 'kyc_status', 'account_status', 'availability_status', 'created_at']),
            'sms_logs' => SmsLog::query()
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'phone', 'purpose', 'status', 'http_status', 'created_at', 'sent_at']),
        ];

        return response()->json([
            'stats' => $stats,
            'charts' => [
                'daily_visits' => $dailyVisits,
                'monthly_visits' => $monthlyVisits,
                'service_totals' => $serviceTotals,
                'status_breakdowns' => $statusBreakdowns,
            ],
            'recent' => $recent,
        ]);
    }

    private function statusCounts(Builder $query, string $column): array
    {
        return $query
            ->selectRaw($column . ' as label, count(*) as value')
            ->groupBy($column)
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) ($row->label ?? 'unknown'),
                'value' => (int) $row->value,
            ])
            ->values()
            ->all();
    }
}
