<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * User đánh giá sản phẩm. Quy tắc: đã mua + đơn 'completed' + mỗi user 1 review / sản phẩm (UNIQUE user_id+product_id).
 */
class ReviewController extends Controller
{
    use ApiResponse;

    /** POST /products/{product}/reviews — body: rating (1-5), comment? */
    public function store(ReviewRequest $request, Product $product)
    {
        $user = $request->user();

        // Đơn đã hoàn thành có chứa sản phẩm này (lưu order_id làm bằng chứng đã mua)
        $order = Order::where('user_id', $user->id)
            ->where('status', Order::COMPLETED)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest('id')
            ->first();

        abort_unless($order, 403, 'Bạn chưa mua sản phẩm này hoặc đơn hàng chưa hoàn thành.');

        if (Review::where('user_id', $user->id)->where('product_id', $product->id)->exists()) {
            return back()->with('error', 'Bạn đã đánh giá sản phẩm này rồi, hãy chỉnh sửa đánh giá cũ.');
        }

        try {
            Review::create([
                'user_id'    => $user->id,
                'product_id' => $product->id,
                'order_id'   => $order->id,
                'rating'     => $request->validated('rating'),
                'comment'    => $request->validated('comment'),
                'status'     => 'visible',
            ]);
        } catch (UniqueConstraintViolationException) {
            // bấm đúp / 2 tab cùng lúc
            return back()->with('error', 'Bạn đã đánh giá sản phẩm này rồi.');
        }

        return back()->with('success', 'Cảm ơn bạn đã đánh giá sản phẩm!');
    }

    /** PUT /reviews/{review} — chỉ chủ review được sửa; trạng thái visible/hidden do admin quản, user không đổi được */
    public function update(ReviewRequest $request, Review $review)
    {
        abort_unless((int) $review->user_id === (int) $request->user()->id, 403);

        $review->update([
            'rating'  => $request->validated('rating'),
            'comment' => $request->validated('comment'),
        ]);

        return back()->with('success', 'Đã cập nhật đánh giá.');
    }
}
