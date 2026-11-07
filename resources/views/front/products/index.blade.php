@extends('layouts.app')

@section('title', 'Cửa hàng')

@push('styles')
<link href="{{ asset('assets/css/pages/catalog.css') }}" rel="stylesheet">
@endpush

@section('content')
{{-- Sprite ngôi sao ✦ --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <symbol id="pc-spark" viewBox="0 0 24 24"><path d="M12 0C12.9 6.9 17.1 11.1 24 12 17.1 12.9 12.9 17.1 12 24 11.1 17.1 6.9 12.9 0 12 6.9 11.1 11.1 6.9 12 0Z"/></symbol>
</svg>

<div class="pc-container cat">

    <nav class="cat-crumb" aria-label="Đường dẫn">
        <a href="{{ route('home') }}">Trang chủ</a> /
        <a href="{{ route('products.index') }}">Cửa hàng</a> /
        <span id="cat-crumb" aria-current="page">{{ $categoryName ?? 'Tất cả văn phòng phẩm' }}</span>
    </nav>

    <header class="cat-head">
        <div>
            <h1 class="cat-title">Ý tưởng lớn tiếp theo bắt đầu từ đây.</h1>
            <p class="cat-summary" id="cat-summary">{{ $summary }}</p>
        </div>
        <svg class="pc-spark cat-spark" aria-hidden="true"><use href="#pc-spark"/></svg>
    </header>

    {{-- ============ THANH LỌC (form GET: vẫn chạy khi tắt JS) ============ --}}
    <form id="filter-form" action="{{ route('products.index') }}" method="GET" class="cat-bar">

        <div class="cat-bar__row">
            <div class="cat-field cat-field--grow">
                <label for="f-q">Tìm theo tên sản phẩm hoặc hãng</label>
                <input id="f-q" class="cat-input" type="search" name="q" value="{{ $filters['q'] }}"
                       maxlength="100" placeholder="Tìm văn phòng phẩm…" autocomplete="off">
            </div>

            <div class="cat-field cat-field--sort">
                <label for="f-sort">Sắp xếp</label>
                <select id="f-sort" class="cat-select" name="sort">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Chỉ hiện ở tablet/mobile: mở ngăn lọc --}}
            <button type="button" class="pc-btn pc-btn--outline cat-filter-toggle"
                    data-bs-toggle="offcanvas" data-bs-target="#filterDrawer" aria-controls="filterDrawer">
                <i class="bi bi-sliders" aria-hidden="true"></i> Bộ lọc
            </button>
        </div>

        {{-- Desktop: hàng ngang. Tablet/mobile: ngăn kéo từ bên trái --}}
        <div class="offcanvas-lg offcanvas-start cat-drawer" tabindex="-1" id="filterDrawer" aria-labelledby="filterDrawerLabel">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title" id="filterDrawerLabel">Bộ lọc</h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#filterDrawer" aria-label="Đóng"></button>
            </div>

            <div class="offcanvas-body">
                <div class="cat-bar__filters">
                    <div class="cat-field">
                        <label for="f-category">Danh mục</label>
                        <select id="f-category" class="cat-select" name="category">
                            <option value="">Tất cả danh mục</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->slug }}" @selected($filters['category'] === $c->slug)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="cat-field">
                        <label for="f-brand">Hãng</label>
                        <select id="f-brand" class="cat-select" name="brand">
                            <option value="">Tất cả hãng</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b }}" @selected($filters['brand'] === $b)>{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="cat-field">
                        <label for="f-min">Giá thấp nhất (₫)</label>
                        <input id="f-min" class="cat-input" type="number" name="min_price" min="0" step="1000"
                               inputmode="numeric" value="{{ $filters['min_price'] }}" placeholder="0">
                    </div>

                    <div class="cat-field">
                        <label for="f-max">Giá cao nhất (₫)</label>
                        <input id="f-max" class="cat-input" type="number" name="max_price" min="0" step="1000"
                               inputmode="numeric" value="{{ $filters['max_price'] }}" placeholder="Không giới hạn">
                    </div>

                    <button type="submit" class="pc-btn pc-btn--primary cat-apply">Áp dụng</button>
                </div>

                <div class="cat-drawer__foot">
                    <button type="button" class="pc-btn pc-btn--primary" data-bs-dismiss="offcanvas" data-bs-target="#filterDrawer">Xem sản phẩm</button>
                    <button type="button" class="pc-btn pc-btn--outline btn-reset-filters">Xóa bộ lọc</button>
                </div>
            </div>
        </div>
    </form>

    {{-- ============ KẾT QUẢ (được thay bằng AJAX khi lọc) ============ --}}
    <div id="product-grid" class="cat-results">
        @include('front.products._grid')
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const $form = $('#filter-form');
    const $grid = $('#product-grid');
    const basePath = new URL($form.prop('action')).pathname;   // đường dẫn tương đối: không lệch host/port
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let xhr = null, timer = null;

    /** Đọc form -> query string gọn (bỏ giá trị rỗng và sort mặc định) */
    function queryString() {
        const p = new URLSearchParams();
        $form.serializeArray().forEach(({ name, value }) => {
            value = $.trim(value);
            if (value === '' || (name === 'sort' && value === 'newest')) return;
            p.set(name, value);
        });
        return p.toString();
    }

    /** Sau mỗi lần tải: cập nhật dòng tóm tắt, breadcrumb, ô tìm kiếm trên header */
    function syncChrome() {
        const $meta = $grid.find('[data-summary]').first();
        if ($meta.length) {
            $('#cat-summary').text($meta.attr('data-summary'));
            $('#cat-crumb').text($meta.attr('data-crumb'));
        }
        $('.site-header input[name=q]').val($form.find('[name=q]').val());
    }

    /** Tải lại phần kết quả bằng AJAX và cập nhật URL (F5 / chia sẻ link vẫn đúng bộ lọc) */
    function load(url, push = true) {
        if (xhr) xhr.abort();
        $grid.addClass('is-loading').attr('aria-busy', 'true');

        const req = xhr = $.ajax({ url, method: 'GET', dataType: 'html' })
            .done(html => {
                $grid.html(html);
                if (push) history.pushState(null, '', url);
                syncChrome();
            })
            .fail((x, status) => {
                if (status !== 'abort') toast('Không tải được danh sách sản phẩm, vui lòng thử lại.', 'danger');
            })
            .always(() => {
                if (req === xhr) $grid.removeClass('is-loading').removeAttr('aria-busy');
            });
    }

    function apply() {
        clearTimeout(timer);
        const qs = queryString();
        load(basePath + (qs ? '?' + qs : ''));
    }

    function resetFields(fields) {
        fields.forEach(f => {
            if (f === 'price') $form.find('[name=min_price],[name=max_price]').val('');
            else $form.find('[name="' + f + '"]').val('');
        });
    }

    // --- Sự kiện ---
    $form.on('change', 'select', apply);                                                   // danh mục, hãng, sắp xếp
    $form.on('input', '[name=q],[name=min_price],[name=max_price]', function () {          // gõ -> chờ 450ms rồi lọc
        clearTimeout(timer);
        timer = setTimeout(apply, 450);
    });
    $form.on('submit', e => { e.preventDefault(); apply(); });                             // Enter / nút Áp dụng

    $(document).on('click', '.filter-chip', function () {                                  // bỏ 1 bộ lọc
        resetFields([$(this).data('clear')]);
        apply();
    });

    $(document).on('click', '.btn-reset-filters', function () {                            // bỏ hết
        resetFields(['q', 'category', 'brand', 'price']);
        $form.find('[name=sort]').val('newest');
        apply();
    });

    $(document).on('click', '#product-grid .cat-pages a', function (e) {                   // phân trang bằng AJAX
        e.preventDefault();
        const u = new URL(this.href);
        load(u.pathname + u.search);
        window.scrollTo({ top: $grid.offset().top - 120, behavior: reduceMotion ? 'auto' : 'smooth' });
    });

    // Nút Back/Forward của trình duyệt: đồng bộ lại form theo URL rồi tải kết quả
    window.addEventListener('popstate', function () {
        const p = new URLSearchParams(location.search);
        $form.find('[name=q],[name=min_price],[name=max_price]').each(function () { this.value = p.get(this.name) || ''; });
        $form.find('[name=category]').val(p.get('category') || '');
        $form.find('[name=brand]').val(p.get('brand') || '');
        $form.find('[name=sort]').val(p.get('sort') || 'newest');
        load(location.pathname + location.search, false);
    });
})();
</script>
@endpush