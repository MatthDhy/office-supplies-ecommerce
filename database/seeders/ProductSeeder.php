<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // [tên, hãng, giá, giá sale|null, tồn kho, nổi bật?]
        $data = [
            'but-viet' => [
                ['Bút bi Thiên Long TL-027', 'Thiên Long', 5000, null, 200, true],
                ['Bút gel Uni-ball Signo 0.5', 'Uni-ball', 25000, 22000, 120, true],
                ['Bút lông bảng Thiên Long WB-03', 'Thiên Long', 12000, null, 80, false],
            ],
            've-so-tay' => [
                ['Vở kẻ ngang Hồng Hà 96 trang', 'Hồng Hà', 9000, null, 300, true],
                ['Sổ tay bìa da A5', 'Klong', 65000, 55000, 60, true],
                ['Sổ lò xo A4 200 trang', 'Campus', 48000, null, 70, false],
            ],
            'giay-in-photo' => [
                ['Giấy A4 Double A 70gsm (500 tờ)', 'Double A', 85000, 79000, 150, true],
                ['Giấy A4 IK Plus 70gsm (500 tờ)', 'IK Plus', 72000, null, 100, false],
            ],
            'dung-cu-hoc-tap' => [
                ['Thước kẻ nhựa 30cm', 'Deli', 8000, null, 150, false],
                ['Hộp bút chì màu 24 màu', 'Colokit', 45000, 39000, 90, true],
                ['Máy tính Casio FX-580VN X', 'Casio', 690000, 650000, 25, true],
            ],
            'do-dung-van-phong' => [
                ['Kẹp giấy màu (hộp 100 cái)', 'Deli', 15000, null, 200, false],
                ['Máy bấm kim số 10', 'Deli', 35000, null, 50, false],
                ['Băng keo trong 5cm', 'Deli', 18000, 15000, 120, false],
            ],
            'balo-tui' => [
                ['Balo học sinh chống gù', 'Miti', 320000, 289000, 30, true],
                ['Túi đựng tài liệu A4 có khóa', 'Deli', 22000, null, 100, false],
            ],
        ];

        foreach ($data as $catSlug => $items) {
            $cat = Category::where('slug', $catSlug)->first();
            if (!$cat) { continue; }

            foreach ($items as [$name, $brand, $price, $sale, $stock, $featured]) {
                Product::create([
                    'category_id' => $cat->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => "Mô tả sản phẩm {$name}. (Dữ liệu mẫu để dev giao diện.)",
                    'brand' => $brand,
                    'price' => $price,
                    'sale_price' => $sale,
                    'stock' => $stock,
                    'is_featured' => $featured,
                    'status' => 'active',
                ]);
            }
        }
    }
}
