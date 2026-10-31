<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Review;
use Illuminate\Http\Request;

/**
 * Kiểm duyệt đánh giá: ẩn/hiện hoặc xóa review vi phạm.
 * View nhận: index -> $reviews (Paginator; $review->product có thể là sản phẩm đã xóa mềm nhưng vẫn có dữ liệu), $filters
 */
class ReviewController extends Controller
{
    use ApiResponse;

    /** GET /admin/reviews?q=&status=&rating= */
    public function index(Request $request)
    {
        $filters = [
            'q'      => is_string($request->query('q')) ? trim($request->query('q')) : '',
            'status' => in_array($request->query('status'), ['visible', 'hidden'], true) ? $request->query('status') : '',
            'rating' => in_array((int) $request->query('rating'), [1, 2, 3, 4, 5], true) ? (int) $request->query('rating') : null,
        ];
        $like = '%' . addcslashes($filters['q'], '%_\\') . '%';

        $reviews = Review::with([
                'user:id,full_name,email',
                'product' => fn ($q) => $q->withTrashed()->select('id', 'name', 'slug'),
            ])
            ->when($filters['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('comment', 'like', $like)
                ->orWhereHas('product', fn ($p) => $p->withTrashed()->where('name', 'like', $like))
                ->orWhereHas('user', fn ($u) => $u->where('full_name', 'like', $like))))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['rating'], fn ($q, $r) => $q->where('rating', $r))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'filters'));
    }

    /** PATCH /admin/reviews/{review}/toggle — visible <-> hidden */
    public function toggle(Review $review)
    {
        $review->update(['status' => $review->status === 'visible' ? 'hidden' : 'visible']);

        return back()->with('success', $review->status === 'hidden' ? 'Đã ẩn đánh giá.' : 'Đã hiển thị lại đánh giá.');
    }

    /** DELETE /admin/reviews/{review} */
    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('success', 'Đã xóa đánh giá.');
    }
}
