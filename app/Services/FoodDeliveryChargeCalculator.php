<?php

namespace App\Services;

use App\Models\FoodDeliverySetting;
use App\Models\Restaurant;

class FoodDeliveryChargeCalculator
{
    public function __construct(private readonly GoogleRoutesDistanceService $googleRoutes)
    {
    }

    public function calculate(?Restaurant $restaurant, ?float $lat, ?float $lng, float $itemsTotal): array
    {
        if (! $restaurant?->delivery_available) {
            return ['fee' => 0.0, 'distance_km' => null, 'mode' => 'disabled', 'label' => 'Delivery disabled'];
        }

        $settings = FoodDeliverySetting::current();
        if (! $settings->is_enabled) {
            return ['fee' => 0.0, 'distance_km' => null, 'mode' => 'disabled', 'label' => 'Delivery disabled'];
        }

        $distanceKm = $this->routeDistance($restaurant, $lat, $lng, $settings);
        if ($settings->free_delivery_min_order !== null && $itemsTotal >= (float) $settings->free_delivery_min_order) {
            return [
                'fee' => 0.0,
                'distance_km' => $distanceKm === null ? null : round($distanceKm, 2),
                'mode' => 'free_delivery',
                'label' => 'Free delivery',
            ];
        }

        if ($distanceKm !== null && $settings->max_delivery_distance_km !== null && $distanceKm > (float) $settings->max_delivery_distance_km) {
            abort(422, 'Delivery location is outside the service area.');
        }

        if ($settings->municipality_rule_enabled && $lat !== null && $lng !== null) {
            return $this->municipalityCharge($settings, $lat, $lng, $distanceKm);
        }

        if ($settings->charge_mode === 'fixed') {
            return [
                'fee' => round((float) $settings->fixed_charge, 2),
                'distance_km' => $distanceKm === null ? null : round($distanceKm, 2),
                'mode' => 'fixed',
                'label' => 'Fixed delivery charge',
            ];
        }

        $fee = (float) $settings->base_charge + (($distanceKm ?? 0) * (float) $settings->per_km_charge);
        $fee = max((float) $settings->minimum_charge, $fee);

        return [
            'fee' => round($fee, 2),
            'distance_km' => $distanceKm === null ? null : round($distanceKm, 2),
            'mode' => 'per_km',
            'label' => 'Distance based delivery charge',
        ];
    }

    public function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function routeDistance(?Restaurant $restaurant, ?float $lat, ?float $lng, FoodDeliverySetting $settings): ?float
    {
        $originLat = $restaurant?->lat !== null ? (float) $restaurant->lat : ($settings->store_lat !== null ? (float) $settings->store_lat : null);
        $originLng = $restaurant?->lng !== null ? (float) $restaurant->lng : ($settings->store_lng !== null ? (float) $settings->store_lng : null);

        if ($originLat === null || $originLng === null || $lat === null || $lng === null) {
            return null;
        }

        return $this->googleRoutes->distanceKm($originLat, $originLng, $lat, $lng)
            ?? $this->distanceKm($originLat, $originLng, $lat, $lng);
    }

    private function municipalityCharge(FoodDeliverySetting $settings, float $lat, float $lng, ?float $distanceKm): array
    {
        $fixed = (float) ($settings->municipality_fixed_charge ?? 50);
        $extraRate = (float) ($settings->municipality_extra_per_km_charge ?? 0);
        if ($this->isInsideMunicipality($settings, $lat, $lng)) {
            return [
                'fee' => round($fixed, 2),
                'distance_km' => $distanceKm === null ? null : round($distanceKm, 2),
                'mode' => 'municipality_fixed',
                'label' => 'Bhola Sadar Pourashava fixed charge',
            ];
        }

        $outsideKm = $this->outsideDistanceKm($settings, $lat, $lng, $distanceKm);
        $thresholdKm = (float) ($settings->municipality_long_distance_threshold_km ?? 8);
        $longDistanceRate = (float) ($settings->municipality_long_distance_per_km_charge ?? 15);
        $chargeableKm = max(0, (float) ($distanceKm ?? $outsideKm));

        if ((bool) ($settings->municipality_long_distance_enabled ?? false) && $chargeableKm > $thresholdKm) {
            return [
                'fee' => round($chargeableKm * $longDistanceRate, 2),
                'distance_km' => $distanceKm === null ? null : round($distanceKm, 2),
                'mode' => 'municipality_outside_long_distance',
                'label' => 'Outside pourashava long distance per KM',
            ];
        }

        return [
            'fee' => round($fixed + ($outsideKm * $extraRate), 2),
            'distance_km' => $distanceKm === null ? null : round($distanceKm, 2),
            'mode' => 'municipality_outside_per_km',
            'label' => 'Pourashava outside extra per KM',
        ];
    }

    private function isInsideMunicipality(FoodDeliverySetting $settings, float $lat, float $lng): bool
    {
        $polygon = $settings->municipality_polygon ?? [];
        if (is_array($polygon) && count($polygon) >= 3) {
            $inside = false;
            $count = count($polygon);
            for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
                $latI = (float) ($polygon[$i]['lat'] ?? 0);
                $lngI = (float) ($polygon[$i]['lng'] ?? 0);
                $latJ = (float) ($polygon[$j]['lat'] ?? 0);
                $lngJ = (float) ($polygon[$j]['lng'] ?? 0);
                $intersects = (($lngI > $lng) !== ($lngJ > $lng))
                    && ($lat < ($latJ - $latI) * ($lng - $lngI) / (($lngJ - $lngI) ?: 0.0000001) + $latI);
                if ($intersects) {
                    $inside = ! $inside;
                }
            }

            return $inside;
        }

        if ($settings->municipality_center_lat !== null && $settings->municipality_center_lng !== null && $settings->municipality_radius_km !== null) {
            return $this->distanceKm((float) $settings->municipality_center_lat, (float) $settings->municipality_center_lng, $lat, $lng)
                <= (float) $settings->municipality_radius_km;
        }

        return false;
    }

    private function outsideDistanceKm(FoodDeliverySetting $settings, float $lat, float $lng, ?float $fallbackDistanceKm): float
    {
        if ($settings->municipality_center_lat !== null && $settings->municipality_center_lng !== null && $settings->municipality_radius_km !== null) {
            $fromCenter = $this->distanceKm((float) $settings->municipality_center_lat, (float) $settings->municipality_center_lng, $lat, $lng);

            return max(0, $fromCenter - (float) $settings->municipality_radius_km);
        }

        return max(0, (float) ($fallbackDistanceKm ?? 0));
    }
}
