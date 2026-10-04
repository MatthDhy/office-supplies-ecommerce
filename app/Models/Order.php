<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const PROCESSING = 'processing';
    public const SHIPPING = 'shipping';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public const LABELS = [
        'pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'processing' => 'Đang chuẩn bị',
        'shipping' => 'Đang giao', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy',
    ];

    /** Luồng chuyển trạng thái hợp lệ (Admin dùng khi cập nhật đơn). */
    public const TRANSITIONS = [
        'pending'    => ['confirmed', 'cancelled'],
        'confirmed'  => ['processing', 'cancelled'],
        'processing' => ['shipping', 'cancelled'],
        'shipping'   => ['completed', 'cancelled'],
        'completed'  => [],
        'cancelled'  => [],
    ];

    protected $fillable = [
        'user_id', 'order_code', 'subtotal', 'discount_amount', 'shipping_fee', 'total_amount',
        'payment_method', 'payment_status', 'status',
        'shipping_name', 'shipping_phone', 'shipping_address', 'note', 'expires_at', 'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'integer', 'discount_amount' => 'integer',
        'shipping_fee' => 'integer', 'total_amount' => 'integer',
        'expires_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public static function generateCode(): string
    {
        return 'DH' . now()->format('ymd') . strtoupper(Str::random(5));
    }

    public function canChangeTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function getStatusLabelAttribute(): string { return self::LABELS[$this->status] ?? $this->status; }

    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function items(): HasMany      { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany   { return $this->hasMany(Payment::class); }
    public function coupon(): HasOne      { return $this->hasOne(OrderCoupon::class); }
}
