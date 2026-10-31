<?php

namespace App\Http\Requests\Admin;

use App\Support\SlugGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $slug  = is_string($this->slug) ? trim($this->slug) : '';
        $brand = is_string($this->brand) ? trim($this->brand) : '';

        $this->merge([
            'name'        => is_string($this->name) ? trim($this->name) : $this->name,
            'slug'        => $slug !== '' ? Str::slug($slug) : null,
            'brand'       => $brand !== '' ? $brand : null,
            'is_featured' => $this->boolean('is_featured'),   // checkbox không tick -> false
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('product')?->id;

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name'        => ['required', 'string', 'max:200'],
            'slug'        => ['nullable', 'string', 'max:220', Rule::unique('products', 'slug')->ignore($id)],
            'description' => ['nullable', 'string', 'max:10000'],
            'brand'       => ['nullable', 'string', 'max:100'],
            'price'       => ['required', 'integer', 'min:1', 'max:100000000'],
            'sale_price'  => ['nullable', 'integer', 'min:1', 'lt:price'],
            'stock'       => ['required', 'integer', 'min:0', 'max:1000000'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_featured' => ['boolean'],
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    /** Dữ liệu cột DB (đã bỏ 'image' — controller tự xử lý upload). */
    public function payload(): array
    {
        $data    = Arr::except($this->validated(), ['image']);
        $current = $this->route('product');

        $data['slug'] = $data['slug'] ?? $current?->slug ?? SlugGenerator::unique('products', $data['name'], null, 220);

        return $data;
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists'   => 'Danh mục không tồn tại.',
            'name.required'        => 'Vui lòng nhập tên sản phẩm.',
            'name.max'             => 'Tên sản phẩm tối đa 200 ký tự.',
            'slug.unique'          => 'Slug này đã được dùng (kể cả sản phẩm đã xóa).',
            'price.required'       => 'Vui lòng nhập giá.',
            'price.integer'        => 'Giá phải là số nguyên (VNĐ).',
            'price.min'            => 'Giá phải lớn hơn 0.',
            'price.max'            => 'Giá quá lớn.',
            'sale_price.integer'   => 'Giá khuyến mãi phải là số nguyên (VNĐ).',
            'sale_price.min'       => 'Giá khuyến mãi phải lớn hơn 0.',
            'sale_price.lt'        => 'Giá khuyến mãi phải nhỏ hơn giá gốc.',
            'stock.required'       => 'Vui lòng nhập tồn kho.',
            'stock.integer'        => 'Tồn kho phải là số nguyên.',
            'stock.min'            => 'Tồn kho không được âm.',
            'image.image'          => 'File tải lên phải là hình ảnh.',
            'image.mimes'          => 'Ảnh chỉ nhận jpg, jpeg, png, webp.',
            'image.max'            => 'Ảnh tối đa 2MB.',
            'status.required'      => 'Vui lòng chọn trạng thái.',
            'status.in'            => 'Trạng thái không hợp lệ.',
        ];
    }
}
