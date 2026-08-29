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
            'municipality_rule_enabled' => ['nullable', 'boolean'],
            'municipality_fixed_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'municipality_extra_per_km_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'municipality_center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'municipality_center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'municipality_radius_km' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'municipality_polygon' => ['nullable', 'array'],
            'municipality_polygon.*.lat' => ['required_with:municipality_polygon', 'numeric', 'between:-90,90'],
            'municipality_polygon.*.lng' => ['required_with:municipality_polygon', 'numeric', 'between:-180,180'],
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
            'municipality_rule_enabled' => (bool) $settings->municipality_rule_enabled,
            'municipality_fixed_charge' => (float) ($settings->municipality_fixed_charge ?? 50),
            'municipality_extra_per_km_charge' => (float) ($settings->municipality_extra_per_km_charge ?? 15),
            'municipality_center_lat' => $settings->municipality_center_lat === null ? null : (float) $settings->municipality_center_lat,
            'municipality_center_lng' => $settings->municipality_center_lng === null ? null : (float) $settings->municipality_center_lng,
            'municipality_radius_km' => $settings->municipality_radius_km === null ? null : (float) $settings->municipality_radius_km,
            'municipality_polygon' => $settings->municipality_polygon ?? [],
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
