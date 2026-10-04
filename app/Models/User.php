<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // KHÔNG đưa 'role' và 'status' vào fillable -> user không thể tự nâng quyền qua form.
    protected $fillable = ['full_name', 'email', 'password', 'phone', 'avatar'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed']; // Laravel 11 tự hash khi gán
    }

    public function isAdmin(): bool  { return $this->role === 'admin'; }
    public function isActive(): bool { return $this->status === 'active'; }

    public function addresses(): HasMany { return $this->hasMany(UserAddress::class); }
    public function orders(): HasMany    { return $this->hasMany(Order::class); }
    public function reviews(): HasMany   { return $this->hasMany(Review::class); }

    /** Wishlist: dùng toggle()/attach()/detach() trực tiếp, không cần model Wishlist. */
    public function wishlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withPivot('created_at');
    }
}
