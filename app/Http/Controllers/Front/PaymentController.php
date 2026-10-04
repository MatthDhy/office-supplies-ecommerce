<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Leader] PayOS: return/cancel redirect + webhook (idempotent).
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class PaymentController extends Controller
{
    use ApiResponse;

    /** GET /payment/return */
    public function payosReturn(Request $request)
    {
        // 1. Đọc orderCode từ query, tìm payment
        // 2. KHÔNG tin query để đánh dấu paid — chỉ webhook mới được cập nhật
        // 3. redirect orders.show
        abort(501, 'TODO [Leader]: PaymentController@payosReturn');
    }

    /** GET /payment/cancel */
    public function payosCancel(Request $request)
    {
        // 1. Đánh dấu hủy/failed nếu cần, báo user
        // 2. redirect orders.show
        abort(501, 'TODO [Leader]: PaymentController@payosCancel');
    }

    /** POST /payment/payos/webhook */
    public function webhook(Request $request)
    {
        // 1. PaymentService::handleWebhook($request->all())
        // 2. Verify chữ ký -> sai trả 400
        // 3. DB::transaction + lockForUpdate payment
        // 4. Nếu payment đã paid (hoặc transaction_id đã có) -> trả 200 và DỪNG (idempotent)
        // 5. Update payment paid + order payment_status=paid, status=confirmed
        abort(501, 'TODO [Leader]: PaymentController@webhook');
    }
}
