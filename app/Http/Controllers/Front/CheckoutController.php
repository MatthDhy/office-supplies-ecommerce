<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Leader] Trang thanh toán + tạo đơn hàng.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class CheckoutController extends Controller
{
    use ApiResponse;

    /** GET /checkout */
    public function index(Request $request)
    {
        // 1. $cart = CartService->detail(); nếu rỗng -> redirect cart.index
        // 2. Lấy danh sách địa chỉ của user + địa chỉ default
        // 3. Tính shipping fee (OrderService::shippingFee($subtotal))
        // 4. return view('front.checkout')
        abort(501, 'TODO [Leader]: CheckoutController@index');
    }

    /** POST /checkout */
    public function store(Request $request)
    {
        // 1. CheckoutRequest: shipping_name/phone/address, payment_method (cod|payos), coupon_code nullable, note
        // 2. OrderService::createFromCart($user, $data)  // toàn bộ trong DB::transaction
        // 3. COD -> redirect orders.show; PAYOS -> PaymentService::createPayosPayment($order) rồi redirect tới payment_url
        abort(501, 'TODO [Leader]: CheckoutController@store');
    }
}
