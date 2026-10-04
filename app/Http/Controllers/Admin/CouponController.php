<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên C] Admin CRUD coupons.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class CouponController extends Controller
{
    use ApiResponse;

    /** GET /admin/coupons */
    public function index(Request $request)
    {
        // 1. Paginate + ô tìm kiếm + lọc status
        // 2. return view('admin.coupons.index')
        abort(501, 'TODO [Thành viên C]: CouponController@index');
    }

    /** GET /admin/coupons/create */
    public function create()
    {
        // 1. return view('admin.coupons.create')
        abort(501, 'TODO [Thành viên C]: CouponController@create');
    }

    /** POST /admin/coupons */
    public function store(Request $request)
    {
        // 1. Tạo FormRequest riêng (validate + unique slug)
        // 2. Str::slug($name) nếu slug để trống
        // 3. Tạo bản ghi
        // 4. redirect index with success
        abort(501, 'TODO [Thành viên C]: CouponController@store');
    }

    /** GET /admin/coupons/{id}/edit */
    public function edit(\App\Models\Coupon $coupon)
    {
        // 1. return view('admin.coupons.edit')
        abort(501, 'TODO [Thành viên C]: CouponController@edit');
    }

    /** PUT /admin/coupons/{id} */
    public function update(Request $request, \App\Models\Coupon $coupon)
    {
        // 1. Validate (unique bỏ qua chính nó: Rule::unique(...)->ignore($coupon->id))
        // 2. Cập nhật (nếu thay ảnh thì xóa ảnh cũ)
        // 3. redirect index with success
        abort(501, 'TODO [Thành viên C]: CouponController@update');
    }

    /** DELETE /admin/coupons/{id} */
    public function destroy(\App\Models\Coupon $coupon)
    {
        // 1. Coupon đã dùng (used_count>0) -> không xóa, chuyển status=inactive
        abort(501, 'TODO [Thành viên C]: CouponController@destroy');
    }
}
