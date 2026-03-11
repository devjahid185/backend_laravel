<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodDonor;
use App\Support\MediaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BloodDonorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $donors = BloodDonor::query()
            ->when($request->filled('blood_group'), fn ($q) => $q->where('blood_group', $request->input('blood_group')))
            ->when($request->filled('location'), fn ($q) => $q->where('location', 'like', '%'.$request->input('location').'%'))
            ->when($request->filled('district'), fn ($q) => $q->where('district', $request->input('district')))
            ->when($request->input('available') !== 'all', fn ($q) => $q->where('available', true))
            ->orderByDesc('id')
            ->paginate(20);

        $map = MediaLookup::primaryUrlMap('blood_donor', array_column($donors->items(), 'id'));
        $donors->setCollection(
            $donors->getCollection()->map(function (BloodDonor $donor) use ($map) {
                $donor->image_url = $map[$donor->id] ?? null;

                return $donor;
            })
        );

        $donors->setCollection(
            $donors->getCollection()->map(function (BloodDonor $donor) use ($request) {
                $donor->is_owner = $request->user()->id === $donor->user_id;

                return $donor;
            })
        );

        return response()->json($donors);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $donor = BloodDonor::query()->findOrFail($id);
        $donor->is_owner = $request->user()->id === $donor->user_id;

        $map = MediaLookup::primaryUrlMap('blood_donor', [$donor->id]);
        $donor->image_url = $map[$donor->id] ?? null;

        return response()->json($donor);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
            'blood_group' => ['required', 'string', 'max:5'],
            'phone' => ['nullable', 'string', 'max:20'],
            'district' => ['nullable', 'string', 'max:80'],
            'upazila' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:16'],
            'age' => ['nullable', 'integer', 'min:16', 'max:80'],
            'weight' => ['nullable', 'integer', 'min:35', 'max:200'],
            'donation_count' => ['nullable', 'integer', 'min:0', 'max:200'],
            'last_donation' => ['nullable', 'date'],
            'available' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $donor = BloodDonor::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            array_merge($validated, [
                'name' => $validated['name'] ?? $request->user()->name ?? null,
            ])
        );

        return response()->json([
            'message' => 'Blood donor profile saved',
            'donor' => $donor,
        ]);
    }
}
