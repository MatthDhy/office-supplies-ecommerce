@extends('layouts.app')
@section('title', 'Giỏ hàng')

@section('content')
<div class="container">
    <h1 class="h3 mb-4">Giỏ hàng</h1>
    {{-- [Leader] TODO: bảng sản phẩm ($cart['items']), +/- số lượng và xóa bằng AJAX
         (PATCH cart.update, DELETE cart.remove — response trả lại $cart mới để vẽ lại), ô nhập coupon, nút "Thanh toán". --}}
    <p class="text-muted">Đang có {{ $cart['count'] }} sản phẩm — tạm tính {{ number_format($cart['subtotal'], 0, ',', '.') }}₫</p>
</div>
@endsection
