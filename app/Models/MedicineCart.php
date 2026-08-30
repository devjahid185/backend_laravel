<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineCart extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(MedicineCartItem::class);
    }
}
