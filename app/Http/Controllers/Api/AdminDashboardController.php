<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\AppVisitLog;
use App\Models\BloodDonor;
use App\Models\Business;
use App\Models\CarRental;
use App\Models\CourierOffice;
use App\Models\Doctor;
use App\Models\EducationInstitute;
use App\Models\ElectricityOffice;
use App\Models\EmergencyContact;
use App\Models\FoodItem;
use App\Models\FoodBanner;
use App\Models\FoodOrder;
use App\Models\Hospital;
use App\Models\Hotel;
use App\Models\HomeBanner;
use App\Models\JobPost;
use App\Models\LaunchService;
use App\Models\MarketplaceItem;
use App\Models\Message;
use App\Models\News;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Report;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\UpdatePost;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;

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
            'food_banners' => FoodBanner::query()->count(),
            'food_banners_active' => FoodBanner::query()->where('is_active', true)->count(),
            'food_orders' => FoodOrder::query()->count(),
            'food_orders_pending' => FoodOrder::query()->whereIn('status', ['pending', 'accepted', 'preparing'])->count(),
            'messages_total' => Message::query()->count(),
            'messages_today' => Message::query()->where('created_at', '>=', $today)->count(),
            'notifications_total' => AppNotification::query()->count(),
            'payments' => Payment::query()->count(),
            'reports_pending' => Report::query()->where('status', 'pending')->count(),
            'reviews_total' => Review::query()->count(),
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
            ['label' => 'Restaurants', 'slug' => 'restaurants', 'value' => $stats['restaurants']],
            ['label' => 'Property', 'slug' => 'property', 'value' => $stats['properties']],
            ['label' => 'Education', 'slug' => 'education', 'value' => $stats['education']],
            ['label' => 'Launch', 'slug' => 'launches', 'value' => $stats['launches']],
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
                ->get(['id', 'order_no', 'status', 'grand_total', 'created_at']),
        ];

        return response()->json([
            'stats' => $stats,
            'charts' => [
                'daily_visits' => $dailyVisits,
                'monthly_visits' => $monthlyVisits,
                'service_totals' => $serviceTotals,
            ],
            'recent' => $recent,
        ]);
    }
}
