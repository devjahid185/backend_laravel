<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminModule extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'group_name',
        'route',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
