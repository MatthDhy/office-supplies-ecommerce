<?php

namespace App\Services;

/**
 * [Leader] COD + PayOS/VietQR. Webhook phải idempotent.
 */
class PaymentService
{
    /** Tạo payment pending + gọi PayOS lấy payment_url. */
    public function createPayosPayment(\App\Models\Order $order): \App\Models\Payment
    {
        // 1. provider_order_code: số duy nhất (vd timestamp+id) — cột có UNIQUE
        // 2. Gọi API PayOS tạo link, lưu payment_url
        // 3. Payment::create(status=pending)
        throw new \LogicException('TODO [Leader]: PaymentService::createPayosPayment');
    }

    /** Xử lý PayOS gọi về. */
    public function handleWebhook(array $payload): void
    {
        // 1. Verify chữ ký bằng checksum key
        // 2. DB::transaction + lockForUpdate Payment theo provider_order_code
        // 3. Đã paid -> return (idempotent)
        // 4. Cập nhật payment (transaction_id, paid_at, raw_response) + order (payment_status=paid, status=confirmed)
        throw new \LogicException('TODO [Leader]: PaymentService::handleWebhook');
    }
}
