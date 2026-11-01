@extends('layouts.app')

@section('title', 'Trang chủ')

@push('styles')
<link href="{{ asset('assets/css/pages/home.css') }}" rel="stylesheet">
@endpush

@php
    /*
     * ẢNH TRANG CHỦ — bỏ file vào public/images/home/ với đúng tên dưới đây (chưa có ảnh thì khung hiện màu pastel):
     *   hero.webp  tile-notebooks.webp  tile-pens.webp  tile-desk.webp  tile-art.webp
     *   banner.webp  collage-page.webp  collage-color.webp  collage-desk.webp  story.webp
     */
    $img = fn (string $file) => asset('images/home/' . $file);

    // 4 ô "Tìm thứ bạn cần": nội dung tuyển chọn tĩnh, mỗi ô trỏ tới 1 danh mục (slug) trong DB
    $tiles = [
        ['label' => 'Sổ & giấy',      'slug' => 'vo-so-tay',         'img' => 'tile-notebooks.webp', 'tone' => 'mint'],
        ['label' => 'Bút viết',       'slug' => 'but-viet',          'img' => 'tile-pens.webp',      'tone' => 'pink'],
        ['label' => 'Góc làm việc',   'slug' => 'do-dung-van-phong', 'img' => 'tile-desk.webp',      'tone' => 'butter'],
        ['label' => 'Dụng cụ vẽ',     'slug' => 'dung-cu-hoc-tap',   'img' => 'tile-art.webp',       'tone' => 'lavender'],
    ];
@endphp

