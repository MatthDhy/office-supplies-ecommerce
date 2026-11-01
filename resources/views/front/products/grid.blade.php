{{-- Phần kết quả: được include ở index và được controller trả riêng khi gọi AJAX.
     data-summary / data-crumb: JS đọc để cập nhật dòng tóm tắt và breadcrumb phía trên. --}}
<div data-summary="{{ $summary }}" data-crumb="{{ $categoryName ?? 'Tất cả văn phòng phẩm' }}">

    {{-- Thẻ bộ lọc đang bật --}}
    <div class="cat-chips">
        @if ($categoryName)
            <button type="button" class="cat-pill cat-pill--x filter-chip" data-clear="category" aria-label="Bỏ bộ lọc danh mục {{ $categoryName }}">
                {{ $categoryName }} <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        @else
            <span class="cat-pill">Tất cả văn phòng phẩm</span>
        @endif

        @foreach ($chips as $chip)
            <button type="button" class="cat-pill cat-pill--x filter-chip" data-clear="{{ $chip['field'] }}" aria-label="Bỏ bộ lọc {{ $chip['label'] }}">
                {{ $chip['label'] }} <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        @endforeach

        @if ($hasFilters)
            <button type="button" class="cat-clear btn-reset-filters">Xóa bộ lọc</button>
        @endif
    </div>

    <hr class="cat-rule">

    @if ($products->isEmpty())
        <div class="cat-empty">
            <i class="bi bi-box-seam" aria-hidden="true"></i>
            <h2>Chưa có kết quả.</h2>
            <p>Thử tên sản phẩm hoặc hãng khác, hoặc xóa bộ lọc để xem tất cả.</p>
            @if ($hasFilters)
                <button type="button" class="pc-btn pc-btn--primary btn-reset-filters">Xóa bộ lọc</button>
            @endif
        </div>
    @else
        <div class="cat-grid">
            @foreach ($products as $p)
                @include('partials.product-card', ['product' => $p])
            @endforeach
        </div>

        <div class="cat-pager">
            <p class="cat-pager__info" aria-live="polite">
                {{ $products->firstItem() }}–{{ $products->lastItem() }} / {{ number_format($products->total(), 0, ',', '.') }} sản phẩm · {{ $products->perPage() }} sản phẩm mỗi trang
            </p>
            {{ $products->links('front.products.pagination') }}
        </div>
    @endif
</div>