<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $properties = Property::query()
            ->when($request->filled('purpose'), fn ($q) => $q->where('purpose', $request->input('purpose')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('location'), fn ($q) => $q->where('location', 'like', '%'.$request->input('location').'%'))
            ->when($request->filled('price_min'), fn ($q) => $q->where('price', '>=', (float) $request->input('price_min')))
            ->when($request->filled('price_max'), fn ($q) => $q->where('price', '<=', (float) $request->input('price_max')))
            ->when($request->filled('bedrooms'), fn ($q) => $q->where('bedrooms', '>=', (int) $request->input('bedrooms')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('title', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhere('address', 'like', $term);
            }))
            ->when($request->input('status') !== 'all', fn ($q) => $q->where('status', 'open'))
            ->orderByDesc('id')
            ->paginate(20);
        $map = MediaLookup::primaryUrlMap('property', array_column($properties->items(), 'id'));

        $properties->setCollection(
            $properties->getCollection()->map(function (Property $property) use ($map, $request) {
                $property->image_url = $map[$property->id] ?? null;
                $property->is_owner = (int) $property->user_id === (int) $request->user()->id;
                $property->category_name = $property->category_id
                    ? PropertyCategory::query()->find($property->category_id)?->name
                    : null;

                return $property;
            })
        );

        return response()->json($properties);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $property = Property::query()->findOrFail($id);
        $property->increment('views');
        $property->image_url = MediaLookup::primaryUrlMap('property', [$property->id])[$property->id] ?? null;
        $property->is_owner = (int) $property->user_id === (int) $request->user()->id;
        $property->category_name = $property->category_id
            ? PropertyCategory::query()->find($property->category_id)?->name
            : null;

        return response()->json($property);
    }

    public function categories(): JsonResponse
    {
        return response()->json(PropertyCategory::query()->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:property_categories,id'],
            'purpose' => ['nullable', 'in:rent,sell'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_per_sqft' => ['nullable', 'numeric', 'min:0'],
            'negotiable' => ['nullable', 'boolean'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'integer', 'min:0', 'max:200'],
            'total_floors' => ['nullable', 'integer', 'min:0', 'max:200'],
            'furnished' => ['nullable', 'boolean'],
            'parking' => ['nullable', 'boolean'],
            'facing' => ['nullable', 'string', 'max:50'],
            'year_built' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'location' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'location_type' => ['nullable', 'string', 'max:80'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string'],
            'contact' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_website' => ['nullable', 'string', 'max:255'],
            'amenities' => ['nullable', 'array'],
        ]);

        $property = Property::query()->create($validated + ['user_id' => $request->user()->id]);

        return response()->json([
            'message' => 'Property added successfully',
            'property' => $property,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $property = Property::query()->findOrFail($id);
        if ((int) $property->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:property_categories,id'],
            'purpose' => ['nullable', 'in:rent,sell'],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_per_sqft' => ['nullable', 'numeric', 'min:0'],
            'negotiable' => ['nullable', 'boolean'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'integer', 'min:0', 'max:200'],
            'total_floors' => ['nullable', 'integer', 'min:0', 'max:200'],
            'furnished' => ['nullable', 'boolean'],
            'parking' => ['nullable', 'boolean'],
            'facing' => ['nullable', 'string', 'max:50'],
            'year_built' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'location' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'location_type' => ['nullable', 'string', 'max:80'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string'],
            'contact' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_website' => ['nullable', 'string', 'max:255'],
            'amenities' => ['nullable', 'array'],
            'status' => ['nullable', 'in:open,closed'],
        ]);

        $property->update($validated);

        return response()->json([
            'message' => 'Property updated',
            'property' => $property->fresh(),
        ]);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $property = Property::query()->findOrFail($id);
        if ((int) $property->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $property->update(['status' => 'closed']);

        return response()->json(['message' => 'Property closed']);
    }

    public function myPosts(Request $request): JsonResponse
    {
        $properties = Property::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(20);

        $map = MediaLookup::primaryUrlMap('property', array_column($properties->items(), 'id'));
        $properties->setCollection(
            $properties->getCollection()->map(function (Property $property) use ($map) {
                $property->image_url = $map[$property->id] ?? null;

                return $property;
            })
        );

        return response()->json($properties);
    }
}
