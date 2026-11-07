@extends('layouts.app')

@section('title', 'Sản phẩm yêu thích')

@push('styles')
{{-- catalog.css: dùng lại .cat-grid, .cat-pager, .cat-empty, .cat-head...; wishlist.css: phần riêng của trang này --}}
<link href="{{ asset('assets/css/pages/catalog.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/pages/wishlist.css') }}" rel="stylesheet">
@endpush

{{-- Controller (WishlistController@index) truyền vào: $products (Paginator các sản phẩm đang bán trong wishlist) --}}
@section('content')
<div class="pc-container cat">

    <nav class="cat-crumb" aria-label="Đường dẫn">
        <a href="{{ route('home') }}">Trang chủ</a> /
        <span aria-current="page">Yêu thích</span>
    </nav>

    <header class="cat-head">
        <div>
            <h1 class="cat-title">Sản phẩm yêu thích</h1>
            <p class="cat-summary">{{ number_format($products->total(), 0, ',', '.') }} sản phẩm đã lưu</p>
        </div>
    </header>

    <hr class="cat-rule">

    @if ($products->isEmpty())
        <div class="cat-empty">
            <i class="bi bi-heart" aria-hidden="true"></i>
            <h2>Chưa có sản phẩm yêu thích.</h2>
            <p>Bấm vào biểu tượng trái tim trên sản phẩm để lưu lại và xem sau.</p>
            <a href="{{ route('products.index') }}" class="pc-btn pc-btn--primary">Đi tới cửa hàng</a>
        </div>
    @else
        <div class="cat-grid" id="wl-grid" data-prev="{{ $products->previousPageUrl() }}">
            @foreach ($products as $p)
                @php $soldOut = $p->stock < 1; @endphp
                <article class="wl-card" data-id="{{ $p->id }}">
                    <a href="{{ route('products.show', $p->slug) }}" class="pc-photo wl-card__img" tabindex="-1" aria-hidden="true">
                        <img src="{{ $p->image_url }}" alt="" loading="lazy">
                        @if ($p->is_on_sale)
                            <span class="pc-badge-sale">SALE</span>
                        @endif
                    </a>

                    <p class="pc-card__meta">
                        {{ collect([$p->category ? mb_strtoupper($p->category->name) : null, $p->brand])->filter()->implode(' · ') }}
                    </p>

                    <h3 class="pc-card__name">
                        <a href="{{ route('products.show', $p->slug) }}">{{ $p->name }}</a>
                    </h3>

                    <p class="pc-card__price">
                        <strong>{{ $p->final_price_text }}</strong>
                        @if ($p->is_on_sale)
                            <s>{{ $p->price_text }}</s>
                        @endif
                    </p>

                    <div class="wl-card__actions">
                        <button type="button" class="pc-btn pc-btn--primary btn-wl-move"
                                data-id="{{ $p->id }}" data-url="{{ route('wishlist.move-to-cart') }}"
                                @disabled($soldOut)>
                            <i class="bi bi-bag-plus" aria-hidden="true"></i>
                            {{ $soldOut ? 'Hết hàng' : 'Chuyển vào giỏ' }}
                        </button>

                        <button type="button" class="pc-btn pc-btn--outline btn-wl-remove"
                                data-id="{{ $p->id }}" data-url="{{ route('wishlist.toggle') }}"
                                aria-label="Bỏ {{ $p->name }} khỏi yêu thích">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="cat-pager">
            <p class="cat-pager__info">
                {{ $products->firstItem() }}–{{ $products->lastItem() }} / {{ number_format($products->total(), 0, ',', '.') }} sản phẩm
            </p>
            {{ $products->links('front.products.pagination') }}
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    const $grid = $('#wl-grid');
    if (!$grid.length) return;

    // Gỡ thẻ khỏi trang; hết thẻ thì tải lại (hoặc lùi về trang trước) để server hiển thị đúng trạng thái
    function removeCard($card) {
        $card.fadeOut(150, function () {
            $(this).remove();
            if ($grid.find('.wl-card').length === 0) {
                const prev = $grid.data('prev');
                window.location.href = prev ? prev : window.location.pathname;
            }
        });
    }

    // Bỏ yêu thích
    $(document).on('click', '.btn-wl-remove', function () {
        const $btn = $(this).prop('disabled', true);
        ajaxCall('POST', $btn.data('url'), { product_id: $btn.data('id') })
            .done(res => { toast(res.message); removeCard($btn.closest('.wl-card')); })
            .fail(() => $btn.prop('disabled', false));
    });

    // Chuyển vào giỏ (server tự gỡ khỏi wishlist nếu thêm giỏ thành công)
    $(document).on('click', '.btn-wl-move', function () {
        const $btn = $(this).prop('disabled', true);
        ajaxCall('POST', $btn.data('url'), { product_id: $btn.data('id') })
            .done(res => { setCartCount(res.data.count); toast(res.message); removeCard($btn.closest('.wl-card')); })
            .fail(() => $btn.prop('disabled', false));
    });
})();
</script>
@endpush