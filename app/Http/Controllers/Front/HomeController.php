<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

/** Trang chủ: Route -> HomeController@index -> Model -> view front.home */
class HomeController extends Controller
{
    public function index()
    {
        // with('category'): thẻ sản phẩm hiện tên danh mục, tránh query lặp (N+1)
        $featured = Product::active()->featured()->with('category')->latest()->take(8)->get();
        $newest   = Product::active()->with('category')->latest()->take(8)->get();

        // Khối "Ai cũng mê": tuyển chọn từ hàng mới, bỏ sản phẩm đã có ở khối "Đồ hay ho"
        $favorites = $newest->diff($featured)->values();
        if ($favorites->isEmpty()) {
            $favorites = $newest;
        }

        return view('front.home', [
            'categories' => Category::active()->orderBy('name')->get(),
            'featured'   => $featured,
            'newest'     => $newest,
            'favorites'  => $favorites,
            'onSale'     => Product::active()->onSale()->with('category')->latest()->take(8)->get(),
        ]);
    }
}