<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use DomainException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * PayOS / VietQR. COD không cần bản ghi payment (admin đánh dấu paid khi giao xong).
 * NGUYÊN TẮC: chỉ webhook (đã verify chữ ký) hoặc API tra cứu server-to-server mới được đánh dấu "paid".
 * Tham số trên URL return/cancel do trình duyệt gửi -> KHÔNG BAO GIỜ tin.
 */
class PaymentService
{
    /** Tạo payment pending + gọi PayOS lấy payment_url. Gọi lại nhiều lần vẫn dùng link cũ nếu còn hạn. */
    public function createPayosPayment(Order $order): Payment
    {
        $this->assertConfigured();

        $existing = $order->payments()->where('payment_method', 'payos')->where('status', 'pending')->latest('id')->first();
        if ($existing && $existing->payment_url) {
            return $existing;
        }

        $providerCode = $this->generateProviderCode();
        $amount       = (int) $order->total_amount;
        $description  = substr($order->order_code, 0, 25);          // PayOS giới hạn 25 ký tự
        $returnUrl    = route('payment.return');
        $cancelUrl    = route('payment.cancel');

        $signature = hash_hmac(
            'sha256',
            "amount={$amount}&cancelUrl={$cancelUrl}&description={$description}&orderCode={$providerCode}&returnUrl={$returnUrl}",
            config('services.payos.checksum_key')
        );

        $response = $this->client()->post($this->baseUrl() . '/v2/payment-requests', [
            'orderCode'   => $providerCode,
            'amount'      => $amount,
            'description' => $description,
            'returnUrl'   => $returnUrl,
            'cancelUrl'   => $cancelUrl,
            'expiredAt'   => optional($order->expires_at)->timestamp ?? now()->addMinutes(OrderService::PAYOS_EXPIRE_MINUTES)->timestamp,
            'signature'   => $signature,
        ]);

        $checkoutUrl = $response->json('data.checkoutUrl');
        if (!$response->successful() || $response->json('code') !== '00' || !$checkoutUrl) {
            Log::error('PayOS tạo link thất bại', ['order' => $order->order_code, 'status' => $response->status(), 'body' => $response->json()]);
            throw new DomainException('Không tạo được liên kết thanh toán PayOS.');
        }

        return Payment::create([
            'order_id'            => $order->id,
            'provider_order_code' => $providerCode,
            'payment_method'      => 'payos',
            'amount'              => $amount,
            'status'              => 'pending',
            'payment_url'         => $checkoutUrl,
        ]);
    }

    /**
     * Xử lý PayOS gọi về.
     * @throws InvalidArgumentException khi chữ ký sai (controller trả 400)
     */
    public function handleWebhook(array $payload): void
    {
        $data      = $payload['data'] ?? null;
        $signature = $payload['signature'] ?? null;

        if (!is_array($data) || !is_string($signature) || !$this->verifySignature($data, $signature)) {
            throw new InvalidArgumentException('Chữ ký webhook không hợp lệ.');
        }

        // Chỉ xử lý giao dịch thành công
        if (($payload['success'] ?? false) !== true || ($data['code'] ?? '') !== '00') {
            return;
        }

        $this->markPaid(
            (int) ($data['orderCode'] ?? 0),
            (int) ($data['amount'] ?? 0),
            $data['reference'] ?? $data['paymentLinkId'] ?? null,
            $payload
        );
    }

    /**
     * Hỏi lại PayOS (server-to-server, có API key) trạng thái link thanh toán.
     * Dùng ở trang return/cancel để không phụ thuộc webhook đến sớm hay muộn (và để test local khi chưa có ngrok).
     */
    public function syncStatus(Payment $payment): void
    {
        if ($payment->status === 'paid' || !$payment->provider_order_code) {
            return;
        }

        try {
            $this->assertConfigured();
            $res = $this->client()->get($this->baseUrl() . '/v2/payment-requests/' . $payment->provider_order_code);

            if (!$res->successful() || $res->json('code') !== '00' || $res->json('data.status') !== 'PAID') {
                return;
            }

            $this->markPaid(
                (int) $payment->provider_order_code,
                (int) ($res->json('data.amountPaid') ?? $res->json('data.amount') ?? 0),
                $res->json('data.transactions.0.reference') ?? $res->json('data.id'),
                (array) $res->json()
            );
        } catch (Throwable $e) {
            report($e); // lỗi mạng: bỏ qua, webhook sẽ cập nhật sau
        }
    }

