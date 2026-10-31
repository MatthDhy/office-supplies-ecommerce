<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Services\CartService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Danh sách yêu thích. Bảng wishlists là pivot (user_id, product_id, created_at) -> dùng quan hệ
 * $user->wishlistProducts(), không có model Wishlist.
 */
class WishlistController extends Controller
{
    use ApiResponse;

    public function __construct(private CartService $cart) {}

    /** GET /wishlist — view front.wishlist nhận $products (Paginator) */
    public function index(Request $request)
    {
        $products = $request->user()->wishlistProducts()
            ->active()
            ->with('category:id,name,slug')
            ->orderByDesc('wishlists.created_at')
            ->paginate(12);

        return view('front.wishlist', compact('products'));
    }

    /** POST /wishlist/toggle  (AJAX) — body: product_id. Trả data: {added: bool, count: int} */
    public function toggle(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'integer']]);

        $user      = $request->user();
        $productId = (int) $data['product_id'];
        $relation  = $user->wishlistProducts();

        if ($relation->where('products.id', $productId)->exists()) {
            // Đã có -> gỡ (cho phép gỡ cả khi sản phẩm đã ngừng bán)
            $user->wishlistProducts()->detach($productId);
            $added = false;
        } else {
            // Chưa có -> chỉ cho thêm sản phẩm đang bán
            $product = Product::active()->find($productId);
            if (!$product) {
                return $this->fail('Sản phẩm không tồn tại hoặc đã ngừng bán.', 404);
            }
            // syncWithoutDetaching: bấm đúp cũng không bị lỗi trùng; pivot không có updated_at nên set created_at thủ công
            $user->wishlistProducts()->syncWithoutDetaching([$product->id => ['created_at' => now()]]);
            $added = true;
        }

        return $this->success(
            ['added' => $added, 'count' => $user->wishlistProducts()->count()],
            $added ? 'Đã thêm vào danh sách yêu thích' : 'Đã gỡ khỏi danh sách yêu thích'
        );
    }

    /** POST /wishlist/move-to-cart  (AJAX) — body: product_id. Trả data: {count: số trong giỏ, wishlist_count} */
    public function moveToCart(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'integer']]);

        try {
            $this->cart->add((int) $data['product_id']);   // kiểm tra tồn tại / active / tồn kho giống nút "Thêm vào giỏ"
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        $user = $request->user();
        $user->wishlistProducts()->detach((int) $data['product_id']);

        return $this->success([
            'count'          => $this->cart->count(),
            'wishlist_count' => $user->wishlistProducts()->count(),
        ], 'Đã chuyển vào giỏ hàng');
    }
}
