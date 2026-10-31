<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Admin CRUD sản phẩm + upload ảnh.
 * View nhận: index -> $products (Paginator), $categories, $filters | create -> $categories | edit -> $product, $categories
 * Xóa = SOFT DELETE (order_items còn tham chiếu). Slug của sản phẩm đã xóa vẫn bị giữ (UNIQUE) -> form báo trùng slug.
 */
class ProductController extends Controller
{
    use ApiResponse;

    /** GET /admin/products?q=&status=&category= */
    public function index(Request $request)
    {
        $filters = [
            'q'        => is_string($request->query('q')) ? trim($request->query('q')) : '',
            'status'   => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '',
            'category' => ctype_digit((string) $request->query('category')) ? (int) $request->query('category') : null,
        ];
        $like = '%' . addcslashes($filters['q'], '%_\\') . '%';

        $products = Product::with('category:id,name')
            ->when($filters['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('brand', 'like', $like)))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['category'], fn ($q, $c) => $q->where('category_id', $c))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products'   => $products,
            'filters'    => $filters,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** GET /admin/products/create */
    public function create()
    {
        return view('admin.products.create', ['categories' => Category::orderBy('name')->get(['id', 'name'])]);
    }

    /** POST /admin/products (multipart) */
    public function store(ProductRequest $request)
    {
        $data = $request->payload();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Đã thêm sản phẩm.');
    }

    /** GET /admin/products/{product}/edit */
    public function edit(Product $product)
    {
        return view('admin.products.edit', [
            'product'    => $product,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** PUT /admin/products/{product} (multipart) */
    public function update(ProductRequest $request, Product $product)
    {
        $data     = $request->payload();
        $oldImage = $product->image;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        // Chỉ xóa ảnh cũ SAU KHI cập nhật DB thành công
        if (isset($data['image'])) {
            $this->deleteImage($oldImage);
        }

        return redirect()->route('admin.products.index')->with('success', 'Đã cập nhật sản phẩm.');
    }

    /** DELETE /admin/products/{product} — soft delete, giữ ảnh vì đơn cũ vẫn hiển thị sản phẩm */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Đã xóa sản phẩm.');
    }

    private function deleteImage(?string $path): void
    {
        if ($path && !str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }
}
