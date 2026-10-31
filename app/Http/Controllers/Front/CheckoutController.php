<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Traits\ApiResponse;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Http\Request;
use Throwable;

/** Trang thanh toán + tạo đơn hàng. Controller mỏng: validate -> gọi Service -> điều hướng. */
class CheckoutController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CartService $cart,
        private OrderService $orders,
        private PaymentService $payments,
    ) {}

    /** GET /checkout */
    public function index(Request $request)
    {
        $cart = $this->cart->detail();
        if (empty($cart['items'])) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }

        $user      = $request->user();
        $addresses = $user->addresses()->orderByDesc('is_default')->latest('id')->get();
        $default   = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        return view('front.checkout', [
            'cart'         => $cart,
            'user'         => $user,
            'addresses'    => $addresses,
            'default'      => $default,
            'shippingFee'  => $this->orders->shippingFee($cart['subtotal']),
            'freeShipFrom' => OrderService::FREE_SHIP_THRESHOLD,
        ]);
    }

    /** POST /checkout */
    public function store(CheckoutRequest $request)
    {
        try {
            $order = $this->orders->createFromCart($request->user(), $request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // COD: xong luôn
        if ($order->payment_method === 'cod') {
            return redirect()->route('orders.show', $order->order_code)
                ->with('success', 'Đặt hàng thành công! Mã đơn: ' . $order->order_code);
        }

        // PayOS: tạo link thanh toán; lỗi -> hủy đơn (hoàn kho) + trả lại giỏ để user thử lại
        try {
            $payment = $this->payments->createPayosPayment($order);
        } catch (Throwable $e) {
            report($e);
            $this->orders->cancel($order, true);
            $this->orders->restoreCart($order);

            return redirect()->route('checkout.index')
                ->with('error', 'Không kết nối được cổng thanh toán. Vui lòng thử lại hoặc chọn thanh toán khi nhận hàng (COD).');
        }

        return redirect()->away($payment->payment_url);
    }
}
