<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amenities' => 'array',
        'services' => 'array',
    ];
}
