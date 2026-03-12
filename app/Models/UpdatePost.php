<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdatePost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'cover_url',
        'body',
        'tags',
        'published_at',
        'is_published',
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];
}