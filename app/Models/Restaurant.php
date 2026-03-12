<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cuisines' => 'array',
        'features' => 'array',
        'delivery_available' => 'boolean',
        'takeaway_available' => 'boolean',
        'dine_in_available' => 'boolean',
    ];
}
