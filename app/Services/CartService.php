<?php

namespace App\Services;

use App\Models\Product;
use DomainException;

/**
 * GIỎ HÀNG LƯU TRONG SESSION:  session('cart') = [ product_id => quantity ]
 *
 * Chỉ lưu id + số lượng. Giá, tên, ảnh luôn LOAD LẠI TỪ DB mỗi lần tính (detail())
 * => không bao giờ tin giá do client gửi lên.
 *
 * Mọi lỗi nghiệp vụ ném DomainException -> controller bắt và trả JSON fail.
 */
class CartService
{
    private const KEY = 'cart';

    /** @return array<int,int> [product_id => quantity] */
    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function add(int $productId, int $qty = 1): void
    {
        $product = $this->findBuyable($productId);
        $newQty  = ($this->raw()[$productId] ?? 0) + $qty;
        $this->assertQuantity($product, $newQty);

        $this->save([$productId => $newQty] + $this->raw());
    }

    public function update(int $productId, int $qty): void
    {
        $product = $this->findBuyable($productId);
        $this->assertQuantity($product, $qty);

        $cart = $this->raw();
        $cart[$productId] = $qty;
        $this->save($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        $this->save($cart);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Dữ liệu đầy đủ để hiển thị / checkout. Tự loại sản phẩm đã inactive/xóa/hết hàng.
     * @return array{items: array, subtotal: int, count: int}
     */
    public function detail(): array
    {
        $cart     = $this->raw();
        $products = Product::active()->whereIn('id', array_keys($cart))->get()->keyBy('id');

        $items = [];
        $subtotal = 0;
        $clean = [];

        foreach ($cart as $id => $qty) {
            $p = $products->get($id);
            if (!$p || $p->stock < 1) {
                continue; // sản phẩm không còn bán -> bỏ khỏi giỏ
            }
            $qty = min($qty, $p->stock); // giảm cho khớp tồn kho
            $line = $p->final_price * $qty;

            $items[] = [
                'product_id' => $p->id,
                'name'       => $p->name,
                'slug'       => $p->slug,
                'image_url'  => $p->image_url,
                'unit_price' => $p->final_price,
                'quantity'   => $qty,
                'stock'      => $p->stock,
                'subtotal'   => $line,
            ];
            $subtotal += $line;
            $clean[$id] = $qty;
        }

        if ($clean !== $cart) {
            $this->save($clean); // đồng bộ lại session nếu có thay đổi
        }

        return ['items' => $items, 'subtotal' => $subtotal, 'count' => array_sum($clean)];
    }

    /* ---------------- helpers ---------------- */

    private function findBuyable(int $productId): Product
    {
        $product = Product::active()->find($productId);
        if (!$product) {
            throw new DomainException('Sản phẩm không tồn tại hoặc đã ngừng bán.');
        }
        return $product;
    }

    private function assertQuantity(Product $product, int $qty): void
    {
        if ($qty < 1) {
            throw new DomainException('Số lượng phải lớn hơn 0.');
        }
        if ($qty > $product->stock) {
            throw new DomainException("Chỉ còn {$product->stock} sản phẩm trong kho.");
        }
    }

    private function save(array $cart): void
    {
        session([self::KEY => $cart]);
    }
}
