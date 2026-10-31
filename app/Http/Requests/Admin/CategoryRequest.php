<?php

namespace App\Http\Requests\Admin;

use App\Support\SlugGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $slug = is_string($this->slug) ? trim($this->slug) : '';

        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'slug' => $slug !== '' ? Str::slug($slug) : null,   // chuẩn hóa: "Bút Viết" -> "but-viet"
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'name'        => ['required', 'string', 'max:150'],
            'slug'        => ['nullable', 'string', 'max:180', Rule::unique('categories', 'slug')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /** Dữ liệu đã validate + slug hoàn chỉnh (để trống: tạo mới thì tự sinh, sửa thì giữ slug cũ). */
    public function payload(): array
    {
        $data    = $this->validated();
        $current = $this->route('category');

        $data['slug'] = $data['slug'] ?? $current?->slug ?? SlugGenerator::unique('categories', $data['name']);

        return $data;
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Vui lòng nhập tên danh mục.',
            'name.max'        => 'Tên danh mục tối đa 150 ký tự.',
            'slug.unique'     => 'Slug này đã được dùng.',
            'slug.max'        => 'Slug tối đa 180 ký tự.',
            'description.max' => 'Mô tả tối đa 2000 ký tự.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in'       => 'Trạng thái không hợp lệ.',
        ];
    }
}
