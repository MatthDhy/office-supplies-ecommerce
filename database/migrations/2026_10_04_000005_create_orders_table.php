<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('order_code', 50)->unique();
            $table->decimal('subtotal', 12, 0);
            $table->decimal('discount_amount', 12, 0)->default(0);
            $table->decimal('shipping_fee', 12, 0)->default(0);
            $table->decimal('total_amount', 12, 0);
            $table->string('payment_method', 30);                                // cod | payos
            $table->string('payment_status', 30)->default('pending')->index();   // pending | paid | failed
            $table->string('status', 30)->default('pending')->index();           // pending|confirmed|processing|shipping|completed|cancelled
            $table->string('shipping_name', 100);
            $table->string('shipping_phone', 20);
            $table->text('shipping_address');
            $table->text('note')->nullable();
            $table->dateTime('expires_at')->nullable()->index();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
