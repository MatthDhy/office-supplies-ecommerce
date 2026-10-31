<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        // CouponService luôn so khớp mã IN HOA -> lưu IN HOA
        $this->merge([
            'code' => is_string($this->code) ? strtoupper(trim($this->code)) : $this->code,
        ]);
    }

    public function rules(): array
    {
        $id      = $this->route('coupon')?->id;
        $percent = $this->input('type') === 'percent';

        return [
            'code'                 => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($id)],
            'type'                 => ['required', Rule::in(['percent', 'fixed'])],
            'value'                => ['required', 'integer', 'min:1', $percent ? 'max:100' : 'max:100000000'],
            'minimum_order_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'max_discount_amount'  => ['nullable', 'integer', 'min:1', 'max:100000000'],   // chỉ có tác dụng với percent
            'max_uses'             => ['nullable', 'integer', 'min:1', 'max:10000000'],     // trống = không giới hạn
            'per_user_limit'       => ['nullable', 'integer', 'min:0', 'max:1000'],         // 0 = không giới hạn mỗi user
            'starts_at'            => ['nullable', 'date'],
            'expires_at'           => array_merge(['nullable', 'date'], $this->filled('starts_at') ? ['after:starts_at'] : []),
            'status'               => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /** Dữ liệu cột DB (không cho sửa used_count). */
    public function payload(): array
    {
        $data = $this->validated();

        $data['minimum_order_amount'] = $data['minimum_order_amount'] ?? 0;
        $data['per_user_limit']       = $data['per_user_limit'] ?? 1;

        if ($data['type'] === 'fixed') {
            $data['max_discount_amount'] = null;   // trần giảm chỉ áp dụng cho loại phần trăm
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'code.required'                => 'Vui lòng nhập mã giảm giá.',
            'code.regex'                   => 'Mã chỉ gồm chữ, số, gạch ngang và gạch dưới.',
            'code.unique'                  => 'Mã giảm giá này đã tồn tại.',
            'type.required'                => 'Vui lòng chọn loại giảm giá.',
            'type.in'                      => 'Loại giảm giá không hợp lệ.',
            'value.required'               => 'Vui lòng nhập mức giảm.',
            'value.integer'                => 'Mức giảm phải là số nguyên.',
            'value.min'                    => 'Mức giảm phải lớn hơn 0.',
            'value.max'                    => 'Mức giảm không hợp lệ (phần trăm tối đa 100).',
            'minimum_order_amount.integer' => 'Đơn tối thiểu phải là số nguyên.',
            'max_discount_amount.integer'  => 'Giảm tối đa phải là số nguyên.',
            'max_uses.integer'             => 'Số lượt dùng phải là số nguyên.',
            'max_uses.min'                 => 'Số lượt dùng tối thiểu là 1 (để trống nếu không giới hạn).',
            'per_user_limit.integer'       => 'Giới hạn mỗi người phải là số nguyên.',
            'starts_at.date'               => 'Ngày bắt đầu không hợp lệ.',
            'expires_at.date'              => 'Ngày kết thúc không hợp lệ.',
            'expires_at.after'             => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'status.required'              => 'Vui lòng chọn trạng thái.',
            'status.in'                    => 'Trạng thái không hợp lệ.',
        ];
    }
}
