@extends('layouts.app')

@section('title', $product->name)

@push('styles')
<link href="{{ asset('assets/css/pages/product-detail.css') }}" rel="stylesheet">
@endpush

{{--
    Trang chi tiết sản phẩm. Controller (ProductController@show) truyền vào:
      $product, $reviews (đang visible, kèm user), $avgRating (float), $inWishlist (bool),
      $canReview (bool: đã mua + đơn completed + chưa review), $myReview (Review|null), $related (4 SP cùng danh mục)
--}}
@section('content')
@php
    $outOfStock = $product->stock < 1;
    $maxQty     = max(1, min((int) $product->stock, 99));
    $discount   = ($product->is_on_sale && $product->price > 0)
        ? (int) round((1 - $product->final_price / $product->price) * 100)
        : 0;
    $reviewCount = $reviews->count();
@endphp

<div class="pc-container pd">

    {{-- ============ BREADCRUMB ============ --}}
    <nav class="pd-crumb" aria-label="Đường dẫn">
        <a href="{{ route('home') }}">Trang chủ</a> /
        <a href="{{ route('products.index') }}">Cửa hàng</a> /
        @if ($product->category)
            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a> /
        @endif
        <span aria-current="page">{{ $product->name }}</span>
    </nav>

    {{-- ============ THÔNG TIN CHÍNH ============ --}}
    <section class="pd-main">
        <div class="pd-media">
            <div class="pc-photo pd-photo">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                @if ($product->is_on_sale)
                    <span class="pc-badge-sale">SALE{{ $discount > 0 ? ' -' . $discount . '%' : '' }}</span>
                @endif
            </div>
        </div>

        <div class="pd-info">
            <p class="pd-meta">
                {{ collect([$product->category ? mb_strtoupper($product->category->name) : null, $product->brand])->filter()->implode(' · ') }}
            </p>

            <h1 class="pd-title">{{ $product->name }}</h1>

            {{-- Điểm đánh giá trung bình --}}
            <p class="pd-rating">
                @if ($reviewCount > 0)
                    <span class="pd-stars" aria-label="{{ $avgRating }} trên 5 sao">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= round($avgRating) ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                        @endfor
                    </span>
                    <span>{{ number_format($avgRating, 1) }} · {{ $reviewCount }} đánh giá</span>
                @else
                    <span class="pd-muted">Chưa có đánh giá</span>
                @endif
            </p>

            <p class="pd-price">
                <strong>{{ $product->final_price_text }}</strong>
                @if ($product->is_on_sale)
                    <s>{{ $product->price_text }}</s>
                @endif
            </p>

            <p class="pd-stock {{ $outOfStock ? 'is-out' : '' }}">
                @if ($outOfStock)
                    Hết hàng
                @elseif ($product->stock <= 5)
                    Chỉ còn {{ $product->stock }} sản phẩm
                @else
                    Còn hàng
                @endif
            </p>

            {{-- Số lượng + Thêm giỏ + Yêu thích. app.js đã xử lý .btn-add-to-cart (đọc data-qty) và .btn-wishlist --}}
            <div class="pd-actions">
                @unless ($outOfStock)
                    <div class="pd-qty" role="group" aria-label="Số lượng">
                        <button type="button" id="pd-minus" aria-label="Giảm số lượng"><i class="bi bi-dash" aria-hidden="true"></i></button>
                        <input id="pd-qty" type="number" inputmode="numeric" value="1" min="1" max="{{ $maxQty }}" aria-label="Số lượng">
                        <button type="button" id="pd-plus" aria-label="Tăng số lượng"><i class="bi bi-plus" aria-hidden="true"></i></button>
                    </div>
                @endunless

                <button type="button" id="pd-add" class="pc-btn pc-btn--primary pd-add btn-add-to-cart"
                        data-id="{{ $product->id }}" data-url="{{ route('cart.add') }}" data-qty="1"
                        @disabled($outOfStock)>
                    <i class="bi bi-bag-plus" aria-hidden="true"></i>
                    {{ $outOfStock ? 'Hết hàng' : 'Thêm vào giỏ' }}
                </button>

                <button type="button" class="pd-heart btn-wishlist" data-id="{{ $product->id }}"
                        data-url="{{ route('wishlist.toggle') }}" aria-label="Lưu {{ $product->name }} vào yêu thích">
                    <i class="bi {{ $inWishlist ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
                </button>
            </div>

            @if (filled($product->description))
                <div class="pd-desc">
                    <h2>Mô tả sản phẩm</h2>
                    {{-- e() chống XSS, nl2br giữ xuống dòng --}}
                    <p>{!! nl2br(e($product->description)) !!}</p>
                </div>
            @endif
        </div>
    </section>

    {{-- ============ ĐÁNH GIÁ ============ --}}
    <section class="pd-reviews" id="reviews" aria-labelledby="reviews-title">
        <h2 id="reviews-title" class="pd-h2">Đánh giá từ khách hàng</h2>

        {{-- Form viết / sửa đánh giá --}}
        @auth
            @if ($canReview || $myReview)
                <form method="POST"
                      action="{{ $myReview ? route('reviews.update', $myReview) : route('reviews.store', $product) }}"
                      class="pd-form">
                    @csrf
                    @if ($myReview) @method('PUT') @endif

                    <h3>{{ $myReview ? 'Chỉnh sửa đánh giá của bạn' : 'Viết đánh giá của bạn' }}</h3>

                    @if ($myReview && $myReview->status !== 'visible')
                        <p class="pd-note">Đánh giá của bạn hiện đang được ẩn bởi quản trị viên.</p>
                    @endif

                    <fieldset class="pd-rate">
                        <legend>Chấm điểm</legend>
                        @php $current = (int) old('rating', $myReview->rating ?? 0); @endphp
                        @for ($i = 1; $i <= 5; $i++)
                            <label for="rate-{{ $i }}">
                                <input type="radio" id="rate-{{ $i }}" name="rating" value="{{ $i }}" @checked($current === $i) required>
                                <span>{{ $i }} <i class="bi bi-star-fill" aria-hidden="true"></i></span>
                            </label>
                        @endfor
                    </fieldset>
                    @error('rating') <p class="pd-error">{{ $message }}</p> @enderror

                    <label for="review-comment" class="pd-label">Nhận xét (không bắt buộc)</label>
                    <textarea id="review-comment" name="comment" rows="4" maxlength="1000" class="pd-textarea"
                              placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm…">{{ old('comment', $myReview->comment ?? '') }}</textarea>
                    @error('comment') <p class="pd-error">{{ $message }}</p> @enderror

                    <button type="submit" class="pc-btn pc-btn--primary">{{ $myReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}</button>
                </form>
            @else
                <p class="pd-note">Chỉ khách đã mua và nhận hàng thành công mới có thể đánh giá sản phẩm này.</p>
            @endif
        @else
            <p class="pd-note">
                <a href="{{ route('login') }}">Đăng nhập</a> để xem và viết đánh giá khi bạn đã mua sản phẩm.
            </p>
        @endauth

        {{-- Danh sách đánh giá --}}
        @forelse ($reviews as $review)
            <article class="pd-review">
                <div class="pd-review__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($review->user->full_name ?? '?', 0, 1)) }}</div>
                <div class="pd-review__body">
                    <p class="pd-review__head">
                        <strong>{{ $review->user->full_name ?? 'Khách hàng' }}</strong>
                        <span class="pd-muted">{{ $review->created_at?->format('d/m/Y') }}</span>
                    </p>
                    <p class="pd-stars" aria-label="{{ $review->rating }} trên 5 sao">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                        @endfor
                    </p>
                    @if (filled($review->comment))
                        <p class="pd-review__text">{!! nl2br(e($review->comment)) !!}</p>
                    @endif
                </div>
            </article>
        @empty
            <p class="pd-empty">Chưa có đánh giá nào cho sản phẩm này.</p>
        @endforelse
    </section>

    {{-- ============ SẢN PHẨM GỢI Ý ============ --}}
    @if ($related->isNotEmpty())
        <section class="pd-related" aria-labelledby="related-title">
            <h2 id="related-title" class="pd-h2">Có thể bạn cũng thích</h2>
            <div class="pd-related__grid">
                @foreach ($related as $p)
                    @include('partials.product-card', ['product' => $p])
                @endforeach
            </div>
        </section>
    @endif

</div>
@endsection

@push('scripts')
<script>
// Ô số lượng: giữ trong [1, tồn kho] và đưa giá trị vào data-qty cho nút .btn-add-to-cart (app.js đọc data('qty'))
(function () {
    const $qty = $('#pd-qty'), $add = $('#pd-add');
    if (!$qty.length) return;

    const max = parseInt($qty.attr('max'), 10) || 1;

    function sync() {
        let v = parseInt($qty.val(), 10);
        if (isNaN(v) || v < 1) v = 1;
        if (v > max) v = max;
        $qty.val(v);
        $add.data('qty', v);
    }

    $('#pd-minus').on('click', () => { $qty.val((parseInt($qty.val(), 10) || 1) - 1); sync(); });
    $('#pd-plus').on('click',  () => { $qty.val((parseInt($qty.val(), 10) || 1) + 1); sync(); });
    $qty.on('input change', sync);
    sync();
})();
</script>
@endpush