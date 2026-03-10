<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerCategory extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function workers()
    {
        return $this->hasMany(Worker::class, 'category_id');
    }
}

