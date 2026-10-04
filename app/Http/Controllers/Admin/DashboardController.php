<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

/**
 * [C] BẢN KHUNG — đã chạy được, thành viên C mở rộng tiếp:
 *   - doanh thu theo tháng (group by month) -> Chart.js
 *   - sản phẩm bán chạy (order_items group by product_id, sum quantity)
 *   - đơn hàng gần đây
 * Doanh thu chỉ tính đơn payment_status = 'paid' và không bị cancelled.
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'totalRevenue'  => (int) Order::where('payment_status', 'paid')->where('status', '!=', 'cancelled')->sum('total_amount'),
            'totalOrders'   => Order::count(),
            'newOrders'     => Order::where('status', 'pending')->count(),
            'totalUsers'    => User::where('role', 'user')->count(),
            'totalProducts' => Product::count(),
            'recentOrders'  => Order::with('user')->latest()->take(5)->get(),
        ]);
    }
}
