<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sections' => 'array',
        'is_available' => 'boolean',
        'is_promoted' => 'boolean',
        'prescription_required' => 'boolean',
        'unit_price' => 'decimal:2',
    ];
}
