<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodReview extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_verified_order' => 'boolean',
        'owner_replied_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ownerReplyUser()
    {
        return $this->belongsTo(User::class, 'owner_reply_user_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class);
    }
}
