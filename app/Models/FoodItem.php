<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'size_options' => 'array',
        'spice_options' => 'array',
        'add_ons' => 'array',
        'is_available' => 'boolean',
        'is_popular' => 'boolean',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(FoodCategory::class, 'food_category_id');
    }

    public function setSizeOptionsAttribute($value): void
    {
        $this->attributes['size_options'] = json_encode(collect($value ?? [])
            ->map(function ($option) {
                if (is_array($option)) {
                    $name = trim((string) ($option['name'] ?? $option['label'] ?? ''));
                    $price = $option['price'] ?? null;
                } else {
                    $name = trim((string) $option);
                    $price = null;
                }

                return $name === '' ? null : [
                    'name' => $name,
                    'price' => $price === null || $price === '' ? null : round((float) $price, 2),
                ];
            })
            ->filter()
            ->values()
            ->all());
    }

    public function setSpiceOptionsAttribute($value): void
    {
        $this->attributes['spice_options'] = json_encode(collect($value ?? [])
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->values()
            ->all());
    }
}
