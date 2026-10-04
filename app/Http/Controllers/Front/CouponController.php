<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Leader] Áp mã giảm giá bằng AJAX tại giỏ hàng/checkout.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class CouponController extends Controller
{
    use ApiResponse;

    /** POST /coupon/apply  (AJAX, JSON) */
    public function apply(Request $request)
    {
        // 1. validate code
        // 2. $subtotal = CartService->detail()['subtotal']  // lấy từ SERVER, không nhận từ client
        // 3. CouponService::validate($code, $subtotal, $user) -> trả discount
        // 4. return success(['discount','total']) hoặc fail(message)
        abort(501, 'TODO [Leader]: CouponController@apply');
    }
}
