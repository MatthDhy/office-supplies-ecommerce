<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên B] Lịch sử đơn hàng của user (đơn do CheckoutController của Leader tạo).
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class OrderController extends Controller
{
    use ApiResponse;

    /** GET /orders */
    public function index(Request $request)
    {
        // 1. $request->user()->orders()->withCount('items')->latest()->paginate(10)
        // 2. return view('front.orders.index')
        abort(501, 'TODO [Thành viên B]: OrderController@index');
    }

    /** GET /orders/{order_code} */
    public function show(Request $request, \App\Models\Order $order)
    {
        // 1. abort_unless($order->user_id === $request->user()->id, 403)  // KHÔNG cho xem đơn người khác
        // 2. $order->load('items.product', 'payments', 'coupon')
        // 3. Hiển thị timeline trạng thái từ Order::LABELS
        // 4. Nếu status = completed: hiện nút 'Đánh giá' cho từng sản phẩm
        // 5. return view('front.orders.show')
        abort(501, 'TODO [Thành viên B]: OrderController@show');
    }

    /** POST /orders/{order_code}/cancel */
    public function cancel(Request $request, \App\Models\Order $order)
    {
        // 1. abort_unless($order->user_id === $request->user()->id, 403)
        // 2. Chỉ cho hủy khi status = pending (hoặc confirmed) và chưa thanh toán online
        // 3. DB::transaction: đổi status = cancelled, cancelled_at = now(), cộng lại stock cho từng order_item
        // 4. redirect back with success
        abort(501, 'TODO [Thành viên B]: OrderController@cancel');
    }
}
