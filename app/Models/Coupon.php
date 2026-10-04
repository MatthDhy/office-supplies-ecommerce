<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'minimum_order_amount', 'max_discount_amount',
        'max_uses', 'per_user_limit', 'used_count', 'starts_at', 'expires_at', 'status',
    ];

    protected $casts = [
        'value' => 'integer', 'minimum_order_amount' => 'integer', 'max_discount_amount' => 'integer',
        'starts_at' => 'datetime', 'expires_at' => 'datetime',
    ];

    public function orderCoupons(): HasMany { return $this->hasMany(OrderCoupon::class); }
}