@section('content')
{{-- Sprite ngôi sao ✦ dùng lại bằng <use href="#pc-spark"> --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <symbol id="pc-spark" viewBox="0 0 24 24"><path d="M12 0C12.9 6.9 17.1 11.1 24 12 17.1 12.9 12.9 17.1 12 24 11.1 17.1 6.9 12.9 0 12 6.9 11.1 11.1 6.9 12 0Z"/></symbol>
</svg>

<div class="pc-container pc-home">

    {{-- 1. HERO ===================================================== --}}
    <section class="pc-hero" aria-labelledby="hero-title">
        <div class="pc-hero__text">
            <p class="pc-eyebrow pc-eyebrow--wide">Dành cho người hay làm, hay mơ, hay vẽ nguệch ngoạc</p>
            <h1 class="pc-h1" id="hero-title">Dụng cụ tốt.<br>Ý tưởng hay.</h1>
            <p class="pc-lead">Mọi thứ bạn cần để viết, sáng tạo, sắp xếp và biến ý tưởng thành hiện thực.</p>
            <div class="pc-hero__actions">
                <a href="{{ route('products.index') }}" class="pc-btn pc-btn--primary">Mua sắm văn phòng phẩm</a>
                <a href="#danh-muc" class="pc-btn pc-btn--outline">Khám phá bộ sưu tập</a>
            </div>
            <p class="pc-small">Chừa chỗ cho ý tưởng. <svg class="pc-spark" aria-hidden="true"><use href="#pc-spark"/></svg></p>
        </div>

        <div class="pc-hero__media">
            <div class="pc-photo pc-bg-peach"><img src="{{ $img('hero.webp') }}" alt="Sổ tay, bút và phụ kiện bàn làm việc trên nền pastel"></div>
            <svg class="pc-spark pc-hero__spark" aria-hidden="true"><use href="#pc-spark"/></svg>
            <span class="pc-pill">Đồ nhỏ. Khả năng lớn.</span>
        </div>
    </section>

    {{-- 2. TÌM THỨ BẠN CẦN ========================================== --}}
    <section class="pc-section" id="danh-muc" aria-labelledby="tiles-title">
        <div class="pc-section__head">
            <h2 class="pc-h2 pc-h2--md" id="tiles-title">Tìm thứ bạn cần</h2>
            <svg class="pc-spark pc-spark--sm" aria-hidden="true"><use href="#pc-spark"/></svg>
        </div>
        <div class="pc-tiles">
            @foreach ($tiles as $t)
                <a class="pc-tile" href="{{ route('products.index', ['category' => $t['slug']]) }}">
                    <span class="pc-photo pc-tile__img pc-bg-{{ $t['tone'] }}"><img src="{{ $img($t['img']) }}" alt="" loading="lazy"></span>
                    <span class="pc-tile__label">
                        <span class="pc-h3">{{ $t['label'] }}</span>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- 3. BANNER GÓC SÁNG TẠO ====================================== --}}
    <section class="pc-section" aria-labelledby="banner-title">
        <div class="pc-banner">
            <div class="pc-banner__text">
                <p class="pc-eyebrow">Góc sáng tạo / 01</p>
                <h2 class="pc-h2 pc-h2--lg" id="banner-title">Dành chỗ cho sáng tạo</h2>
                <p class="pc-body">Một trang giấy trắng. Một cây bút yêu thích. Một khoảng nhỏ để xem điều gì sẽ đến tiếp theo.</p>
                <a href="{{ route('products.index', ['category' => 'dung-cu-hoc-tap']) }}" class="pc-btn pc-btn--primary">Khám phá bộ sưu tập</a>
                <svg class="pc-spark pc-spark--sm" aria-hidden="true"><use href="#pc-spark"/></svg>
            </div>
            <div class="pc-photo pc-banner__img pc-bg-peach"><img src="{{ $img('banner.webp') }}" alt="Bàn làm việc gỗ với sổ tay mở và cốc đựng bút" loading="lazy"></div>
        </div>
    </section>

    {{-- 4. ĐỒ HAY HO — sản phẩm nổi bật từ DB ======================= --}}
    @include('front.product-rail', [
        'id'    => 'rail-featured',
        'title' => 'Đồ hay ho',
        'sub'   => 'Những món thiết yếu mỗi ngày. Còn nhiều điều đáng thử.',
        'items' => $featured,
    ])

    {{-- 5. BA Ô ẢNH GHÉP ============================================ --}}
    <section class="pc-section" aria-label="Gợi ý theo nhu cầu">
        <div class="pc-collage">
            <a class="pc-collage__item pc-collage__item--big" href="{{ route('products.index', ['category' => 'vo-so-tay']) }}">
                <span class="pc-photo pc-bg-mint"><img src="{{ $img('collage-page.webp') }}" alt="" loading="lazy"></span>
                <span class="pc-collage__label">
                    <strong class="pc-h3 pc-h3--lg">Một trang mới</strong>
                    <span>Cho mọi chương tiếp theo →</span>
                </span>
            </a>
            <a class="pc-collage__item" href="{{ route('products.index', ['category' => 'dung-cu-hoc-tap']) }}">
                <span class="pc-photo pc-bg-lavender"><img src="{{ $img('collage-color.webp') }}" alt="" loading="lazy"></span>
                <span class="pc-collage__label">
                    <strong class="pc-h3 pc-h3--lg">Tô màu vượt đường viền</strong>
                    <span>Dụng cụ cho trí tưởng tượng →</span>
                </span>
            </a>
            <a class="pc-collage__item" href="{{ route('products.index', ['category' => 'do-dung-van-phong']) }}">
                <span class="pc-photo pc-bg-butter"><img src="{{ $img('collage-desk.webp') }}" alt="" loading="lazy"></span>
                <span class="pc-collage__label">
                    <strong class="pc-h3 pc-h3--lg">Bàn làm việc gọn gàng</strong>
                    <span>Một chút ngăn nắp. Nhiều khả năng mới →</span>
                </span>
            </a>
        </div>
    </section>

    {{-- 6. AI CŨNG MÊ — tuyển chọn, không xếp hạng ================== --}}
    @include('front.product-rail', [
        'id'    => 'rail-favorites',
        'title' => 'Ai cũng mê',
        'sub'   => 'Vài món chúng mình nghĩ bạn sẽ thích. Tuyển chọn, không xếp hạng.',
        'items' => $favorites,
    ])

    {{-- 7. CÂU CHUYỆN THƯƠNG HIỆU =================================== --}}
    <section class="pc-section" aria-labelledby="story-title">
        <div class="pc-story">
            <div class="pc-photo pc-story__img pc-bg-soft"><img src="{{ $img('story.webp') }}" alt="Góc bàn bên cửa sổ với sổ tay, bút chì và chậu cây nhỏ" loading="lazy"></div>
            <div class="pc-story__text">
                <p class="pc-eyebrow">Xin chào, chúng mình là PAPER CLUB.</p>
                <h2 class="pc-h2 pc-h2--lg" id="story-title">Thêm chút chỗ để sáng tạo</h2>
                <p class="pc-body pc-body--lg">Chúng mình tin rằng đồ dùng tốt khiến những khoảnh khắc thường ngày dễ bắt đầu hơn. Cho bản nháp đầu tiên, giáo án ngày mai, ý tưởng lớn hay danh sách nhỏ — ở đây đều có chỗ cho tất cả.</p>
                {{-- TODO: trỏ tới trang "Câu chuyện của chúng mình" khi Leader thêm route --}}
                <a href="#" class="pc-btn pc-btn--outline">Câu chuyện của chúng mình</a>
            </div>
        </div>
    </section>

    {{-- Form đăng ký email (Figma): chưa có backend nên chưa đưa vào. Thêm lại khi có route. --}}
</div>
@endsection

@push('scripts')
<script>
(function () {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Mũi tên ← → của hàng sản phẩm: cuộn đúng một "trang"
    document.querySelectorAll('[data-rail-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const rail = document.querySelector(btn.dataset.railTarget);
            if (!rail) return;
            rail.scrollBy({ left: Number(btn.dataset.dir) * (rail.clientWidth + 24), behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    });

    // Ảnh chưa có file: ẩn ảnh vỡ để khung pastel hiện ra thay vì icon lỗi
    document.querySelectorAll('.pc-photo img').forEach(function (img) {
        const hide = function () { img.style.visibility = 'hidden'; };
        if (img.complete && img.naturalWidth === 0) hide(); else img.addEventListener('error', hide);
    });
})();
</script>
@endpush