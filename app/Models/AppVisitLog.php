<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVisitLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
