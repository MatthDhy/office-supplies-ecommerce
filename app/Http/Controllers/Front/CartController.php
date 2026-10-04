<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\CartService;
use DomainException;
use Illuminate\Http\Request;

/**
 * MẪU CHUẨN CHO MỌI API AJAX CỦA TEAM (đọc file này để biết cách viết):
 *   JS ($.post) -> Route -> Controller (validate) -> Service (nghiệp vụ) -> JSON {success,message,data}
 */
class CartController extends Controller
{
    use ApiResponse;

    public function __construct(private CartService $cart) {}

    public function index()
    {
        return view('front.cart', ['cart' => $this->cart->detail()]);
    }

    public function count()
    {
        return $this->success(['count' => $this->cart->count()]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity'   => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $this->cart->add($data['product_id'], $data['quantity'] ?? 1);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success(['count' => $this->cart->count()], 'Đã thêm vào giỏ hàng');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        try {
            $this->cart->update($data['product_id'], $data['quantity']);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success($this->cart->detail(), 'Đã cập nhật giỏ hàng'); // trả luôn subtotal mới để JS vẽ lại
    }

    public function remove(Request $request)
    {
        $data = $request->validate(['product_id' => ['required', 'integer']]);
        $this->cart->remove($data['product_id']);

        return $this->success($this->cart->detail(), 'Đã xóa sản phẩm');
    }
}
