<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Bút viết', 'Vở & Sổ tay', 'Giấy in & Photo', 'Dụng cụ học tập', 'Đồ dùng văn phòng', 'Balo & Túi'] as $name) {
            Category::create(['name' => $name, 'slug' => Str::slug($name), 'status' => 'active']);
        }
    }
}
