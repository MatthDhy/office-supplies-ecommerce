{{-- Footer dùng chung. $full = true (trang chủ) -> bản đầy đủ có cột liên kết + chữ thương hiệu cỡ lớn.
     Mục chưa có route thì hiện chữ thường (không bấm được); có route rồi thì điền URL vào đúng chỗ. --}}
@php
    $brand = 'PAPER CLUB';
    $full  = $full ?? false;

    $columns = [
        'Mua sắm' => [
            ['Tất cả văn phòng phẩm', route('products.index')],
            ['Bút viết',              route('products.index', ['category' => 'but-viet'])],
            ['Sổ & giấy',             route('products.index', ['category' => 'vo-so-tay'])],
            ['Bộ sưu tập',            null],
        ],
        $brand => [
            ['Câu chuyện của chúng mình', null],
            ['Liên hệ',                   null],
            ['Instagram',                 null],
            ['Facebook',                  null],
        ],
        'Hỗ trợ' => [
            ['Câu hỏi thường gặp',  null],
            ['Giao hàng & đổi trả', null],
            ['Chính sách bảo mật',  null],
            ['Điều khoản sử dụng',  null],
        ],
    ];

    $compact = [
        ['Cửa hàng', route('products.index')],
        ['Bộ sưu tập', null], ['Giới thiệu', null], ['Liên hệ', null],
        ['Câu hỏi thường gặp', null], ['Giao hàng & đổi trả', null], ['Bảo mật', null],
    ];
@endphp
<footer class="pc-footer {{ $full ? 'pc-footer--full' : 'pc-footer--compact' }}">
    <div class="pc-container">
        @if ($full)
            <div class="pc-footer__top">
                <div class="pc-footer__brand">
                    <p class="pc-footer__slogan">Chừa chỗ cho ý tưởng.</p>
                    <p>Đồ hay cho bàn làm việc của bạn. Thêm chỗ cho điều đến tiếp theo.</p>
                    <p class="pc-footer__mail">hello@paperclub.example</p>
                </div>

                @foreach ($columns as $title => $items)
                    <nav class="pc-footer__col" aria-label="{{ $title }}">
                        <h2 class="pc-footer__title">{{ $title }}</h2>
                        <ul>
                            @foreach ($items as [$label, $url])
                                <li>@if ($url)<a href="{{ $url }}">{{ $label }}</a>@else<span>{{ $label }}</span>@endif</li>
                            @endforeach
                        </ul>
                    </nav>
                @endforeach
            </div>
        @else
            <p class="pc-footer__slogan">{{ $brand }} · Chừa chỗ cho ý tưởng.</p>
            <ul class="pc-footer__inline">
                @foreach ($compact as [$label, $url])
                    <li>@if ($url)<a href="{{ $url }}">{{ $label }}</a>@else<span>{{ $label }}</span>@endif</li>
                @endforeach
            </ul>
        @endif

        <div class="pc-footer__bottom">
            <span>© {{ date('Y') }} {{ $brand }} · Đồ án website bán văn phòng phẩm</span>
            <span>Thanh toán khi nhận hàng · Thanh toán online qua PayOS</span>
        </div>

        @if ($full)
            <p class="pc-footer__mark" aria-hidden="true">{{ $brand }}</p>
        @endif
    </div>
</footer>