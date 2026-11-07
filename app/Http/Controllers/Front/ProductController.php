<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * [Thành viên B] Danh sách + tìm kiếm + lọc + sắp xếp (AJAX) và trang chi tiết sản phẩm.
 */
class ProductController extends Controller
{
    use ApiResponse;

    private const PER_PAGE = 12;

    /** Giá khách thực trả: có sale (và sale < giá gốc) thì lấy sale. Khớp với Product::final_price. */
    private const FINAL_PRICE_SQL = 'CASE WHEN products.sale_price IS NOT NULL AND products.sale_price < products.price '
                                  . 'THEN products.sale_price ELSE products.price END';

    private const SORTS = [
        'newest'     => 'Mới nhất',
        'bestseller' => 'Bán chạy',
        'price_asc'  => 'Giá tăng dần',
        'price_desc' => 'Giá giảm dần',
    ];

    /** GET /products  (?q=&category=&brand=&min_price=&max_price=&sort=&page=) */
    public function index(Request $request)
    {
        $f = $this->filters($request);

        $query = Product::active()
            ->with('category')
            // Sản phẩm thuộc danh mục đang ẩn thì không hiển thị ngoài cửa hàng
            ->whereHas('category', fn ($c) => $c->active())
            ->when($f['q'] !== '', function ($q) use ($f) {
                $like = '%' . addcslashes($f['q'], '%_\\') . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('brand', 'like', $like));
            })
            ->when($f['category'] !== '', fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $f['category'])))
            ->when($f['brand'] !== '', fn ($q) => $q->where('brand', $f['brand']))
            ->when($f['min_price'] !== null, fn ($q) => $q->whereRaw('(' . self::FINAL_PRICE_SQL . ') >= ?', [$f['min_price']]))
            ->when($f['max_price'] !== null, fn ($q) => $q->whereRaw('(' . self::FINAL_PRICE_SQL . ') <= ?', [$f['max_price']]));

        match ($f['sort']) {
            'price_asc'  => $query->orderByRaw('(' . self::FINAL_PRICE_SQL . ') asc'),
            'price_desc' => $query->orderByRaw('(' . self::FINAL_PRICE_SQL . ') desc'),
            'bestseller' => $query
                // Chỉ tính các đơn chưa bị hủy
                ->withSum(['orderItems as sold' => fn ($i) => $i->whereHas('order', fn ($o) => $o->where('status', '!=', Order::CANCELLED))], 'quantity')
                ->orderByDesc('sold')
                ->latest(),
            default      => $query->latest(),
        };

        // id giảm dần làm khóa phụ để phân trang ổn định khi nhiều sản phẩm trùng giá / cùng thời điểm
        $products = $query->orderByDesc('products.id')->paginate(self::PER_PAGE)->withQueryString();

        $categories   = Category::active()->orderBy('name')->get(['id', 'name', 'slug']);
        $categoryName = $f['category'] !== ''
            ? ($categories->firstWhere('slug', $f['category'])?->name ?? $f['category'])
            : null;
        $chips = $this->chips($f);

        $data = [
            'products'     => $products,
            'categories'  => $categories,
            'brands'      => Product::active()->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand'),
            'filters'     => $f,
            'categoryName' => $categoryName,
            'chips'       => $chips,
            'hasFilters'  => $categoryName !== null || count($chips) > 0,
            'summary'     => number_format($products->total(), 0, ',', '.') . ' sản phẩm · ' . ($categoryName ?? 'Tất cả văn phòng phẩm'),
            'sortOptions' => self::SORTS,
        ];

        // AJAX chỉ lấy phần kết quả (HTML); vào thẳng URL thì trả cả trang.
        // Vary: tránh trình duyệt cache nhầm bản "chỉ có lưới" cho cùng một URL (nút Back).
        $view = $request->ajax() ? 'front.products._grid' : 'front.products.index';

        return response()->view($view, $data)->header('Vary', 'X-Requested-With');
    }

    /** GET /products/{slug} */
    public function show(Request $request, Product $product)
    {
        // 1. Sản phẩm ngừng bán hoặc danh mục đang ẩn -> 404
        abort_unless($product->status === 'active', 404);
        $product->load('category');
        abort_unless($product->category?->status === 'active', 404);

        // 2. Review đang hiển thị (kèm user) + điểm trung bình
        $reviews = $product->reviews()->visible()->with('user:id,full_name,avatar')->latest()->get();
        $avgRating = round((float) $product->reviews()->visible()->avg('rating'), 1);

        $user = $request->user();
        $inWishlist = false;
        $canReview = false;
        $myReview = null;

        if ($user) {
            // 3. Đã nằm trong wishlist chưa (để tô tim)
            $inWishlist = $user->wishlistProducts()->where('products.id', $product->id)->exists();

            // 4. Được review khi: có đơn completed chứa sản phẩm này + chưa review
            $myReview = $reviews->firstWhere('user_id', $user->id)
                ?? $product->reviews()->where('user_id', $user->id)->first();   // review của mình kể cả khi bị admin ẩn
            $hasBought = Order::where('user_id', $user->id)
                ->where('status', Order::COMPLETED)
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->exists();
            $canReview = $hasBought && !$myReview;
        }

        // 5. 4 sản phẩm cùng danh mục để gợi ý
        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()->limit(4)->get();

        // 6.
        return view('front.products.show', compact(
            'product', 'reviews', 'avgRating', 'inWishlist', 'canReview', 'myReview', 'related'
        ));
    }

    /* ---------------- helpers ---------------- */

    /** Chuẩn hóa query string: giá trị sai (mảng, số âm, sort lạ...) bị bỏ qua thay vì gây lỗi. */
    private function filters(Request $request): array
    {
        $text = function (string $key, int $max = 100) use ($request): string {
            $v = $request->query($key);
            return is_scalar($v) ? mb_substr(trim((string) $v), 0, $max) : '';
        };
        $money = function (string $key) use ($request): ?int {
            $v = $request->query($key);
            return (is_scalar($v) && is_numeric($v) && $v >= 0) ? (int) min($v, 1_000_000_000) : null;
        };

        $min = $money('min_price');
        $max = $money('max_price');
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $sort = $text('sort');

        return [
            'q'         => $text('q'),
            'category'  => $text('category', 180),
            'brand'     => $text('brand'),
            'min_price' => $min,
            'max_price' => $max,
            'sort'      => array_key_exists($sort, self::SORTS) ? $sort : 'newest',
        ];
    }

    /** Thẻ bộ lọc đang bật (bấm để bỏ). field: q | brand | price. Danh mục hiển thị riêng ở view. */
    private function chips(array $f): array
    {
        $money = fn (int $v) => number_format($v, 0, ',', '.') . '₫';
        $chips = [];

        if ($f['q'] !== '') {
            $chips[] = ['field' => 'q', 'label' => 'Từ khóa: ' . $f['q']];
        }
        if ($f['brand'] !== '') {
            $chips[] = ['field' => 'brand', 'label' => 'Hãng: ' . $f['brand']];
        }
        if ($f['min_price'] !== null || $f['max_price'] !== null) {
            $label = match (true) {
                $f['min_price'] !== null && $f['max_price'] !== null => $money($f['min_price']) . ' – ' . $money($f['max_price']),
                $f['min_price'] !== null                             => 'Từ ' . $money($f['min_price']),
                default                                              => 'Đến ' . $money($f['max_price']),
            };
            $chips[] = ['field' => 'price', 'label' => 'Giá: ' . $label];
        }

        return $chips;
    }
}