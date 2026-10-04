<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị') — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-light">
<div class="d-flex">
    <aside class="admin-sidebar bg-dark text-white p-3">
        <a href="{{ route('admin.dashboard') }}" class="d-block text-white text-decoration-none fw-bold mb-4">
            <i class="bi bi-speedometer2"></i> Quản trị
        </a>
        @php
            $menu = [
                ['admin.dashboard', 'bi-house', 'Dashboard'],
                ['admin.orders.index', 'bi-receipt', 'Đơn hàng'],
                ['admin.products.index', 'bi-box-seam', 'Sản phẩm'],
                ['admin.categories.index', 'bi-tags', 'Danh mục'],
                ['admin.users.index', 'bi-people', 'Người dùng'],
                ['admin.coupons.index', 'bi-ticket-perforated', 'Mã giảm giá'],
                ['admin.reviews.index', 'bi-star', 'Đánh giá'],
            ];
        @endphp
        <nav class="nav flex-column">
            @foreach ($menu as [$r, $icon, $label])
                <a class="nav-link text-white-50 {{ ($r === 'admin.dashboard' ? request()->routeIs($r) : request()->routeIs(Str::beforeLast($r, '.') . '.*')) ? 'active text-white' : '' }}"
                   href="{{ route($r) }}"><i class="bi {{ $icon }}"></i> {{ $label }}</a>
            @endforeach
        </nav>
        <hr class="border-secondary">
        <a class="nav-link text-white-50" href="{{ route('home') }}"><i class="bi bi-arrow-left"></i> Về trang web</a>
        <form action="{{ route('logout') }}" method="POST">@csrf
            <button class="btn btn-link nav-link text-white-50 text-start"><i class="bi bi-box-arrow-right"></i> Đăng xuất</button>
        </form>
    </aside>

    <main class="flex-grow-1 p-4">
        @yield('content')
    </main>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
@if (session('success')) <script>toast(@json(session('success')), 'success');</script> @endif
@if (session('error'))   <script>toast(@json(session('error')), 'danger');</script> @endif
@stack('scripts')
</body>
</html>
