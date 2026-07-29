<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Support\MediaLookup;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = BusinessCategory::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function index(Request $request): JsonResponse
    {
        $businesses = DB::table('businesses')
            ->join('business_categories', 'business_categories.id', '=', 'businesses.category_id')
            ->select('businesses.*', 'business_categories.name as category_name')
            ->when($request->filled('category_id'), fn ($q) => $q->where('businesses.category_id', $request->integer('category_id')))
            ->orderByDesc('businesses.id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $logoMap = MediaLookup::primaryUrlMap('business', array_column($businesses->items(), 'id'));

        $businesses->setCollection(
            $businesses->getCollection()->map(function ($business) use ($logoMap) {
                $business->logo_url = $logoMap[$business->id] ?? MediaUrl::toUrl($business->logo);

                return $business;
            })
        );

        return response()->json($businesses);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $business = DB::table('businesses')
            ->join('business_categories', 'business_categories.id', '=', 'businesses.category_id')
            ->select('businesses.*', 'business_categories.name as category_name')
            ->where('businesses.id', $id)
            ->first();

        if (! $business) {
            return response()->json(['message' => 'Business not found'], 404);
        }

        $business->logo_url = MediaLookup::primaryUrlMap('business', [$business->id])[$business->id] ?? MediaUrl::toUrl($business->logo);
        $business->is_owner = $business->user_id === $request->user()->id;

        return response()->json($business);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:business_categories,id'],
            'logo' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook_page' => ['nullable', 'string', 'max:255'],
        ]);

        $business = Business::query()->create($validated + [
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Business added successfully',
            'business' => $business,
        ], 201);
    }
}
