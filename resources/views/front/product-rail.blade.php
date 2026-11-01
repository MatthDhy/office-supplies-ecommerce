{{-- Hàng sản phẩm cuộn ngang (4 thẻ/lần nhìn). Tham số: $id, $title, $sub, $items --}}
@if ($items->isNotEmpty())
<section class="pc-section" aria-labelledby="{{ $id }}-title">
    <div class="pc-section__head">
        <div>
            <h2 class="pc-h2" id="{{ $id }}-title">{{ $title }}</h2>
            <p class="pc-section__sub">{{ $sub }}</p>
        </div>

        @if ($items->count() > 4)
            <div class="pc-rail-nav">
                <button type="button" class="pc-rail-btn" data-rail-target="#{{ $id }}" data-dir="-1" aria-label="Xem sản phẩm trước">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="pc-rail-btn" data-rail-target="#{{ $id }}" data-dir="1" aria-label="Xem sản phẩm tiếp theo">
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        @endif
    </div>

    <div class="pc-rail" id="{{ $id }}" role="region" aria-label="{{ $title }}" tabindex="0">
        @foreach ($items as $p)
            <div class="pc-rail__item">@include('partials.product-card', ['product' => $p])</div>
        @endforeach
    </div>
</section>
@endif