<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];
}
