@extends('layouts.app')

@section('title', 'Trang chủ')

@section('content')
<div class="container">

    {{-- 1. Banner (Leader thay bằng thiết kế thật) --}}
    <section class="hero rounded-4 p-5 mb-5 text-center">
        <h1 class="display-5 fw-bold">Văn phòng phẩm chính hãng</h1>
        <p class="lead mb-4">Bút, vở, giấy in, dụng cụ học tập — giao nhanh toàn quốc.</p>
        <a href="{{ route('products.index') }}" class="btn btn-dark btn-lg">Mua sắm ngay</a>
    </section>

    {{-- 2. Danh mục --}}
    <section class="mb-5">
        <h2 class="h4 fw-bold mb-3">Danh mục sản phẩm</h2>
        <div class="row g-3">
            @foreach ($categories as $c)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('products.index', ['category' => $c->slug]) }}"
                       class="d-block text-center p-3 border rounded-3 text-decoration-none text-dark category-tile">
                        {{ $c->name }}
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 3-5. Các khối sản phẩm: dùng chung 1 partial --}}
    @foreach ([['Sản phẩm nổi bật', $featured], ['Sản phẩm mới', $newest], ['Đang khuyến mãi', $onSale]] as [$title, $list])
        @if ($list->isNotEmpty())
            <section class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 fw-bold mb-0">{{ $title }}</h2>
                    <a href="{{ route('products.index') }}" class="small">Xem tất cả</a>
                </div>
                <div class="row g-3">
                    @foreach ($list as $p)
                        <div class="col-6 col-md-4 col-lg-3">@include('partials.product-card', ['product' => $p])</div>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</div>
@endsection
