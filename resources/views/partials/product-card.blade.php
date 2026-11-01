{{-- Dùng: @include('partials.product-card', ['product' => $p])
     Giữ nguyên class .btn-add-to-cart / .btn-wishlist và data-id, data-url: app.js dựa vào đó. --}}
@php
    $meta = collect([mb_strtoupper($product->category?->name ?? ''), $product->brand])->filter()->implode(' · ');
    $soldOut = $product->stock < 1;
@endphp
<article class="pc-card">
    <div class="pc-card__media">
        <a href="{{ route('products.show', $product->slug) }}" class="pc-photo pc-card__img" tabindex="-1" aria-hidden="true">
            <img src="{{ $product->image_url }}" alt="" loading="lazy">
        </a>

        @if ($product->is_on_sale)
            <span class="pc-badge-sale">SALE</span>
        @endif

        {{-- [B] handler .btn-wishlist viết trong app.js (đổi bi-heart <-> bi-heart-fill) --}}
        <button type="button" class="pc-card__heart btn-wishlist" data-id="{{ $product->id }}"
                data-url="{{ route('wishlist.toggle') }}" aria-label="Lưu {{ $product->name }} vào yêu thích">
            <i class="bi bi-heart" aria-hidden="true"></i>
        </button>
    </div>

    @if ($meta !== '')
        <p class="pc-card__meta">{{ $meta }}</p>
    @endif

    <h3 class="pc-card__name">
        <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
    </h3>

    <p class="pc-card__price">
        <strong>{{ $product->final_price_text }}</strong>
        @if ($product->is_on_sale)
            <s>{{ $product->price_text }}</s>
        @endif
    </p>

    <button type="button" class="pc-card__add btn-add-to-cart" data-id="{{ $product->id }}"
            data-url="{{ route('cart.add') }}" @disabled($soldOut)>
        <span>{{ $soldOut ? 'Hết hàng' : 'Thêm nhanh' }}</span>
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
    </button>
</article>