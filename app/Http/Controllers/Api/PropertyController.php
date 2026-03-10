<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(): JsonResponse
    {
        $properties = Property::query()->latest()->paginate(20);
        $map = MediaLookup::primaryUrlMap('property', array_column($properties->items(), 'id'));

        $properties->setCollection(
            $properties->getCollection()->map(function (Property $property) use ($map) {
                $property->image_url = $map[$property->id] ?? null;

                return $property;
            })
        );

        return response()->json($properties);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contact' => ['nullable', 'string', 'max:255'],
        ]);

        $property = Property::query()->create($validated + ['user_id' => $request->user()->id]);

        return response()->json([
            'message' => 'Property added successfully',
            'property' => $property,
        ], 201);
    }
}
