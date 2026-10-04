<?php

namespace App\Services;

/**
 * [Leader] Tạo đơn hàng từ giỏ. Đây là nghiệp vụ nhạy cảm nhất -> luôn DB::transaction.
 */
class OrderService
{
    /** Phí ship (vd: miễn phí từ 300.000đ, còn lại 30.000đ). */
    public function shippingFee(int $subtotal): int
    {
        // 1. Quy tắc đơn giản, có thể hard-code
        throw new \LogicException('TODO [Leader]: OrderService::shippingFee');
    }

    /** Giỏ hàng -> Order + OrderItems. */
    public function createFromCart(\App\Models\User $user, array $data): \App\Models\Order
    {
        // 1. DB::transaction(function () { ...
        // 2. Load lại product từ DB bằng lockForUpdate() (KHÔNG tin giá/số lượng từ client)
        // 3. Kiểm tra stock từng sản phẩm; thiếu -> ném DomainException
        // 4. subtotal = sum(final_price * qty); discount từ CouponService; shipping từ shippingFee; total
        // 5. Order::create([... 'order_code' => Order::generateCode(), 'expires_at' => PayOS ? now()->addMinutes(15) : null])
        // 6. OrderItem::create cho từng dòng: product_name + unit_price = SNAPSHOT tại thời điểm mua
        // 7. Trừ stock (decrement), lưu order_coupons + CouponService::consume nếu có mã
        // 8. COD: sau commit -> CartService->clear(); PayOS: clear khi thanh toán thành công (theo quy tắc nhóm chọn)
        // 9. });
        throw new \LogicException('TODO [Leader]: OrderService::createFromCart');
    }
}
