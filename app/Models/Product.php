<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'brand',
        'price', 'sale_price', 'stock', 'image', 'is_featured', 'status',
    ];

    // decimal(12,0) mặc định ra string -> ép về int để tính toán
    protected $casts = [
        'price'       => 'integer',
        'sale_price'  => 'integer',
        'stock'       => 'integer',
        'is_featured' => 'boolean',
    ];

    /* ---------- Scopes ---------- */
    public function scopeActive(Builder $q): Builder   { return $q->where('status', 'active'); }
    public function scopeFeatured(Builder $q): Builder { return $q->where('is_featured', true); }
    public function scopeOnSale(Builder $q): Builder
    {
        return $q->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price');
    }

    /* ---------- Accessors ---------- */
    /** Giá thực tế khách phải trả (có sale thì lấy sale). LUÔN dùng giá này khi tính tiền. */
    public function getFinalPriceAttribute(): int
    {
        return ($this->sale_price !== null && $this->sale_price < $this->price)
            ? $this->sale_price
            : $this->price;
    }

    public function getIsOnSaleAttribute(): bool { return $this->final_price < $this->price; }

    public function getFinalPriceTextAttribute(): string { return number_format($this->final_price, 0, ',', '.') . '₫'; }
    public function getPriceTextAttribute(): string      { return number_format($this->price, 0, ',', '.') . '₫'; }

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return 'https://placehold.co/400x400?text=' . urlencode($this->name);
        }
        return str_starts_with($this->image, 'http') ? $this->image : asset('storage/' . $this->image);
    }

    /* ---------- Relations ---------- */
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function reviews(): HasMany    { return $this->hasMany(Review::class); }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
}
