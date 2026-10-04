<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'status'];

    public function scopeActive(Builder $q): Builder { return $q->where('status', 'active'); }

    public function products(): HasMany { return $this->hasMany(Product::class); }
}
