<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\HospitalCategory;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HospitalController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json(HospitalCategory::query()->orderBy('name')->get());
    }

    public function index(Request $request): JsonResponse
    {
        $hospitals = Hospital::query()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->input('category_id')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('emergency_available'), fn ($q) => $q->where('emergency_available', (bool) $request->input('emergency_available')))
            ->when($request->filled('icu_available'), fn ($q) => $q->where('icu_available', (bool) $request->input('icu_available')))
            ->when($request->filled('ambulance_available'), fn ($q) => $q->where('ambulance_available', (bool) $request->input('ambulance_available')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('name', 'like', $term)
                    ->orWhere('address', 'like', $term)
                    ->orWhere('district', 'like', $term);
            }))
            ->where('status', 'active')
            ->orderByDesc('id')
            ->paginate(20);

        $map = MediaLookup::primaryUrlMap('hospital', array_column($hospitals->items(), 'id'));
        $hospitals->setCollection(
            $hospitals->getCollection()->map(function (Hospital $hospital) use ($map, $request) {
                $hospital->image_url = $map[$hospital->id] ?? null;
                $hospital->is_owner = (int) $hospital->user_id === (int) $request->user()->id;
                $hospital->category_name = $hospital->category_id
                    ? HospitalCategory::query()->find($hospital->category_id)?->name
                    : null;

                return $hospital;
            })
        );

        return response()->json($hospitals);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $hospital = Hospital::query()->findOrFail($id);
        $hospital->increment('views');
        $hospital->image_url = MediaLookup::primaryUrlMap('hospital', [$hospital->id])[$hospital->id] ?? null;
        $hospital->is_owner = (int) $hospital->user_id === (int) $request->user()->id;
        $hospital->category_name = $hospital->category_id
            ? HospitalCategory::query()->find($hospital->category_id)?->name
            : null;

        return response()->json($hospital);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:hospital_categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['nullable', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:30'],
            'emergency_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'bed_capacity' => ['nullable', 'integer', 'min:0'],
            'icu_available' => ['nullable', 'boolean'],
            'emergency_available' => ['nullable', 'boolean'],
            'ambulance_available' => ['nullable', 'boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'max:120'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string', 'max:120'],
            'description' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $hospital = Hospital::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Hospital saved',
            'hospital' => $hospital,
        ]);
    }

    public function myHospitals(Request $request): JsonResponse
    {
        $hospitals = Hospital::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($hospitals);
    }
}
