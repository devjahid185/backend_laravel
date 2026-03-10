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
            ->where('available', true)
            ->orderByDesc('id')
            ->paginate(20);

        $map = MediaLookup::primaryUrlMap('blood_donor', array_column($donors->items(), 'id'));
        $donors->setCollection(
            $donors->getCollection()->map(function (BloodDonor $donor) use ($map) {
                $donor->image_url = $map[$donor->id] ?? null;

                return $donor;
            })
        );

        return response()->json($donors);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'blood_group' => ['required', 'string', 'max:5'],
            'last_donation' => ['nullable', 'date'],
            'available' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $donor = BloodDonor::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json([
            'message' => 'Blood donor profile saved',
            'donor' => $donor,
        ]);
    }
}
