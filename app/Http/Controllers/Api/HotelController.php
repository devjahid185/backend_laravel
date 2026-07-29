<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelCategory;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(HotelCategory::query()->orderBy('name')->get());
    }

    public function index(Request $request): JsonResponse
    {
        $hotels = Hotel::query()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('upazila'), fn ($q) => $q->where('upazila', $request->input('upazila')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('min_price', '>=', (int) $request->input('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('max_price', '<=', (int) $request->input('max_price')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('name', 'like', $term)
                    ->orWhere('address', 'like', $term)
                    ->orWhere('district', 'like', $term);
            }))
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $map = MediaLookup::primaryUrlMap('hotel', array_column($hotels->items(), 'id'));
        $hotels->setCollection(
            $hotels->getCollection()->map(function (Hotel $hotel) use ($map, $request) {
                $hotel->image_url = $map[$hotel->id] ?? null;
                $hotel->is_owner = (int) $hotel->user_id === (int) $request->user()->id;
                $hotel->category_name = $hotel->category_id
                    ? HotelCategory::query()->find($hotel->category_id)?->name
                    : null;

                return $hotel;
            })
        );

        return response()->json($hotels);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::query()->findOrFail($id);
        $hotel->increment('views');
        $hotel->image_url = MediaLookup::primaryUrlMap('hotel', [$hotel->id])[$hotel->id] ?? null;
        $hotel->is_owner = (int) $hotel->user_id === (int) $request->user()->id;
        $hotel->category_name = $hotel->category_id
            ? HotelCategory::query()->find($hotel->category_id)?->name
            : null;

        return response()->json($hotel);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:hotel_categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['nullable', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'check_in' => ['nullable', 'string', 'max:40'],
            'check_out' => ['nullable', 'string', 'max:40'],
            'rooms_total' => ['nullable', 'integer', 'min:0'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:120'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'max:120'],
            'description' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $hotel = Hotel::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Hotel saved',
            'hotel' => $hotel,
        ]);
    }

    public function myHotels(Request $request): JsonResponse
    {
        $hotels = Hotel::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($hotels);
    }
}
