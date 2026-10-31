<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use Illuminate\Http\Request;

/**
 * Admin CRUD danh mục.
 * View nhận: index -> $categories (Paginator, có products_count), $filters | create -> (không cần) | edit -> $category
 */
class CategoryController extends Controller
{
    use ApiResponse;

    /** GET /admin/categories?q=&status= */
    public function index(Request $request)
    {
        $filters = [
            'q'      => is_string($request->query('q')) ? trim($request->query('q')) : '',
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '',
        ];
        $like = '%' . addcslashes($filters['q'], '%_\\') . '%';

        $categories = Category::withCount('products')
            ->when($filters['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('slug', 'like', $like)))
            ->when($filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', compact('categories', 'filters'));
    }

    /** GET /admin/categories/create */
    public function create()
    {
        return view('admin.categories.create');
    }

    /** POST /admin/categories */
    public function store(CategoryRequest $request)
    {
        Category::create($request->payload());

        return redirect()->route('admin.categories.index')->with('success', 'Đã tạo danh mục.');
    }

    /** GET /admin/categories/{category}/edit */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /** PUT /admin/categories/{category} */
    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->payload());

        return redirect()->route('admin.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    /** DELETE /admin/categories/{category} — còn sản phẩm (kể cả đã xóa mềm) thì KHÔNG xóa */
    public function destroy(Category $category)
    {
        if ($category->products()->withTrashed()->exists()) {
            return back()->with('error', 'Danh mục còn sản phẩm nên không thể xóa. Hãy chuyển trạng thái sang "inactive" hoặc chuyển sản phẩm sang danh mục khác.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Đã xóa danh mục.');
    }
}
