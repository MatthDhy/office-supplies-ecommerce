<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Hủy đơn PayOS quá hạn thanh toán + hoàn kho/coupon.
// Local: php artisan schedule:work   |   Server: cron chạy "php artisan schedule:run" mỗi phút
Artisan::command('orders:cancel-expired', function () {
    $n = app(\App\Services\OrderService::class)->cancelExpired();
    $this->info("Đã hủy {$n} đơn PayOS quá hạn.");
})->purpose('Hủy đơn PayOS quá hạn thanh toán');

Schedule::command('orders:cancel-expired')->everyMinute();
