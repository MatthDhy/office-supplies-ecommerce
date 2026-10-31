<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Áp mã giảm giá bằng AJAX tại giỏ hàng/checkout. Đây chỉ là XEM TRƯỚC;
 * khi đặt hàng, OrderService sẽ validate lại từ đầu (không tin kết quả ở đây).
 */
class CouponController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CartService $cart,
        private CouponService $coupons,
        private OrderService $orders,
    ) {}

    /** POST /coupon/apply  (AJAX, JSON) */
    public function apply(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
        ], [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
            'code.regex'    => 'Mã giảm giá không hợp lệ.',
        ]);

        $subtotal = $this->cart->detail()['subtotal']; // lấy từ SERVER, không nhận từ client
        if ($subtotal <= 0) {
            return $this->fail('Giỏ hàng đang trống.');
        }

        try {
            $result = $this->coupons->validate($data['code'], $subtotal, $request->user());
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        $discount = $result['discount'];
        $shipping = $this->orders->shippingFee($subtotal - $discount);

        return $this->success([
            'code'         => $result['coupon']->code,
            'subtotal'     => $subtotal,
            'discount'     => $discount,
            'shipping_fee' => $shipping,
            'total'        => $subtotal - $discount + $shipping,
        ], 'Áp dụng mã giảm giá thành công');
    }
}
