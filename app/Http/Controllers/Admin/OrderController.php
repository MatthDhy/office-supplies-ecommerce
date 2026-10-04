<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên C] Quản lý đơn hàng.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class OrderController extends Controller
{
    use ApiResponse;

    /** GET /admin/orders */
    public function index(Request $request)
    {
        // 1. Lọc theo status, payment_status, ngày, mã đơn
        // 2. Order::with('user')->latest()->paginate(15)
        // 3. return view('admin.orders.index')
        abort(501, 'TODO [Thành viên C]: OrderController@index');
    }

    /** GET /admin/orders/{order} */
    public function show(\App\Models\Order $order)
    {
        // 1. $order->load('user','items.product','payments','coupon')
        // 2. return view('admin.orders.show')
        abort(501, 'TODO [Thành viên C]: OrderController@show');
    }

    /** PATCH /admin/orders/{order}/status */
    public function updateStatus(Request $request, \App\Models\Order $order)
    {
        // 1. validate status in Order::LABELS keys
        // 2. if (!$order->canChangeTo($status)) -> báo lỗi (đã có sẵn bảng TRANSITIONS trong model Order)
        // 3. Nếu chuyển sang cancelled: DB::transaction hoàn lại stock + set cancelled_at
        // 4. Nếu COD và chuyển sang completed: set payment_status = 'paid'
        // 5. KHÔNG cho xóa đơn đã phát sinh giao dịch
        abort(501, 'TODO [Thành viên C]: OrderController@updateStatus');
    }
}
