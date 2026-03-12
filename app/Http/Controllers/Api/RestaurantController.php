<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(RestaurantCategory::query()->orderBy('name')->get());
    }

    public function index(Request $request): JsonResponse
    {
        $restaurants = Restaurant::query()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('upazila'), fn ($q) => $q->where('upazila', $request->input('upazila')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('min_price', '>=', (int) $request->input('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('max_price', '<=', (int) $request->input('max_price')))
            ->when($request->filled('delivery_available'), fn ($q) => $q->where('delivery_available', (bool) $request->input('delivery_available')))
            ->when($request->filled('takeaway_available'), fn ($q) => $q->where('takeaway_available', (bool) $request->input('takeaway_available')))
            ->when($request->filled('dine_in_available'), fn ($q) => $q->where('dine_in_available', (bool) $request->input('dine_in_available')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('name', 'like', $term)
                    ->orWhere('address', 'like', $term)
                    ->orWhere('district', 'like', $term);
            }))
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate(20);

        $map = MediaLookup::primaryUrlMap('restaurant', array_column($restaurants->items(), 'id'));
        $restaurants->setCollection(
            $restaurants->getCollection()->map(function (Restaurant $restaurant) use ($map, $request) {
                $restaurant->image_url = $map[$restaurant->id] ?? null;
                $restaurant->is_owner = (int) $restaurant->user_id === (int) $request->user()->id;
                $restaurant->category_name = $restaurant->category_id
                    ? RestaurantCategory::query()->find($restaurant->category_id)?->name
                    : null;

                return $restaurant;
            })
        );

        return response()->json($restaurants);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $restaurant = Restaurant::query()->findOrFail($id);
        $restaurant->increment('views');
        $restaurant->image_url = MediaLookup::primaryUrlMap('restaurant', [$restaurant->id])[$restaurant->id] ?? null;
        $restaurant->is_owner = (int) $restaurant->user_id === (int) $request->user()->id;
        $restaurant->category_name = $restaurant->category_id
            ? RestaurantCategory::query()->find($restaurant->category_id)?->name
            : null;

        return response()->json($restaurant);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:restaurant_categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'cuisines' => ['nullable', 'array'],
            'cuisines.*' => ['string', 'max:120'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:120'],
            'delivery_available' => ['nullable', 'boolean'],
            'takeaway_available' => ['nullable', 'boolean'],
            'dine_in_available' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $restaurant = Restaurant::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Restaurant saved',
            'restaurant' => $restaurant,
        ]);
    }

    public function myRestaurants(Request $request): JsonResponse
    {
        $restaurants = Restaurant::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($restaurants);
    }
}
