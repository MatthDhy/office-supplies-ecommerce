<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->string('brand', 100)->nullable()->index();
            $table->decimal('price', 12, 0)->index();
            $table->decimal('sale_price', 12, 0)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('image', 500)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('status', 20)->default('active')->index();  // active | inactive
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
