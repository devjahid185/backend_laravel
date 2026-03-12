<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationInstitute extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'levels' => 'array',
        'mediums' => 'array',
        'facilities' => 'array',
    ];
}
