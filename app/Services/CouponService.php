<?php

namespace App\Services;

/**
 * [Leader] Kiểm tra & tính giảm giá. Mọi con số do BACKEND tính.
 */
class CouponService
{
    /** Trả ['coupon' => Coupon, 'discount' => int]. */
    public function validate(string $code, int $subtotal, ?\App\Models\User $user): array
    {
        // 1. Coupon::where('code', strtoupper($code))->first()  -> không có: ném DomainException('Mã không tồn tại')
        // 2. status = active
        // 3. now() nằm trong [starts_at, expires_at]
        // 4. used_count < max_uses (nếu max_uses != null)
        // 5. Số lần user đã dùng (order_coupons join orders) < per_user_limit
        // 6. $subtotal >= minimum_order_amount
        // 7. percent: floor($subtotal*value/100) rồi min với max_discount_amount; fixed: min(value, subtotal)
        throw new \LogicException('TODO [Leader]: CouponService::validate');
    }

    /** Tăng used_count — gọi TRONG transaction tạo đơn. */
    public function consume(\App\Models\Coupon $coupon): void
    {
        // 1. Coupon::whereKey($coupon->id)->lockForUpdate()->first()  // khóa dòng chống 2 người dùng cùng lúc
        // 2. Kiểm tra lại max_uses rồi increment('used_count')
        throw new \LogicException('TODO [Leader]: CouponService::consume');
    }
}
