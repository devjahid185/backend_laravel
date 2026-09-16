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
        'permissions',
        'is_active',
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
            'permissions' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessAdminModule(string $module, string $action = 'view'): bool
    {
        if ($this->is_super) {
            return true;
        }

        if (! $this->is_active) {
            return false;
        }

        if (in_array($module, ['dashboard', 'profile'], true) && $action === 'view') {
            return true;
        }

        $permissions = $this->permissions ?: [];
        $allowed = $permissions[$module] ?? [];
        return in_array($action, $allowed, true);
    }
}
