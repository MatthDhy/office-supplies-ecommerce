<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên B] User đánh giá sản phẩm.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class ReviewController extends Controller
{
    use ApiResponse;

    /** POST /products/{product}/reviews */
    public function store(Request $request, \App\Models\Product $product)
    {
        // 1. Tạo ReviewRequest: rating 1..5, comment nullable|max:1000
        // 2. Tìm đơn của user có chứa product này VÀ status = 'completed' (Order::where(user_id)->where('status','completed')->whereHas('items', fn($q)=>$q->where('product_id',$product->id))->first())
        // 3. Không có -> abort(403) 'Bạn chưa mua sản phẩm này'
        // 4. Đã review rồi (unique user_id+product_id)? -> báo lỗi hoặc chuyển sang update
        // 5. Review::create([... 'order_id' => $order->id])
        // 6. redirect back with success
        abort(501, 'TODO [Thành viên B]: ReviewController@store');
    }

    /** PUT /reviews/{review} */
    public function update(Request $request, \App\Models\Review $review)
    {
        // 1. Chỉ chủ review mới sửa: abort_unless($review->user_id === auth()->id(), 403)
        // 2. validate rating/comment (dùng lại ReviewRequest)
        // 3. $review->update([...])
        // 4. redirect back with success
        abort(501, 'TODO [Thành viên B]: ReviewController@update');
    }
}
