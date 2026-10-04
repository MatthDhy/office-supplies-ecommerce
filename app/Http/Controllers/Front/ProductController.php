<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * [Thành viên B] Danh sách + tìm kiếm + lọc + sắp xếp (AJAX) và trang chi tiết sản phẩm.
 *
 * BẢN KHUNG: mỗi method có sẵn ĐƯỜNG ĐI các bước cần làm (đọc theo thứ tự 1 -> n).
 * Làm xong thì xóa dòng abort(501) và viết code thật.
 */
class ProductController extends Controller
{
    use ApiResponse;

    /** GET /products  (?q=&category=&brand=&min_price=&max_price=&sort=) */
    public function index(Request $request)
    {
        // 1. Đọc query: q, category (slug), brand, min_price, max_price, sort
        // 2. Bắt đầu: Product::active()->with('category')
        // 3. Áp từng điều kiện bằng ->when($request->q, fn($q,$v)=>$q->where('name','like',"%$v%")) ...
        // 4. Lọc giá theo CỘT price (hoặc sale_price nếu muốn chuẩn hơn)
        // 5. sort: price_asc | price_desc | newest (mặc định) | bestseller (withSum order_items.quantity)
        // 6. ->paginate(12)->withQueryString()
        // 7. Nếu $request->ajax(): trả view('front.products._grid') (HTML) hoặc JSON; ngược lại trả view('front.products.index')
        // 8. Dùng lại partial 'partials.product-card' cho từng sản phẩm
        abort(501, 'TODO [Thành viên B]: ProductController@index');
    }

    /** GET /products/{slug} */
    public function show(\App\Models\Product $product)
    {
        // 1. Nếu $product->status !== 'active' -> abort(404)
        // 2. Load: category, reviews đang visible (kèm user), avg rating (->reviews()->visible()->avg('rating'))
        // 3. Kiểm tra sản phẩm đã nằm trong wishlist của user chưa (để tô tim)
        // 4. Kiểm tra user có được review không (đã mua + đơn completed + chưa review)
        // 5. Lấy 4 sản phẩm cùng danh mục để gợi ý
        // 6. return view('front.products.show', compact(...))
        abort(501, 'TODO [Thành viên B]: ProductController@show');
    }
}
