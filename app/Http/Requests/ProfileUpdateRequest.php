<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Cố ý KHÔNG có: email, role, status -> user không tự đổi được.
     * Muốn đổi mật khẩu: phải gửi current_password đúng + password mới (+ password_confirmation).
     */
    public function rules(): array
    {
        return [
            'full_name'        => ['required', 'string', 'max:100'],
            'phone'            => ['nullable', 'regex:/^(0|\+84)[0-9]{9}$/'],
            'avatar'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password'         => ['nullable', 'confirmed', Password::min(6)],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'                => 'Vui lòng nhập họ tên.',
            'full_name.max'                     => 'Họ tên tối đa 100 ký tự.',
            'phone.regex'                       => 'Số điện thoại không hợp lệ (vd: 0901234567).',
            'avatar.image'                      => 'Avatar phải là hình ảnh.',
            'avatar.mimes'                      => 'Avatar chỉ nhận jpg, jpeg, png, webp.',
            'avatar.max'                        => 'Avatar tối đa 2MB.',
            'current_password.required_with'    => 'Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu.',
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.confirmed'                => 'Mật khẩu xác nhận không khớp.',
            'password.min'                      => 'Mật khẩu tối thiểu 6 ký tự.',
        ];
    }
}
