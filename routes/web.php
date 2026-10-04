<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\CouponController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\OrderController;
use App\Http\Controllers\Front\PaymentController;
use App\Http\Controllers\Front\ProductController;
use App\Http\Controllers\Front\ProfileController;
use App\Http\Controllers\Front\ReviewController;
use App\Http\Controllers\Front\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ROUTES CHO KHÁCH / USER (khu Admin nằm ở routes/admin.php)
| Tag [A] [B] [C] = người phụ trách. Thêm route mới vào ĐÚNG nhóm của mình.
|--------------------------------------------------------------------------
*/

// ============ PUBLIC (ai cũng vào được) ============
Route::get('/', [HomeController::class, 'index'])->name('home');                              // [A]
Route::get('/products', [ProductController::class, 'index'])->name('products.index');        // [B] list + search + filter + sort
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show'); // [B]

// ============ CART (session, guest cũng dùng được) ============ [A]
Route::prefix('cart')->name('cart.')->controller(CartController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/count', 'count')->name('count');
    Route::post('/add', 'add')->name('add');
    Route::patch('/update', 'update')->name('update');
    Route::delete('/remove', 'remove')->name('remove');
});

// ============ AUTH (chưa đăng nhập) ============ [A]
Route::middleware('guest')->controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('login.submit');
    Route::get('/register', 'showRegister')->name('register');
    Route::post('/register', 'register')->name('register.submit');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ============ USER (bắt buộc đăng nhập) ============
Route::middleware('auth')->group(function () {

    // --- Checkout / Coupon / Order create --- [A]
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/coupon/apply', [CouponController::class, 'apply'])->name('coupon.apply');

    // --- Payment redirect --- [A]
    Route::get('/payment/return', [PaymentController::class, 'payosReturn'])->name('payment.return');
    Route::get('/payment/cancel', [PaymentController::class, 'payosCancel'])->name('payment.cancel');

    // --- Orders (lịch sử, chi tiết, hủy) --- [B]
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order:order_code}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order:order_code}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // --- Wishlist --- [B]
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/wishlist/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlist.move-to-cart');

    // --- Review --- [B]
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');

    // --- Profile + địa chỉ --- [B]
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/addresses', [ProfileController::class, 'storeAddress'])->name('profile.addresses.store');
    Route::delete('/profile/addresses/{address}', [ProfileController::class, 'destroyAddress'])->name('profile.addresses.destroy');
    Route::patch('/profile/addresses/{address}/default', [ProfileController::class, 'setDefaultAddress'])->name('profile.addresses.default');
});

// ============ WEBHOOK (server PayOS gọi, KHÔNG auth, KHÔNG csrf) ============ [A]
Route::post('/payment/payos/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
