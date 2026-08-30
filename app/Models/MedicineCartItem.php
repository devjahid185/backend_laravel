<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineCartItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function medicineItem()
    {
        return $this->belongsTo(MedicineItem::class);
    }
}
