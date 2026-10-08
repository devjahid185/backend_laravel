<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodMenuImport extends Model
{
    protected $guarded = [];

    protected $casts = [
        'source_images' => 'array',
        'extracted_items' => 'array',
        'created_food_item_ids' => 'array',
        'extracted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
