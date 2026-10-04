<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên C] Admin CRUD products.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class ProductController extends Controller
{
    use ApiResponse;

    /** GET /admin/products */
    public function index(Request $request)
    {
        // 1. Paginate + ô tìm kiếm + lọc status
        // 2. return view('admin.products.index')
        abort(501, 'TODO [Thành viên C]: ProductController@index');
    }

    /** GET /admin/products/create */
    public function create()
    {
        // 1. return view('admin.products.create')
        abort(501, 'TODO [Thành viên C]: ProductController@create');
    }

    /** POST /admin/products */
    public function store(Request $request)
    {
        // 1. Tạo FormRequest riêng (validate + unique slug)
        // 2. Str::slug($name) nếu slug để trống
        // 3. Upload ảnh (nếu có): $request->file('image')->store('products','public')
        // 4. redirect index with success
        abort(501, 'TODO [Thành viên C]: ProductController@store');
    }

    /** GET /admin/products/{id}/edit */
    public function edit(\App\Models\Product $product)
    {
        // 1. return view('admin.products.edit')
        abort(501, 'TODO [Thành viên C]: ProductController@edit');
    }

    /** PUT /admin/products/{id} */
    public function update(Request $request, \App\Models\Product $product)
    {
        // 1. Validate (unique bỏ qua chính nó: Rule::unique(...)->ignore($product->id))
        // 2. Cập nhật (nếu thay ảnh thì xóa ảnh cũ)
        // 3. redirect index with success
        abort(501, 'TODO [Thành viên C]: ProductController@update');
    }

    /** DELETE /admin/products/{id} */
    public function destroy(\App\Models\Product $product)
    {
        // 1. Dùng SoftDeletes ($product->delete()) vì order_items còn tham chiếu product
        abort(501, 'TODO [Thành viên C]: ProductController@destroy');
    }
}
