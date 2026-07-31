<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodDeliverySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFoodDeliverySettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => $this->serialize(FoodDeliverySetting::current()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'charge_mode' => ['required', 'in:fixed,per_km'],
            'fixed_charge' => ['required', 'numeric', 'min:0', 'max:10000'],
            'base_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'per_km_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'minimum_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'free_delivery_min_order' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'max_delivery_distance_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'store_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'store_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = FoodDeliverySetting::current();
        $settings->update($validated);

        return response()->json([
            'message' => 'Food delivery settings updated successfully.',
            'settings' => $this->serialize($settings->fresh()),
        ]);
    }

    private function serialize(FoodDeliverySetting $settings): array
    {
        return [
            'is_enabled' => (bool) $settings->is_enabled,
            'charge_mode' => $settings->charge_mode,
            'fixed_charge' => (float) $settings->fixed_charge,
            'base_charge' => (float) $settings->base_charge,
            'per_km_charge' => (float) $settings->per_km_charge,
            'minimum_charge' => (float) $settings->minimum_charge,
            'free_delivery_min_order' => $settings->free_delivery_min_order === null ? null : (float) $settings->free_delivery_min_order,
            'max_delivery_distance_km' => $settings->max_delivery_distance_km === null ? null : (float) $settings->max_delivery_distance_km,
            'store_lat' => $settings->store_lat === null ? null : (float) $settings->store_lat,
            'store_lng' => $settings->store_lng === null ? null : (float) $settings->store_lng,
            'note' => $settings->note,
        ];
    }
}
