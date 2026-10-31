<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\OrderCoupon;
use App\Models\User;
use DomainException;

/**
 * Kiểm tra & tính giảm giá. Mọi con số do BACKEND tính, client chỉ gửi mã.
 */
class CouponService
{
    /**
     * Trả ['coupon' => Coupon, 'discount' => int]. Sai điều kiện -> DomainException.
     *
     * @param bool $lock true khi gọi TRONG transaction tạo đơn: khóa dòng coupon (FOR UPDATE)
     *                   để 2 người dùng cùng lúc không vượt quá max_uses / per_user_limit.
     */
    public function validate(string $code, int $subtotal, ?User $user, bool $lock = false): array
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            throw new DomainException('Vui lòng nhập mã giảm giá.');
        }
        if (!$user) {
            throw new DomainException('Vui lòng đăng nhập để sử dụng mã giảm giá.');
        }

        $coupon = Coupon::where('code', $code)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();

        if (!$coupon) {
            throw new DomainException('Mã giảm giá không tồn tại.');
        }
        if ($coupon->status !== 'active') {
            throw new DomainException('Mã giảm giá hiện không khả dụng.');
        }
        if ($coupon->starts_at && now()->lt($coupon->starts_at)) {
            throw new DomainException('Mã giảm giá chưa đến thời gian áp dụng.');
        }
        if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
            throw new DomainException('Mã giảm giá đã hết hạn.');
        }
        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            throw new DomainException('Mã giảm giá đã hết lượt sử dụng.');
        }

        // Đơn đã hủy thì không tính vào số lần user đã dùng. per_user_limit = 0 => không giới hạn.
        if ($coupon->per_user_limit > 0) {
            $used = OrderCoupon::where('coupon_id', $coupon->id)
                ->whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', '!=', 'cancelled'))
                ->count();

            if ($used >= $coupon->per_user_limit) {
                throw new DomainException('Bạn đã dùng hết số lượt cho mã này.');
            }
        }

        if ($subtotal < $coupon->minimum_order_amount) {
            throw new DomainException(
                'Đơn hàng tối thiểu ' . number_format($coupon->minimum_order_amount, 0, ',', '.') . '₫ để dùng mã này.'
            );
        }

        $discount = $this->calculate($coupon, $subtotal);
        if ($discount <= 0) {
            throw new DomainException('Mã giảm giá không áp dụng được cho đơn hàng này.');
        }

        return ['coupon' => $coupon, 'discount' => $discount];
    }

    /** Tăng used_count — gọi TRONG transaction tạo đơn. */
    public function consume(Coupon $coupon): void
    {
        $fresh = Coupon::whereKey($coupon->id)->lockForUpdate()->firstOrFail();

        if ($fresh->max_uses !== null && $fresh->used_count >= $fresh->max_uses) {
            throw new DomainException('Mã giảm giá đã hết lượt sử dụng.');
        }

        $fresh->increment('used_count');
    }

    /** Hoàn lượt dùng khi đơn bị hủy (không để used_count âm). */
    public function release(int $couponId): void
    {
        Coupon::whereKey($couponId)->where('used_count', '>', 0)->decrement('used_count');
    }

    private function calculate(Coupon $coupon, int $subtotal): int
    {
        $discount = match ($coupon->type) {
            'percent' => (int) floor($subtotal * min($coupon->value, 100) / 100),
            'fixed'   => (int) $coupon->value,
            default   => throw new DomainException('Loại mã giảm giá không hợp lệ.'),
        };

        if ($coupon->type === 'percent' && $coupon->max_discount_amount !== null) {
            $discount = min($discount, (int) $coupon->max_discount_amount);
        }

        return max(0, min($discount, $subtotal)); // không bao giờ giảm quá tiền hàng
    }
}
