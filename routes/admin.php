<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ROUTES ADMIN — tự động có prefix "/admin", tên "admin.*", middleware auth+admin
| (cấu hình trong bootstrap/app.php). Ví dụ: route('admin.products.index') => /admin/products
|--------------------------------------------------------------------------
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');   // [C]

Route::resource('categories', CategoryController::class)->except('show');   // [C]
Route::resource('products', ProductController::class)->except('show');      // [C]
Route::resource('coupons', CouponController::class)->except('show');        // [C]

Route::controller(OrderController::class)->prefix('orders')->name('orders.')->group(function () { // [C]
    Route::get('/', 'index')->name('index');
    Route::get('/{order}', 'show')->name('show');
    Route::patch('/{order}/status', 'updateStatus')->name('status');
});

Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {    // [C]
    Route::get('/', 'index')->name('index');
    Route::get('/{user}', 'show')->name('show');
    Route::patch('/{user}/toggle-status', 'toggleStatus')->name('toggle');
});

Route::controller(ReviewController::class)->prefix('reviews')->name('reviews.')->group(function () { // [C]
    Route::get('/', 'index')->name('index');
    Route::patch('/{review}/toggle', 'toggle')->name('toggle');
    Route::delete('/{review}', 'destroy')->name('destroy');
});
