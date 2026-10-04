<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // Toàn bộ routes/admin.php tự động có: prefix /admin, tên admin.*, và phải là admin
            Route::middleware(['web', 'auth', 'admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['admin' => AdminMiddleware::class]);
        // Webhook PayOS là server gọi server -> không có CSRF token
        $middleware->validateCsrfTokens(except: ['payment/payos/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
