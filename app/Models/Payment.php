<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'provider_order_code', 'transaction_id', 'payment_method',
        'amount', 'status', 'payment_url', 'paid_at', 'raw_response',
    ];

    protected $casts = ['amount' => 'integer', 'paid_at' => 'datetime'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
