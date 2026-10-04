<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên C] Quản lý đánh giá vi phạm.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class ReviewController extends Controller
{
    use ApiResponse;

    /** GET /admin/reviews */
    public function index(Request $request)
    {
        // 1. Review::with('user','product')->latest()->paginate(15)
        abort(501, 'TODO [Thành viên C]: ReviewController@index');
    }

    /** PATCH /admin/reviews/{review}/toggle */
    public function toggle(\App\Models\Review $review)
    {
        // 1. visible <-> hidden
        abort(501, 'TODO [Thành viên C]: ReviewController@toggle');
    }

    /** DELETE /admin/reviews/{review} */
    public function destroy(\App\Models\Review $review)
    {
        // 1. $review->delete()
        abort(501, 'TODO [Thành viên C]: ReviewController@destroy');
    }
}
