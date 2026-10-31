<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderCoupon;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tạo / hủy đơn hàng. Nghiệp vụ nhạy cảm nhất -> luôn DB::transaction + khóa dòng.
 * Controller khác (User hủy đơn, Admin hủy đơn) PHẢI gọi cancel() ở đây để hoàn kho + hoàn coupon đồng nhất.
 */
class OrderService
{
    public const SHIPPING_FEE         = 30000;
    public const FREE_SHIP_THRESHOLD  = 300000;
    public const PAYOS_EXPIRE_MINUTES = 15;

    private const METHODS = ['cod', 'payos'];

    public function __construct(private CartService $cart, private CouponService $coupons) {}

    /** Miễn phí ship từ 300.000đ (tính trên tiền hàng SAU giảm giá), còn lại 30.000đ. */
    public function shippingFee(int $subtotal): int
    {
        return ($subtotal <= 0 || $subtotal >= self::FREE_SHIP_THRESHOLD) ? 0 : self::SHIPPING_FEE;
    }

    /**
     * Giỏ hàng (session) -> Order + OrderItems.
     * $data: shipping_name, shipping_phone, shipping_address, payment_method, coupon_code?, note?
     * Lỗi nghiệp vụ -> DomainException (controller bắt và báo user).
     */
    public function createFromCart(User $user, array $data): Order
    {
        if (!$user->isActive()) {
            throw new DomainException('Tài khoản của bạn đã bị khóa.');
        }

        $method = $data['payment_method'] ?? null;
        if (!in_array($method, self::METHODS, true)) {
            throw new DomainException('Phương thức thanh toán không hợp lệ.');
        }

        // Chống bấm đúp / mở 2 tab đặt cùng lúc -> tạo 2 đơn trừ kho 2 lần
        $lock = Cache::lock("checkout:{$user->id}", 20);
        if (!$lock->get()) {
            throw new DomainException('Đơn hàng đang được xử lý, vui lòng đợi giây lát.');
        }

        try {
            $order = DB::transaction(fn () => $this->placeOrder($user, $data, $method), 3);

            // Kho đã được giữ chỗ trong đơn nên xóa giỏ ngay (cả COD lẫn PayOS).
            // Webhook PayOS là server gọi server, không có session của user nên không thể xóa giỏ ở đó.
            $this->cart->clear();

            return $order->load('items');
        } finally {
            $lock->release();
        }
    }

    /**
     * Hủy đơn: hoàn kho + hoàn lượt coupon + đóng payment pending.
     * @param bool $unpaidOnly true => từ chối nếu đơn đã thanh toán online (dùng cho user hủy / hết hạn / bấm hủy PayOS)
     */
    public function cancel(Order $order, bool $unpaidOnly = false): Order
    {
        DB::transaction(function () use ($order, $unpaidOnly) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (!$locked->canChangeTo(Order::CANCELLED)) {
                throw new DomainException('Đơn hàng không thể hủy ở trạng thái hiện tại.');
            }
            if ($unpaidOnly && $locked->payment_status === 'paid') {
                throw new DomainException('Đơn hàng đã thanh toán, không thể tự hủy. Vui lòng liên hệ cửa hàng.');
            }

            foreach ($locked->items()->orderBy('product_id')->get() as $item) {
                Product::withTrashed()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }

            if ($oc = $locked->coupon) {
                $this->coupons->release($oc->coupon_id);
            }

            $locked->payments()->where('status', 'pending')->update(['status' => 'cancelled']);

            $locked->update([
                'status'         => Order::CANCELLED,
                'cancelled_at'   => now(),
                'payment_status' => ($locked->payment_method === 'payos' && $locked->payment_status === 'pending')
                    ? 'failed' : $locked->payment_status,
            ]);
        }, 3);