    /** Hủy link thanh toán bên PayOS (best effort, lỗi cũng không sao vì link có hạn). */
    public function cancelPayosLink(Payment $payment, string $reason = 'Khách hủy thanh toán'): void
    {
        try {
            $this->client()->post(
                $this->baseUrl() . '/v2/payment-requests/' . $payment->provider_order_code . '/cancel',
                ['cancellationReason' => $reason]
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Chữ ký webhook: sort key A-Z, nối "k=v&k=v", HMAC-SHA256 bằng checksum key. */
    public function verifySignature(array $data, string $signature): bool
    {
        ksort($data, SORT_STRING);

        $pairs = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif ($value === null || $value === 'null' || $value === 'undefined') {
                $value = '';
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $pairs[] = $key . '=' . $value;
        }

        $expected = hash_hmac('sha256', implode('&', $pairs), (string) config('services.payos.checksum_key'));

        return hash_equals($expected, $signature);
    }

    /* ---------------- nội bộ ---------------- */

    /** Đánh dấu đã thanh toán. IDEMPOTENT: gọi bao nhiêu lần cũng chỉ xử lý 1 lần. */
    private function markPaid(int $orderCode, int $amount, ?string $transactionId, array $raw): void
    {
        DB::transaction(function () use ($orderCode, $amount, $transactionId, $raw) {
            $peek = Payment::where('provider_order_code', $orderCode)->first();
            if (!$peek) {
                Log::warning('PayOS: không tìm thấy payment', ['orderCode' => $orderCode]); // vd. webhook test của PayOS
                return;
            }

            // Khóa order TRƯỚC rồi mới khóa payment (cùng thứ tự với OrderService::cancel -> không deadlock)
            $order   = Order::whereKey($peek->order_id)->lockForUpdate()->first();
            $payment = Payment::whereKey($peek->id)->lockForUpdate()->first();

            if ($payment->status === 'paid') {
                return; // webhook gửi lại -> bỏ qua
            }
            if ($amount !== (int) $payment->amount) {
                Log::error('PayOS: số tiền không khớp', ['orderCode' => $orderCode, 'paid' => $amount, 'expected' => $payment->amount]);
                return;
            }

            $payment->update([
                'status'         => 'paid',
                'transaction_id' => $transactionId,
                'paid_at'        => now(),
                'raw_response'   => json_encode($raw, JSON_UNESCAPED_UNICODE),
            ]);

            if ($order->status === Order::CANCELLED) {
                // Khách trả tiền sau khi đơn đã hết hạn/hủy: ghi nhận tiền, KHÔNG hồi sinh đơn -> admin hoàn tiền thủ công
                $order->update(['payment_status' => 'paid']);
                Log::warning('PayOS: thanh toán cho đơn đã hủy, cần hoàn tiền', ['order' => $order->order_code]);
                return;
            }

            $order->update([
                'payment_status' => 'paid',
                'status'         => $order->status === Order::PENDING ? Order::CONFIRMED : $order->status,
                'expires_at'     => null,
            ]);
        }, 3);
    }

    private function generateProviderCode(): int
    {
        // 12 chữ số ngày giờ + 3 số ngẫu nhiên = 15 chữ số (< 2^53 theo giới hạn PayOS). Cột có UNIQUE chặn trùng.
        return (int) (now()->format('ymdHis') . random_int(100, 999));
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'x-client-id' => config('services.payos.client_id'),
            'x-api-key'   => config('services.payos.api_key'),
        ])->acceptJson()->timeout(15);
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.payos.base_url'), '/');
    }

    private function assertConfigured(): void
    {
        foreach (['client_id', 'api_key', 'checksum_key'] as $key) {
            if (!config("services.payos.$key")) {
                throw new DomainException('Chưa cấu hình PayOS (kiểm tra PAYOS_* trong .env).');
            }
        }
    }
}
