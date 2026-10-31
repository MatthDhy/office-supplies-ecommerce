<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

/** PayOS: return/cancel redirect + webhook (idempotent). */
class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private PaymentService $payments, private OrderService $orders) {}

    /** GET /payment/return — user quay về sau khi thanh toán. Query chỉ để TÌM đơn, không để đánh dấu paid. */
    public function payosReturn(Request $request)
    {
        $payment = $this->findOwnedPayment($request);
        if (!$payment) {
            return redirect()->route('orders.index')->with('error', 'Không tìm thấy giao dịch.');
        }

        $this->payments->syncStatus($payment);   // hỏi lại PayOS (server-to-server) thay vì tin query
        $order = $payment->order->refresh();

        $message = $order->payment_status === 'paid'
            ? 'Thanh toán thành công! Đơn hàng của bạn đã được xác nhận.'
            : 'Hệ thống đang xác nhận thanh toán, vui lòng tải lại trang sau ít giây.';

        return redirect()->route('orders.show', $order->order_code)->with('success', $message);
    }

    /** GET /payment/cancel — user bấm hủy trên trang PayOS. */
    public function payosCancel(Request $request)
    {
        $payment = $this->findOwnedPayment($request);
        if (!$payment) {
            return redirect()->route('orders.index')->with('error', 'Không tìm thấy giao dịch.');
        }

        $this->payments->syncStatus($payment);   // phòng khi thực ra đã trả tiền
        $order = $payment->order->refresh();

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order->order_code)->with('success', 'Đơn hàng đã được thanh toán.');
        }

        try {
            $this->payments->cancelPayosLink($payment);
            $this->orders->cancel($order, true);    // hoàn kho + coupon
            $this->orders->restoreCart($order);     // trả sản phẩm về giỏ
        } catch (DomainException $e) {
            return redirect()->route('orders.show', $order->order_code)->with('error', $e->getMessage());
        }

        return redirect()->route('cart.index')->with('error', 'Bạn đã hủy thanh toán. Sản phẩm đã được trả về giỏ hàng.');
    }

    /** POST /payment/payos/webhook — PayOS gọi (không auth, không CSRF; bảo vệ bằng chữ ký). */
    public function webhook(Request $request)
    {
        try {
            $this->payments->handleWebhook($request->all());
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Server error'], 500); // 5xx để PayOS gửi lại
        }

        return response()->json(['success' => true, 'message' => 'OK']);
    }

    /** Tìm payment theo ?orderCode và chỉ trả về nếu thuộc về user đang đăng nhập (chống xem/hủy đơn người khác). */
    private function findOwnedPayment(Request $request): ?Payment
    {
        $code = (string) $request->query('orderCode');
        if (!ctype_digit($code)) {
            return null;
        }

        $payment = Payment::with('order')->where('provider_order_code', (int) $code)->first();

        return ($payment && (int) $payment->order->user_id === (int) $request->user()->id) ? $payment : null;
    }
}