        return $order->refresh();
    }

    /** Hủy các đơn PayOS quá hạn thanh toán (gọi từ lệnh orders:cancel-expired). Trả về số đơn đã hủy. */
    public function cancelExpired(): int
    {
        $count = 0;

        Order::where('payment_method', 'payos')
            ->where('payment_status', 'pending')
            ->where('status', Order::PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(function (Order $order) use (&$count) {
                try {
                    $this->cancel($order, true);
                    $count++;
                } catch (DomainException) {
                    // vừa được thanh toán / đổi trạng thái -> bỏ qua
                }
            });

        return $count;
    }

    /** Đưa lại các sản phẩm của đơn vào giỏ (khi user hủy thanh toán PayOS hoặc tạo link lỗi). */
    public function restoreCart(Order $order): void
    {
        foreach ($order->items as $item) {
            try {
                $this->cart->add($item->product_id, $item->quantity);
            } catch (DomainException) {
                // sản phẩm hết hàng / ngừng bán -> bỏ qua dòng đó
            }
        }
    }

    /* ---------------- nội bộ ---------------- */

    private function placeOrder(User $user, array $data, string $method): Order
    {
        $cart = $this->cart->raw();
        if (empty($cart)) {
            throw new DomainException('Giỏ hàng đang trống.');
        }

        // Khóa các sản phẩm theo thứ tự id tăng dần -> tránh deadlock giữa các đơn đồng thời
        $ids = array_map('intval', array_keys($cart));
        sort($ids);
        $products = Product::active()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        $lines = [];
        $subtotal = 0;

        foreach ($cart as $id => $qty) {
            $qty = (int) $qty;
            $product = $products->get((int) $id);

            if (!$product) {
                throw new DomainException('Có sản phẩm trong giỏ đã ngừng bán, vui lòng kiểm tra lại giỏ hàng.');
            }
            if ($qty < 1) {
                continue;
            }
            if ($product->stock < $qty) {
                throw new DomainException("Sản phẩm \"{$product->name}\" chỉ còn {$product->stock} trong kho.");
            }

            $unit = $product->final_price;              // GIÁ LẤY TỪ DB, không từ client
            $lines[] = ['product' => $product, 'qty' => $qty, 'unit' => $unit, 'line' => $unit * $qty];
            $subtotal += $unit * $qty;
        }

        if (!$lines) {
            throw new DomainException('Giỏ hàng đang trống.');
        }

        // Coupon: validate lại + khóa dòng ngay trong transaction
        $coupon = null;
        $discount = 0;
        $code = trim((string) ($data['coupon_code'] ?? ''));
        if ($code !== '') {
            $result = $this->coupons->validate($code, $subtotal, $user, true);
            $coupon = $result['coupon'];
            $discount = $result['discount'];
        }

        $shipping = $this->shippingFee($subtotal - $discount);
        $total = $subtotal - $discount + $shipping;

        do {
            $orderCode = Order::generateCode();
        } while (Order::where('order_code', $orderCode)->exists());

        $order = Order::create([
            'user_id'          => $user->id,
            'order_code'       => $orderCode,
            'subtotal'         => $subtotal,
            'discount_amount'  => $discount,
            'shipping_fee'     => $shipping,
            'total_amount'     => $total,
            'payment_method'   => $method,
            'payment_status'   => 'pending',
            'status'           => Order::PENDING,
            'shipping_name'    => trim($data['shipping_name']),
            'shipping_phone'   => trim($data['shipping_phone']),
            'shipping_address' => trim($data['shipping_address']),
            'note'             => isset($data['note']) ? trim($data['note']) : null,
            'expires_at'       => $method === 'payos' ? now()->addMinutes(self::PAYOS_EXPIRE_MINUTES) : null,
        ]);

        foreach ($lines as $l) {
            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $l['product']->id,
                'product_name' => $l['product']->name,   // SNAPSHOT tên
                'unit_price'   => $l['unit'],            // SNAPSHOT giá tại thời điểm mua
                'quantity'     => $l['qty'],
                'subtotal'     => $l['line'],
            ]);

            // Trừ kho có điều kiện: 0 dòng bị ảnh hưởng => hết hàng
            $affected = Product::whereKey($l['product']->id)
                ->where('stock', '>=', $l['qty'])
                ->decrement('stock', $l['qty']);

            if ($affected === 0) {
                throw new DomainException("Sản phẩm \"{$l['product']->name}\" vừa hết hàng.");
            }
        }

        if ($coupon) {
            OrderCoupon::create([
                'order_id'        => $order->id,
                'coupon_id'       => $coupon->id,
                'coupon_code'     => $coupon->code,
                'discount_amount' => $discount,
            ]);
            $this->coupons->consume($coupon);
        }

        return $order;
    }
}
