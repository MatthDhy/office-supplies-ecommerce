<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Coupon;
use Illuminate\Http\Request;

/**
 * Admin CRUD mã giảm giá. used_count do hệ thống tự tăng/giảm (OrderService), admin không sửa tay.
 * View nhận: index -> $coupons (Paginator), $filters | create | edit -> $coupon
 */
class CouponController extends Controller
{
    use ApiResponse;

    /** GET /admin/coupons?q=&status= */
    public function index(Request $request)
    {
        $filters = [
            'q'      => is_string($request->query('q')) ? trim($request->query('q')) : '',
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '',
        ];

        $coupons = Coupon::query()
            ->when($filters['q'] !== '', fn ($q) => $q->where('code', 'like', '%' . addcslashes($filters['q'], '%_\\') . '%'))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.coupons.index', compact('coupons', 'filters'));
    }

    /** GET /admin/coupons/create */
    public function create()
    {
        return view('admin.coupons.create');
    }

    /** POST /admin/coupons */
    public function store(CouponRequest $request)
    {
        Coupon::create($request->payload());

        return redirect()->route('admin.coupons.index')->with('success', 'Đã tạo mã giảm giá.');
    }

    /** GET /admin/coupons/{coupon}/edit */
    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    /** PUT /admin/coupons/{coupon} */
    public function update(CouponRequest $request, Coupon $coupon)
    {
        $coupon->update($request->payload());   // đơn cũ đã lưu snapshot mã/giảm trong order_coupons nên không bị ảnh hưởng

        return redirect()->route('admin.coupons.index')->with('success', 'Đã cập nhật mã giảm giá.');
    }

    /** DELETE /admin/coupons/{coupon} — đã từng dùng thì chỉ chuyển inactive (giữ lịch sử order_coupons) */
    public function destroy(Coupon $coupon)
    {
        if ($coupon->used_count > 0 || $coupon->orderCoupons()->exists()) {
            $coupon->update(['status' => 'inactive']);

            return redirect()->route('admin.coupons.index')
                ->with('success', 'Mã đã từng được sử dụng nên không xóa hẳn — đã chuyển sang inactive.');
        }

        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Đã xóa mã giảm giá.');
    }
}
