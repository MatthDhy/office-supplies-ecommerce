<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->unsignedBigInteger('provider_order_code')->nullable()->unique();
            $table->string('transaction_id', 150)->nullable()->unique();   // unique => chống webhook trùng
            $table->string('payment_method', 30);
            $table->decimal('amount', 12, 0);
            $table->string('status', 30)->default('pending')->index();
            $table->string('payment_url', 1000)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
