<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = ['user_id', 'receiver_name', 'receiver_phone', 'address', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
