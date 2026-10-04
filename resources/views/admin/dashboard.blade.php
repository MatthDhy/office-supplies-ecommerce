@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
<h1 class="h3 mb-4">Dashboard</h1>

<div class="row g-3 mb-4">
    @foreach ([
        ['Tổng doanh thu', number_format($totalRevenue, 0, ',', '.') . '₫'],
        ['Tổng đơn hàng', $totalOrders],
        ['Đơn chờ xử lý', $newOrders],
        ['Người dùng', $totalUsers],
        ['Sản phẩm', $totalProducts],
    ] as [$label, $value])
        <div class="col-6 col-lg"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ $label }}</div>
            <div class="h4 mb-0">{{ $value }}</div>
        </div></div></div>
    @endforeach
</div>

{{-- [C] TODO: biểu đồ doanh thu theo tháng (Chart.js), top sản phẩm bán chạy --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Đơn hàng gần đây</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Mã đơn</th><th>Khách</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead>
            <tbody>
            @forelse ($recentOrders as $o)
                <tr>
                    <td>{{ $o->order_code }}</td><td>{{ $o->user->full_name }}</td>
                    <td>{{ number_format($o->total_amount, 0, ',', '.') }}₫</td><td>{{ $o->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Chưa có đơn hàng</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
