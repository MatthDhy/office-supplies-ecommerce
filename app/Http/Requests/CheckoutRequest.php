<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'shipping_name'    => is_string($this->shipping_name) ? trim($this->shipping_name) : $this->shipping_name,
            'shipping_phone'   => is_string($this->shipping_phone) ? preg_replace('/\s+/', '', $this->shipping_phone) : $this->shipping_phone,
            'shipping_address' => is_string($this->shipping_address) ? trim($this->shipping_address) : $this->shipping_address,
            'coupon_code'      => is_string($this->coupon_code) && trim($this->coupon_code) !== ''
                ? strtoupper(trim($this->coupon_code)) : null,
        ]);
    }

    /**
     * Cố ý KHÔNG có: giá, tổng tiền, giảm giá, shipping_fee -> mọi con số do backend tính.
     */
    public function rules(): array
    {
        return [
            'shipping_name'    => ['required', 'string', 'max:100'],
            'shipping_phone'   => ['required', 'regex:/^(0|\+84)[0-9]{9}$/'],
            'shipping_address' => ['required', 'string', 'min:10', 'max:500'],
            'payment_method'   => ['required', 'in:cod,payos'],
            'coupon_code'      => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'note'             => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_name.required'    => 'Vui lòng nhập họ tên người nhận.',
            'shipping_name.max'         => 'Họ tên tối đa 100 ký tự.',
            'shipping_phone.required'   => 'Vui lòng nhập số điện thoại.',
            'shipping_phone.regex'      => 'Số điện thoại không hợp lệ (vd: 0901234567).',
            'shipping_address.required' => 'Vui lòng nhập địa chỉ nhận hàng.',
            'shipping_address.min'      => 'Địa chỉ quá ngắn, vui lòng ghi đầy đủ (số nhà, đường, phường/xã, quận/huyện, tỉnh/thành).',
            'shipping_address.max'      => 'Địa chỉ tối đa 500 ký tự.',
            'payment_method.required'   => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in'         => 'Phương thức thanh toán không hợp lệ.',
            'coupon_code.regex'         => 'Mã giảm giá chỉ gồm chữ, số, gạch ngang và gạch dưới.',
            'note.max'                  => 'Ghi chú tối đa 500 ký tự.',
        ];
    }
}
