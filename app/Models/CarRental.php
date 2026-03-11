<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarRental extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'driver_available' => 'boolean',
        'ac_available' => 'boolean',
        'gps_available' => 'boolean',
        'delivery_available' => 'boolean',
    ];
}
