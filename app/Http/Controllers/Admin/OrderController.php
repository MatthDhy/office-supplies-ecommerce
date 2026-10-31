<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Quản lý đơn hàng. KHÔNG có xóa đơn (đơn đã phát sinh giao dịch chỉ đổi trạng thái).
 *
 * Quy tắc cập nhật trạng thái (updateStatus):
 *   - Chỉ đi theo Order::TRANSITIONS (pending -> confirmed -> processing -> shipping -> completed, hoặc cancelled).
 *   - Hủy -> OrderService::cancel() (hoàn kho + hoàn coupon + đóng payment pending).
 *   - Đơn PayOS chưa thanh toán: KHÔNG được xác nhận/đẩy tiếp (chỉ webhook mới xác nhận), chỉ được hủy.
 *   - Đơn COD chuyển 'completed' -> payment_status = 'paid'.
 */
class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private OrderService $orders, private PaymentService $payments) {}

    /** GET /admin/orders?q=&status=&payment_status=&from=&to=  — view admin.orders.index nhận $orders, $filters, $statuses */
    public function index(Request $request)
    {
        $f = $request->validate([
            'q'              => ['nullable', 'string', 'max:100'],
            'status'         => ['nullable', Rule::in(array_keys(Order::LABELS))],
            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'failed'])],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date'],
        ]);

        $like = isset($f['q']) ? '%' . addcslashes($f['q'], '%_\\') . '%' : null;

        $orders = Order::with('user:id,full_name,email')
            ->when($like, fn ($q) => $q->where(fn ($w) => $w
                ->where('order_code', 'like', $like)
                ->orWhere('shipping_name', 'like', $like)
                ->orWhere('shipping_phone', 'like', $like)))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['payment_status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders'   => $orders,
            'filters'  => $f,
            'statuses' => Order::LABELS,
        ]);
    }

    /**
     * GET /admin/orders/{order}
     * View admin.orders.show nhận: $order (load user, items.product, payments, coupon),
     *   $nextStatuses [status => label] = các trạng thái admin được chuyển tiếp (đã loại trường hợp bị chặn) -> đổ vào <select>.
     */
    public function show(Order $order)
    {
        $order->load('user', 'items.product', 'payments', 'coupon');

        $nextStatuses = collect(Order::TRANSITIONS[$order->status] ?? [])
            ->reject(fn ($s) => $s !== Order::CANCELLED && $this->awaitingOnlinePayment($order))
            ->mapWithKeys(fn ($s) => [$s => Order::LABELS[$s]])
            ->all();

        return view('admin.orders.show', compact('order', 'nextStatuses'));
    }

    /** PATCH /admin/orders/{order}/status — body: status. Trả redirect back (form) hoặc JSON (AJAX) */
    public function updateStatus(Request $request, Order $order)
    {
        $data   = $request->validate(['status' => ['required', Rule::in(array_keys(Order::LABELS))]]);
        $status = $data['status'];

        try {
            $message = $status === Order::CANCELLED ? $this->cancel($order) : $this->advance($order, $status);
        } catch (DomainException $e) {
            return $request->expectsJson() ? $this->fail($e->getMessage()) : back()->with('error', $e->getMessage());
        }

        $order->refresh();

        return $request->expectsJson()
            ? $this->success(['status' => $order->status, 'label' => $order->status_label, 'payment_status' => $order->payment_status], $message)
            : back()->with('success', $message);
    }

    /* ---------------- nội bộ ---------------- */

    private function cancel(Order $order): string
    {
        $wasPaidOnline = $order->payment_method === 'payos' && $order->payment_status === 'paid';
        $pendingLinks  = $order->payments()->where('payment_method', 'payos')->where('status', 'pending')->get();

        $this->orders->cancel($order);   // admin được hủy cả đơn đã thanh toán (unpaidOnly = false)

        foreach ($pendingLinks as $payment) {
            $this->payments->cancelPayosLink($payment, 'Cửa hàng hủy đơn');
        }

        return $wasPaidOnline
            ? 'Đã hủy đơn. Đơn này ĐÃ THANH TOÁN ONLINE — cần hoàn tiền thủ công cho khách.'
            : 'Đã hủy đơn hàng.';
    }

    private function advance(Order $order, string $status): string
    {
        DB::transaction(function () use ($order, $status) {
            // Khóa dòng + kiểm tra lại: tránh đua với webhook PayOS / user hủy cùng lúc
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (!$locked->canChangeTo($status)) {
                throw new DomainException('Không thể chuyển từ "' . $locked->status_label . '" sang "' . (Order::LABELS[$status] ?? $status) . '".');
            }
            if ($this->awaitingOnlinePayment($locked)) {
                throw new DomainException('Đơn PayOS chưa thanh toán, chưa thể xử lý tiếp. Chỉ có thể hủy đơn.');
            }

            $update = ['status' => $status];
            if ($status === Order::COMPLETED && $locked->payment_method === 'cod') {
                $update['payment_status'] = 'paid';   // COD: thu tiền khi giao xong
            }

            $locked->update($update);
        }, 3);

        return 'Đã cập nhật trạng thái đơn hàng.';
    }

    private function awaitingOnlinePayment(Order $order): bool
    {
        return $order->payment_method === 'payos' && $order->payment_status !== 'paid';
    }
}
