<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên B] Danh sách yêu thích.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class WishlistController extends Controller
{
    use ApiResponse;

    /** GET /wishlist */
    public function index(Request $request)
    {
        // 1. $request->user()->wishlistProducts()->active()->paginate(12)
        // 2. return view('front.wishlist')
        abort(501, 'TODO [Thành viên B]: WishlistController@index');
    }

    /** POST /wishlist/toggle  (AJAX, JSON) */
    public function toggle(Request $request)
    {
        // 1. validate product_id (exists:products,id)
        // 2. $result = $request->user()->wishlistProducts()->toggle($productId)  // đã có -> gỡ, chưa có -> thêm
        // 3. added = count($result['attached']) > 0
        // 4. return $this->success(['added' => $added], ...)
        abort(501, 'TODO [Thành viên B]: WishlistController@toggle');
    }

    /** POST /wishlist/move-to-cart  (AJAX) */
    public function moveToCart(Request $request)
    {
        // 1. validate product_id
        // 2. app(\App\Services\CartService::class)->add($productId)  // try/catch DomainException như CartController
        // 3. detach khỏi wishlist
        // 4. return $this->success(['count' => cart count])
        abort(501, 'TODO [Thành viên B]: WishlistController@moveToCart');
    }
}
