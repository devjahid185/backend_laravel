<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'services' => 'array',
        'facilities' => 'array',
        'icu_available' => 'boolean',
        'emergency_available' => 'boolean',
        'ambulance_available' => 'boolean',
    ];
}
