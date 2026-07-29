<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_super' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
