<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Restaurant extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cuisines' => 'array',
        'features' => 'array',
        'delivery_available' => 'boolean',
        'accepts_food_orders' => 'boolean',
        'is_promoted' => 'boolean',
        'promotion_priority' => 'integer',
        'commission_enabled' => 'boolean',
        'cod_enabled' => 'boolean',
        'takeaway_available' => 'boolean',
        'dine_in_available' => 'boolean',
        'service_radius_km' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_fixed_fee' => 'decimal:2',
        'promotion_starts_at' => 'datetime',
        'promotion_ends_at' => 'datetime',
    ];

    public function foodItems()
    {
        return $this->hasMany(FoodItem::class);
    }

    public function foodOrders()
    {
        return $this->hasMany(FoodOrder::class);
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'restaurant_user')
            ->withPivot('access_role')
            ->withTimestamps();
    }

    public function scopeAccessibleByUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $access) use ($userId): void {
            $access->where('user_id', $userId)
                ->orWhereHas('assignedUsers', fn (Builder $users) => $users->where('users.id', $userId));
        });
    }

    public function isAccessibleBy(int $userId): bool
    {
        if ((int) $this->user_id === $userId) {
            return true;
        }

        return $this->assignedUsers()->where('users.id', $userId)->exists();
    }

    public function operatorUserIds()
    {
        $ids = $this->relationLoaded('assignedUsers')
            ? $this->assignedUsers->pluck('id')
            : $this->assignedUsers()->pluck('users.id');
        if ($this->user_id) {
            $ids->push((int) $this->user_id);
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values();
    }

    public function isOpenNow(?CarbonImmutable $now = null): bool
    {
        if (! $this->accepts_food_orders || $this->status !== 'active') {
            return false;
        }

        $openTime = $this->normalizeBusinessTime($this->open_time);
        $closeTime = $this->normalizeBusinessTime($this->close_time);
        if (! $openTime || ! $closeTime) {
            return true;
        }

        $now ??= CarbonImmutable::now('Asia/Dhaka');
        $current = $now->format('H:i:s');
        if ($openTime === $closeTime) {
            return true;
        }

        return $openTime < $closeTime
            ? $current >= $openTime && $current < $closeTime
            : $current >= $openTime || $current < $closeTime;
    }

    public function businessHoursLabel(): ?string
    {
        $openTime = $this->normalizeBusinessTime($this->open_time);
        $closeTime = $this->normalizeBusinessTime($this->close_time);
        if (! $openTime || ! $closeTime) {
            return $this->opening_hours;
        }

        return CarbonImmutable::createFromFormat('H:i:s', $openTime)->format('g:i A')
            .' - '.CarbonImmutable::createFromFormat('H:i:s', $closeTime)->format('g:i A');
    }

    private function normalizeBusinessTime(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $value)->format('H:i:s');
    }
}
