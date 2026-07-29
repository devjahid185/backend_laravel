<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarCategory;
use App\Models\CarRental;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarRentalController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(CarCategory::query()->orderBy('name')->get());
    }

    public function index(Request $request): JsonResponse
    {
        $rentals = CarRental::query()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('transmission'), fn ($q) => $q->where('transmission', $request->input('transmission')))
            ->when($request->filled('fuel_type'), fn ($q) => $q->where('fuel_type', $request->input('fuel_type')))
            ->when($request->filled('seats'), fn ($q) => $q->where('seats', (int) $request->input('seats')))
            ->when($request->filled('driver_available'), fn ($q) => $q->where('driver_available', (bool) $request->input('driver_available')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('title', 'like', $term)
                    ->orWhere('brand', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhere('address', 'like', $term);
            }))
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $map = MediaLookup::primaryUrlMap('car_rental', array_column($rentals->items(), 'id'));
        $rentals->setCollection(
            $rentals->getCollection()->map(function (CarRental $rental) use ($map, $request) {
                $rental->image_url = $map[$rental->id] ?? null;
                $rental->is_owner = (int) $rental->user_id === (int) $request->user()->id;
                $rental->category_name = $rental->category_id
                    ? CarCategory::query()->find($rental->category_id)?->name
                    : null;

                return $rental;
            })
        );

        return response()->json($rentals);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $rental = CarRental::query()->findOrFail($id);
        $rental->increment('views');
        $rental->image_url = MediaLookup::primaryUrlMap('car_rental', [$rental->id])[$rental->id] ?? null;
        $rental->is_owner = (int) $rental->user_id === (int) $request->user()->id;
        $rental->category_name = $rental->category_id
            ? CarCategory::query()->find($rental->category_id)?->name
            : null;

        return response()->json($rental);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:car_categories,id'],
            'title' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'variant' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:2035'],
            'fuel_type' => ['nullable', 'string', 'max:40'],
            'transmission' => ['nullable', 'string', 'max:40'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:60'],
            'doors' => ['nullable', 'integer', 'min:1', 'max:8'],
            'color' => ['nullable', 'string', 'max:40'],
            'reg_no' => ['nullable', 'string', 'max:60'],
            'price_per_day' => ['nullable', 'numeric', 'min:0'],
            'price_per_hour' => ['nullable', 'numeric', 'min:0'],
            'price_per_km' => ['nullable', 'numeric', 'min:0'],
            'driver_available' => ['nullable', 'boolean'],
            'ac_available' => ['nullable', 'boolean'],
            'gps_available' => ['nullable', 'boolean'],
            'delivery_available' => ['nullable', 'boolean'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'pickup_location' => ['nullable', 'string', 'max:255'],
            'dropoff_location' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:120'],
            'description' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date'],
        ]);

        $rental = CarRental::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Car rental saved',
            'rental' => $rental,
        ]);
    }

    public function myRentals(Request $request): JsonResponse
    {
        $rentals = CarRental::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($rentals);
    }
}
