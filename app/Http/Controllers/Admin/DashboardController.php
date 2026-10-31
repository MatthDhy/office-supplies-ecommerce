<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

/**
 * Dashboard. Doanh thu chỉ tính đơn payment_status = 'paid' và không bị cancelled.
 *
 * View nhận (giữ nguyên các biến cũ + thêm 3 biến mới):
 *   $totalRevenue, $totalOrders, $newOrders, $totalUsers, $totalProducts, $recentOrders
 *   $chartLabels : ['11/2025', ..., '10/2026']  (12 tháng gần nhất)  ┐ đưa thẳng vào Chart.js:
 *   $chartData   : [0, 150000, ...]             (doanh thu từng tháng) ┘ labels: @json($chartLabels), data: @json($chartData)
 *   $topProducts : 5 sản phẩm bán chạy — mỗi dòng có ->product_id, ->name, ->sold (số lượng), ->revenue
 */
class DashboardController extends Controller
{
    public function index()
    {
        $revenueQuery = fn () => Order::where('payment_status', 'paid')->where('status', '!=', Order::CANCELLED);

        // 12 tháng gần nhất, cũ -> mới
        $months = collect(range(11, 0))->map(fn (int $i) => now()->startOfMonth()->subMonths($i));

        $byMonth = $revenueQuery()
            ->where('created_at', '>=', $months->first())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(total_amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        // Bán chạy: cộng từ order_items của đơn chưa hủy (tên lấy từ snapshot nên sản phẩm đã xóa vẫn hiện)
        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', Order::CANCELLED)
            ->selectRaw('order_items.product_id, MAX(order_items.product_name) as name, SUM(order_items.quantity) as sold, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_id')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'totalRevenue'  => (int) $revenueQuery()->sum('total_amount'),
            'totalOrders'   => Order::count(),
            'newOrders'     => Order::where('status', Order::PENDING)->count(),
            'totalUsers'    => User::where('role', 'user')->count(),
            'totalProducts' => Product::count(),
            'recentOrders'  => Order::with('user:id,full_name')->latest('id')->take(5)->get(),
            'chartLabels'   => $months->map(fn ($m) => $m->format('m/Y'))->all(),
            'chartData'     => $months->map(fn ($m) => (int) ($byMonth[$m->format('Y-m')] ?? 0))->all(),
            'topProducts'   => $topProducts,
        ]);
    }
}
