{{-- Dùng: @include('partials.product-card', ['product' => $p]) --}}
<div class="card product-card h-100 border-0 shadow-sm">
    <a href="{{ route('products.show', $product->slug) }}" class="ratio ratio-1x1">
        <img src="{{ $product->image_url }}" class="card-img-top object-fit-cover" alt="{{ $product->name }}" loading="lazy">
    </a>

    @if ($product->is_on_sale)
        <span class="badge bg-danger position-absolute top-0 start-0 m-2">Giảm giá</span>
    @endif

    <div class="card-body d-flex flex-column">
        <small class="text-muted">{{ $product->brand }}</small>
        <a href="{{ route('products.show', $product->slug) }}" class="text-dark text-decoration-none fw-semibold mb-2">
            {{ $product->name }}
        </a>

        <div class="mt-auto">
            <span class="text-danger fw-bold">{{ $product->final_price_text }}</span>
            @if ($product->is_on_sale)
                <small class="text-muted text-decoration-line-through ms-1">{{ $product->price_text }}</small>
            @endif
        </div>

        <div class="d-flex gap-2 mt-3">
            {{-- JS ở public/assets/js/app.js bắt class .btn-add-to-cart --}}
            <button class="btn btn-dark btn-sm flex-grow-1 btn-add-to-cart" data-id="{{ $product->id }}"
                    data-url="{{ route('cart.add') }}" @disabled($product->stock < 1)>
                <i class="bi bi-bag-plus"></i> {{ $product->stock < 1 ? 'Hết hàng' : 'Thêm vào giỏ' }}
            </button>
            {{-- Nút tim: [B] viết handler .btn-wishlist trong app.js (gọi route wishlist.toggle) --}}
            <button class="btn btn-outline-secondary btn-sm btn-wishlist" data-id="{{ $product->id }}"
                    data-url="{{ route('wishlist.toggle') }}"><i class="bi bi-heart"></i></button>
        </div>
    </div>
</div>
