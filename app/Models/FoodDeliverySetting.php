<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliverySetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'charge_mode',
        'municipality_rule_enabled',
        'municipality_fixed_charge',
        'municipality_extra_per_km_charge',
        'municipality_center_lat',
        'municipality_center_lng',
        'municipality_radius_km',
        'municipality_polygon',
        'fixed_charge',
        'base_charge',
        'per_km_charge',
        'minimum_charge',
        'free_delivery_min_order',
        'max_delivery_distance_km',
        'store_lat',
        'store_lng',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'municipality_rule_enabled' => 'boolean',
            'municipality_fixed_charge' => 'decimal:2',
            'municipality_extra_per_km_charge' => 'decimal:2',
            'municipality_center_lat' => 'decimal:7',
            'municipality_center_lng' => 'decimal:7',
            'municipality_radius_km' => 'decimal:2',
            'municipality_polygon' => 'array',
            'fixed_charge' => 'decimal:2',
            'base_charge' => 'decimal:2',
            'per_km_charge' => 'decimal:2',
            'minimum_charge' => 'decimal:2',
            'free_delivery_min_order' => 'decimal:2',
            'max_delivery_distance_km' => 'decimal:2',
            'store_lat' => 'decimal:7',
            'store_lng' => 'decimal:7',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'is_enabled' => true,
                'charge_mode' => 'fixed',
                'municipality_rule_enabled' => true,
                'municipality_fixed_charge' => 50,
                'municipality_extra_per_km_charge' => 15,
                'municipality_center_lat' => 22.686,
                'municipality_center_lng' => 90.644,
                'municipality_radius_km' => 1.66,
                'municipality_polygon' => [
                    ['lat' => 22.7044, 'lng' => 90.6179],
                    ['lat' => 22.7049, 'lng' => 90.6227],
                    ['lat' => 22.6996, 'lng' => 90.6274],
                    ['lat' => 22.7016, 'lng' => 90.6373],
                    ['lat' => 22.6993, 'lng' => 90.6448],
                    ['lat' => 22.6990, 'lng' => 90.6511],
                    ['lat' => 22.7031, 'lng' => 90.6525],
                    ['lat' => 22.7050, 'lng' => 90.6558],
                    ['lat' => 22.6987, 'lng' => 90.6579],
                    ['lat' => 22.6961, 'lng' => 90.6644],
                    ['lat' => 22.6901, 'lng' => 90.6617],
                    ['lat' => 22.6835, 'lng' => 90.6591],
                    ['lat' => 22.6755, 'lng' => 90.6642],
                    ['lat' => 22.6603, 'lng' => 90.6665],
                    ['lat' => 22.6487, 'lng' => 90.6677],
                    ['lat' => 22.6449, 'lng' => 90.6639],
                    ['lat' => 22.6465, 'lng' => 90.6571],
                    ['lat' => 22.6552, 'lng' => 90.6534],
                    ['lat' => 22.6645, 'lng' => 90.6500],
                    ['lat' => 22.6739, 'lng' => 90.6460],
                    ['lat' => 22.6746, 'lng' => 90.6389],
                    ['lat' => 22.6791, 'lng' => 90.6365],
                    ['lat' => 22.6812, 'lng' => 90.6291],
                    ['lat' => 22.6852, 'lng' => 90.6250],
                    ['lat' => 22.6880, 'lng' => 90.6172],
                ],
                'fixed_charge' => 40,
                'base_charge' => 0,
                'per_km_charge' => 15,
                'minimum_charge' => 30,
            ]
        );
    }
}
