<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên C] Admin CRUD categories.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class CategoryController extends Controller
{
    use ApiResponse;

    /** GET /admin/categories */
    public function index(Request $request)
    {
        // 1. Paginate + ô tìm kiếm + lọc status
        // 2. return view('admin.categories.index')
        abort(501, 'TODO [Thành viên C]: CategoryController@index');
    }

    /** GET /admin/categories/create */
    public function create()
    {
        // 1. return view('admin.categories.create')
        abort(501, 'TODO [Thành viên C]: CategoryController@create');
    }

    /** POST /admin/categories */
    public function store(Request $request)
    {
        // 1. Tạo FormRequest riêng (validate + unique slug)
        // 2. Str::slug($name) nếu slug để trống
        // 3. Tạo bản ghi
        // 4. redirect index with success
        abort(501, 'TODO [Thành viên C]: CategoryController@store');
    }

    /** GET /admin/categories/{id}/edit */
    public function edit(\App\Models\Category $category)
    {
        // 1. return view('admin.categories.edit')
        abort(501, 'TODO [Thành viên C]: CategoryController@edit');
    }

    /** PUT /admin/categories/{id} */
    public function update(Request $request, \App\Models\Category $category)
    {
        // 1. Validate (unique bỏ qua chính nó: Rule::unique(...)->ignore($category->id))
        // 2. Cập nhật (nếu thay ảnh thì xóa ảnh cũ)
        // 3. redirect index with success
        abort(501, 'TODO [Thành viên C]: CategoryController@update');
    }

    /** DELETE /admin/categories/{id} */
    public function destroy(\App\Models\Category $category)
    {
        // 1. Còn product tham chiếu -> KHÔNG xóa, báo lỗi (hoặc chuyển status=inactive)
        abort(501, 'TODO [Thành viên C]: CategoryController@destroy');
    }
}
