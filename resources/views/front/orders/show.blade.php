@extends('layouts.app')

@section('title', 'Đơn hàng ' . $order->order_code)

@push('styles')
<link href="{{ asset('assets/css/pages/catalog.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/pages/orders.css') }}" rel="stylesheet">
@endpush

{{--
    OrderController@show truyền vào:
      $order (đã load items.product, payments, coupon), $flow (5 bước), $currentIndex (int|null; null = đã hủy),
      $canCancel (bool), $payUrl (string|null), $reviewedProductIds (array)
--}}
@section('content')
@php
    $money = fn ($n) => number_format((int) $n, 0, ',', '.') . '₫';
    $methodLabels = ['cod' => 'Thanh toán khi nhận hàng (COD)', 'payos' => 'Chuyển khoản online (PayOS)'];
    $payLabels = ['pending' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thanh toán thất bại'];
    $isCancelled = $order->status === \App\Models\Order::CANCELLED;
    $isCompleted = $order->status === \App\Models\Order::COMPLETED;
@endphp

<div class="pc-container cat">

    <nav class="cat-crumb" aria-label="Đường dẫn">
        <a href="{{ route('home') }}">Trang chủ</a> /
        <a href="{{ route('orders.index') }}">Đơn hàng của tôi</a> /
        <span aria-current="page">{{ $order->order_code }}</span>
    </nav>

    <header class="cat-head">
        <div>
            <h1 class="cat-title">Đơn {{ $order->order_code }}</h1>
            <p class="cat-summary">Đặt lúc {{ $order->created_at?->format('H:i d/m/Y') }}</p>
        </div>
        <span class="od-badge od-badge--{{ $order->status }} od-badge--lg">{{ $order->status_label }}</span>
    </header>

    {{-- ============ TIMELINE / TRẠNG THÁI HỦY ============ --}}
    @if ($isCancelled)
        <p class="od-alert od-alert--danger" role="status">
            Đơn hàng đã bị hủy{{ $order->cancelled_at ? ' lúc ' . $order->cancelled_at->format('H:i d/m/Y') : '' }}.
        </p>
    @else
        <ol class="od-timeline" aria-label="Tiến trình đơn hàng">
            @foreach ($flow as $i => $step)
                @php
                    $state = $currentIndex === null ? '' : ($i < $currentIndex ? 'is-done' : ($i === $currentIndex ? 'is-current' : ''));
                @endphp
                <li class="od-step {{ $state }}" @if ($state === 'is-current') aria-current="step" @endif>
                    <span class="od-step__dot" aria-hidden="true">
                        @if ($state === 'is-done') <i class="bi bi-check-lg"></i> @else {{ $i + 1 }} @endif
                    </span>
                    <span class="od-step__label">{{ \App\Models\Order::LABELS[$step] ?? $step }}</span>
                </li>
            @endforeach
        </ol>
    @endif

    {{-- Thanh toán lại PayOS (chỉ khi controller trả $payUrl) --}}
    @if ($payUrl)
        <div class="od-alert od-alert--info">
            <span>Đơn hàng đang chờ thanh toán online
                @if ($order->expires_at) (hết hạn lúc {{ $order->expires_at->format('H:i d/m/Y') }}) @endif.
            </span>
            <a href="{{ $payUrl }}" class="pc-btn pc-btn--primary" rel="noopener">Thanh toán lại</a>
        </div>
    @endif

    <div class="od-layout">

        {{-- ============ DANH SÁCH SẢN PHẨM ============ --}}
        <section aria-labelledby="od-items-title">
            <h2 id="od-items-title" class="od-h2">Sản phẩm</h2>

            <ul class="od-items">
                @foreach ($order->items as $item)
                    @php
                        $product   = $item->product;
                        $available = $product && !$product->trashed() && $product->status === 'active';
                        $reviewed  = in_array($item->product_id, $reviewedProductIds, true);
                    @endphp
                    <li class="od-item">
                        <div class="pc-photo od-item__img">
                            <img src="{{ $product?->image_url ?? 'https://placehold.co/160x160?text=' . urlencode($item->product_name) }}" alt="" loading="lazy">
                        </div>

                        <div class="od-item__body">
                            <p class="od-item__name">
                                {{-- Tên/giá lấy từ snapshot của đơn, không phải giá hiện tại --}}
                                @if ($available)
                                    <a href="{{ route('products.show', $product->slug) }}">{{ $item->product_name }}</a>
                                @else
                                    {{ $item->product_name }}
                                @endif
                            </p>
                            <p class="od-muted">{{ $money($item->unit_price) }} × {{ $item->quantity }}</p>

                            @if ($isCompleted && $available)
                                @if ($reviewed)
                                    <span class="od-muted"><i class="bi bi-check2-circle" aria-hidden="true"></i> Đã đánh giá</span>
                                @else
                                    <a href="{{ route('products.show', $product->slug) }}#reviews" class="od-item__review">
                                        <i class="bi bi-star" aria-hidden="true"></i> Đánh giá sản phẩm
                                    </a>
                                @endif
                            @endif
                        </div>

                        <p class="od-item__sum">{{ $money($item->subtotal) }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- ============ TỔNG KẾT + THÔNG TIN ============ --}}
        <aside class="od-side">
            <section class="od-box" aria-labelledby="od-sum-title">
                <h2 id="od-sum-title" class="od-h3">Thanh toán</h2>
                <dl class="od-sum">
                    <div><dt>Tạm tính</dt><dd>{{ $money($order->subtotal) }}</dd></div>
                    @if ($order->discount_amount > 0)
                        <div>
                            <dt>Giảm giá{{ $order->coupon ? ' (' . $order->coupon->coupon_code . ')' : '' }}</dt>
                            <dd>-{{ $money($order->discount_amount) }}</dd>
                        </div>
                    @endif
                    <div><dt>Phí vận chuyển</dt><dd>{{ $order->shipping_fee > 0 ? $money($order->shipping_fee) : 'Miễn phí' }}</dd></div>
                    <div class="od-sum__total"><dt>Tổng cộng</dt><dd>{{ $money($order->total_amount) }}</dd></div>
                </dl>
                <p class="od-muted">
                    {{ $methodLabels[$order->payment_method] ?? $order->payment_method }} ·
                    <strong>{{ $payLabels[$order->payment_status] ?? $order->payment_status }}</strong>
                </p>
            </section>

            <section class="od-box" aria-labelledby="od-ship-title">
                <h2 id="od-ship-title" class="od-h3">Giao hàng</h2>
                <p class="od-ship">
                    <strong>{{ $order->shipping_name }}</strong><br>
                    {{ $order->shipping_phone }}<br>
                    {{ $order->shipping_address }}
                </p>
                @if (filled($order->note))
                    <p class="od-muted">Ghi chú: {!! nl2br(e($order->note)) !!}</p>
                @endif
            </section>

            @if ($canCancel)
                <form method="POST" action="{{ route('orders.cancel', $order->order_code) }}"
                      onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?');">
                    @csrf
                    <button type="submit" class="pc-btn pc-btn--outline od-cancel">Hủy đơn hàng</button>
                </form>
            @endif
        </aside>
    </div>

</div>
@endsection