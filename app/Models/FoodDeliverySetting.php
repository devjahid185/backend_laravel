<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDeliverySetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'charge_mode',
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
                'fixed_charge' => 40,
                'base_charge' => 0,
                'per_km_charge' => 15,
                'minimum_charge' => 30,
            ]
        );
    }
}
