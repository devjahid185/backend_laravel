<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ElectricityOffice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ElectricityOfficeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $offices = ElectricityOffice::query()
            ->when($request->filled('provider'), fn ($q) => $q->where('provider', $request->input('provider')))
            ->when($request->filled('office_type'), fn ($q) => $q->where('office_type', $request->input('office_type')))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->filled('upazila'), fn ($q) => $q->where('upazila', $request->input('upazila')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = '%'.$request->input('q').'%';
                $sub->where('name', 'like', $term)
                    ->orWhere('address', 'like', $term)
                    ->orWhere('provider', 'like', $term);
            }))
            ->where('status', 'active')
            ->orderBy('name')
            ->paginate((int) min(max((int) request()->query('per_page', 50), 1), 100));

        $offices->setCollection(
            $offices->getCollection()->map(function (ElectricityOffice $office) use ($request) {
                $office->is_owner = $office->user_id && (int) $office->user_id === (int) $request->user()->id;
                return $office;
            })
        );

        return response()->json($offices);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $office = ElectricityOffice::query()->findOrFail($id);
        $office->increment('views');
        $office->is_owner = $office->user_id && (int) $office->user_id === (int) $request->user()->id;

        return response()->json($office);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'provider' => ['nullable', 'string', 'max:80'],
            'office_type' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'phones' => ['nullable', 'array'],
            'phones.*' => ['string', 'max:30'],
            'hotline' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $office = ElectricityOffice::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
                'district' => $validated['district'] ?? null,
            ],
            $validated + ['user_id' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Electricity office saved',
            'office' => $office,
        ]);
    }

    public function myOffices(Request $request): JsonResponse
    {
        $offices = ElectricityOffice::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($offices);
    }
}
