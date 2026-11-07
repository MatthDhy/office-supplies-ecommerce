@extends('layouts.app')

@section('title', 'Đơn hàng của tôi')

@push('styles')
<link href="{{ asset('assets/css/pages/catalog.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/pages/orders.css') }}" rel="stylesheet">
@endpush

{{-- OrderController@index truyền vào: $orders (Paginator, có items_count), $statuses (Order::LABELS), $status (string|null) --}}
@section('content')
@php
    $money = fn ($n) => number_format((int) $n, 0, ',', '.') . '₫';
    $methodLabels = ['cod' => 'Thanh toán khi nhận hàng', 'payos' => 'Chuyển khoản online'];
    $payLabels = ['pending' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thanh toán thất bại'];
@endphp

<div class="pc-container cat">

    <nav class="cat-crumb" aria-label="Đường dẫn">
        <a href="{{ route('home') }}">Trang chủ</a> /
        <span aria-current="page">Đơn hàng của tôi</span>
    </nav>

    <header class="cat-head">
        <div>
            <h1 class="cat-title">Đơn hàng của tôi</h1>
            <p class="cat-summary">{{ number_format($orders->total(), 0, ',', '.') }} đơn hàng{{ $status ? ' · ' . $statuses[$status] : '' }}</p>
        </div>
    </header>

    {{-- Lọc theo trạng thái (link GET, chạy cả khi tắt JS) --}}
    <nav class="od-tabs" aria-label="Lọc theo trạng thái">
        <a href="{{ route('orders.index') }}" class="od-tab {{ $status === null ? 'is-active' : '' }}" @if ($status === null) aria-current="page" @endif>Tất cả</a>
        @foreach ($statuses as $value => $label)
            <a href="{{ route('orders.index', ['status' => $value]) }}"
               class="od-tab {{ $status === $value ? 'is-active' : '' }}"
               @if ($status === $value) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <hr class="cat-rule">

    @if ($orders->isEmpty())
        <div class="cat-empty">
            <i class="bi bi-receipt" aria-hidden="true"></i>
            <h2>{{ $status ? 'Không có đơn nào ở trạng thái này.' : 'Bạn chưa có đơn hàng nào.' }}</h2>
            <p>{{ $status ? 'Thử chọn trạng thái khác hoặc xem tất cả đơn hàng.' : 'Khi bạn đặt hàng, đơn sẽ xuất hiện ở đây để theo dõi.' }}</p>
            @if ($status)
                <a href="{{ route('orders.index') }}" class="pc-btn pc-btn--outline">Xem tất cả đơn</a>
            @else
                <a href="{{ route('products.index') }}" class="pc-btn pc-btn--primary">Đi tới cửa hàng</a>
            @endif
        </div>
    @else
        <div class="od-list">
            @foreach ($orders as $order)
                <article class="od-card">
                    <div class="od-card__head">
                        <div>
                            <p class="od-code">{{ $order->order_code }}</p>
                            <p class="od-muted">Đặt lúc {{ $order->created_at?->format('H:i d/m/Y') }}</p>
                        </div>
                        <span class="od-badge od-badge--{{ $order->status }}">{{ $order->status_label }}</span>
                    </div>

                    <dl class="od-card__meta">
                        <div><dt>Số mặt hàng</dt><dd>{{ $order->items_count }}</dd></div>
                        <div><dt>Thanh toán</dt><dd>{{ $methodLabels[$order->payment_method] ?? $order->payment_method }} · {{ $payLabels[$order->payment_status] ?? $order->payment_status }}</dd></div>
                        <div><dt>Tổng tiền</dt><dd class="od-total">{{ $money($order->total_amount) }}</dd></div>
                    </dl>

                    <a href="{{ route('orders.show', $order->order_code) }}" class="pc-btn pc-btn--outline od-card__link">Xem chi tiết</a>
                </article>
            @endforeach
        </div>

        <div class="cat-pager">
            <p class="cat-pager__info">
                {{ $orders->firstItem() }}–{{ $orders->lastItem() }} / {{ number_format($orders->total(), 0, ',', '.') }} đơn hàng
            </p>
            {{ $orders->links('front.products.pagination') }}
        </div>
    @endif

</div>
@endsection