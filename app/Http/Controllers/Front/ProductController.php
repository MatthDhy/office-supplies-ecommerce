<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Danh sách + tìm kiếm + lọc + sắp xếp (AJAX) và trang chi tiết sản phẩm.
 *
 * LUỒNG index:  GET /products?q=&category=&brand=&min_price=&max_price=&sort=&page=
 *   - Request thường  -> view('front.products.index')   (trang đầy đủ)
 *   - Request AJAX    -> JSON {success, data:{html, total}}  (html = view('front.products._grid'))
 *
 * View nhận:
 *   index  : $products (Paginator), $categories, $brands, $filters (mảng bộ lọc đang chọn)
 *   _grid  : $products  (nên tự có $products->links() bên trong để phân trang cũng chạy AJAX)
 *   show   : xem phần show() bên dưới
 */
class ProductController extends Controller
{
    use ApiResponse;

    private const SORTS = ['newest', 'price_asc', 'price_desc', 'bestseller'];

    /** Giá khách thực sự trả (khớp Product::getFinalPriceAttribute) — dùng để lọc/sắp xếp theo giá ở SQL. */
    private const FINAL_PRICE_SQL = 'CASE WHEN products.sale_price IS NOT NULL AND products.sale_price < products.price THEN products.sale_price ELSE products.price END';

    /** GET /products */
    public function index(Request $request)
    {
        // Chuẩn hóa query: giá trị sai (mảng, chữ, số âm...) thì bỏ qua thay vì báo lỗi
        $text = fn (string $key): string => is_string($v = $request->query($key)) ? trim($v) : '';
        $int  = function (string $key): ?int {
            $v = filter_var(request()->query($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            return $v === false ? null : $v;
        };

        $filters = [
            'q'         => mb_substr($text('q'), 0, 100),
            'category'  => $text('category'),
            'brand'     => $text('brand'),
            'min_price' => $int('min_price'),
            'max_price' => $int('max_price'),
            'sort'      => in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest',
        ];

        $like = '%' . addcslashes($filters['q'], '%_\\') . '%';

        $query = Product::active()
            ->with('category:id,name,slug')
            ->whereHas('category', fn ($c) => $c->active())
            ->when($filters['q'] !== '', fn ($b) => $b->where(
                fn ($w) => $w->where('name', 'like', $like)->orWhere('brand', 'like', $like)
            ))
            ->when($filters['category'] !== '', fn ($b) => $b->whereHas('category', fn ($c) => $c->where('slug', $filters['category'])))
            ->when($filters['brand'] !== '', fn ($b) => $b->where('brand', $filters['brand']))
            ->when($filters['min_price'] !== null, fn ($b) => $b->whereRaw('(' . self::FINAL_PRICE_SQL . ') >= ?', [$filters['min_price']]))
            ->when($filters['max_price'] !== null, fn ($b) => $b->whereRaw('(' . self::FINAL_PRICE_SQL . ') <= ?', [$filters['max_price']]));

        switch ($filters['sort']) {
            case 'price_asc':
                $query->orderByRaw(self::FINAL_PRICE_SQL . ' ASC')->orderByDesc('products.id');
                break;
            case 'price_desc':
                $query->orderByRaw(self::FINAL_PRICE_SQL . ' DESC')->orderByDesc('products.id');
                break;
            case 'bestseller':
                // Tổng số lượng đã bán, không tính đơn đã hủy
                $query->withSum(
                    ['orderItems as sold_qty' => fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', Order::CANCELLED))],
                    'quantity'
                )->orderByDesc('sold_qty')->orderByDesc('products.id');
                break;
            default: // newest
                $query->orderByDesc('products.created_at')->orderByDesc('products.id');
        }

        $products = $query->paginate(12)->withQueryString();

        if ($request->ajax()) {
            return $this->success([
                'html'  => view('front.products._grid', compact('products'))->render(),
                'total' => $products->total(),
            ]);
        }

        return view('front.products.index', [
            'products'   => $products,
            'filters'    => $filters,
            'categories' => Category::active()->orderBy('name')->get(['id', 'name', 'slug']),
            'brands'     => Product::active()->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand'),
        ]);
    }

    /**
     * GET /products/{slug}
     *
     * View front.products.show nhận:
     *   $product, $reviews (Paginator review đang visible, kèm user), $avgRating (float|null), $reviewCount,
     *   $inWishlist (bool), $canReview (bool - được viết review mới), $myReview (Review|null - review cũ của mình để sửa),
     *   $related (4 sản phẩm cùng danh mục)
     */
    public function show(Request $request, Product $product)
    {
        abort_unless($product->status === 'active', 404);

        $product->load('category:id,name,slug');

        $visible = $product->reviews()->visible();
        $avg     = $visible->avg('rating');

        $reviews = $product->reviews()->visible()
            ->with('user:id,full_name,avatar')
            ->latest()
            ->paginate(5, ['*'], 'review_page')
            ->withQueryString();

        $user       = $request->user();
        $inWishlist = false;
        $canReview  = false;
        $myReview   = null;

        if ($user) {
            $inWishlist = $user->wishlistProducts()->where('products.id', $product->id)->exists();

            // Review của chính mình (kể cả đã bị admin ẩn) -> chỉ được sửa, không tạo thêm (UNIQUE user_id+product_id)
            $myReview = $product->reviews()->where('user_id', $user->id)->first();

            $purchased = Order::where('user_id', $user->id)
                ->where('status', Order::COMPLETED)
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->exists();

            $canReview = $purchased && !$myReview;
        }

        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('front.products.show', [
            'product'     => $product,
            'reviews'     => $reviews,
            'avgRating'   => $avg !== null ? round((float) $avg, 1) : null,
            'reviewCount' => $reviews->total(),
            'inWishlist'  => $inWishlist,
            'canReview'   => $canReview,
            'myReview'    => $myReview,
            'related'     => $related,
        ]);
    }
}
