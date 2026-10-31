<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Models\Review;
use App\Services\OrderService;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Lịch sử đơn hàng của user (đơn do CheckoutController tạo).
 * HỦY ĐƠN luôn đi qua OrderService::cancel() để hoàn kho + hoàn lượt coupon + đóng payment pending đồng nhất.
 */
class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private OrderService $orders, private PaymentService $payments) {}

    /** GET /orders[?status=pending] — view front.orders.index nhận $orders (Paginator), $statuses (Order::LABELS), $status */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $status = is_string($status) && array_key_exists($status, Order::LABELS) ? $status : null;

        $orders = $request->user()->orders()
            ->withCount('items')
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('front.orders.index', [
            'orders'   => $orders,
            'statuses' => Order::LABELS,
            'status'   => $status,
        ]);
    }

    /**
     * GET /orders/{order_code}
     *
     * View front.orders.show nhận:
     *   $order (đã load items.product, payments, coupon)
     *   $flow ['pending','confirmed','processing','shipping','completed'] + $currentIndex (int|null; null nếu đã hủy) -> vẽ timeline
     *   $canCancel (bool)         -> hiện nút "Hủy đơn" (POST orders.cancel)
     *   $payUrl (string|null)     -> link "Thanh toán lại" nếu đơn PayOS còn hạn
     *   $reviewedProductIds (array) -> sản phẩm đã review rồi; đơn completed mà chưa review thì hiện nút "Đánh giá"
     */
    public function show(Request $request, Order $order)
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);   // KHÔNG cho xem đơn người khác

        $order->load('items.product', 'payments', 'coupon');

        $flow         = [Order::PENDING, Order::CONFIRMED, Order::PROCESSING, Order::SHIPPING, Order::COMPLETED];
        $currentIndex = array_search($order->status, $flow, true);

        $payUrl = null;
        if ($order->payment_method === 'payos'
            && $order->payment_status === 'pending'
            && $order->status === Order::PENDING
            && $order->expires_at?->isFuture()) {
            $payUrl = $order->payments->where('status', 'pending')->sortByDesc('id')->first()?->payment_url;
        }

        $reviewedProductIds = $order->status === Order::COMPLETED
            ? Review::where('user_id', $order->user_id)
                ->whereIn('product_id', $order->items->pluck('product_id'))
                ->pluck('product_id')
                ->all()
            : [];

        return view('front.orders.show', [
            'order'              => $order,
            'flow'               => $flow,
            'currentIndex'       => $currentIndex === false ? null : $currentIndex,
            'canCancel'          => $this->userCanCancel($order),
            'payUrl'             => $payUrl,
            'reviewedProductIds' => $reviewedProductIds,
        ]);
    }

    /** POST /orders/{order_code}/cancel */
    public function cancel(Request $request, Order $order)
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);

        if (!$this->userCanCancel($order)) {
            return back()->with('error', 'Đơn hàng không thể hủy ở trạng thái hiện tại.');
        }

        // Lấy trước các link PayOS đang chờ để đóng sau khi hủy thành công
        $pendingLinks = $order->payments()->where('payment_method', 'payos')->where('status', 'pending')->get();

        try {
            $this->orders->cancel($order, true);   // true = từ chối nếu đã thanh toán online
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        foreach ($pendingLinks as $payment) {
            $this->payments->cancelPayosLink($payment);   // best effort
        }

        return redirect()->route('orders.show', $order->order_code)->with('success', 'Đã hủy đơn hàng.');
    }

    /** User chỉ tự hủy khi đơn còn pending/confirmed và chưa thanh toán online. */
    private function userCanCancel(Order $order): bool
    {
        return in_array($order->status, [Order::PENDING, Order::CONFIRMED], true)
            && $order->payment_status !== 'paid';
    }
}
