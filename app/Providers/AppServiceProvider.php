<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // $products->links() mặc định ra Tailwind -> đổi sang Bootstrap 5 cho khớp giao diện
        Paginator::useBootstrapFive();
    }
}
