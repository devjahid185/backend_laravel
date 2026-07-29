<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Doctor;
use App\Models\EducationInstitute;
use App\Models\Hospital;
use App\Models\Hotel;
use App\Models\JobPost;
use App\Models\LaunchService;
use App\Models\MarketplaceItem;
use App\Models\Property;
use App\Models\Report;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $stats = [
            'users' => User::query()->count(),
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
            'reports_pending' => Report::query()->where('status', 'pending')->count(),
            'reviews_total' => Review::query()->count(),
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
        ];

        return response()->json([
            'stats' => $stats,
            'recent' => $recent,
        ]);
    }
}
