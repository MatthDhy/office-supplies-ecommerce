<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

/** Trang chủ: Route -> HomeController@index -> Model (4 query) -> view front.home */
class HomeController extends Controller
{
    public function index()
    {
        return view('front.home', [
            'categories' => Category::active()->orderBy('name')->get(),
            'featured'   => Product::active()->featured()->latest()->take(8)->get(),
            'newest'     => Product::active()->latest()->take(8)->get(),
            'onSale'     => Product::active()->onSale()->latest()->take(8)->get(),
        ]);
    }
}
