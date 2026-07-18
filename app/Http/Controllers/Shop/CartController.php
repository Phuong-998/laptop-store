<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use ShopHelpers;

    public function index()
    {
        $cart = $this->cartDetail();
        return view('shop.cart', ['cart' => $cart]);
    }

    /**
     * Thêm sản phẩm vào giỏ (session). Trả JSON khi gọi AJAX, ngược lại redirect.
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = DB::table('products')->where('id', $validated['product_id'])->where('status', 1)->first();
        if (!$product) {
            return $this->respond($request, false, 'Sản phẩm không khả dụng', 404);
        }

        $qty = (int) ($validated['quantity'] ?? 1);
        $cart = $this->cart();
        $newQty = ($cart[$product->id] ?? 0) + $qty;

        if ($product->stock_quantity !== null && $newQty > (int) $product->stock_quantity) {
            $newQty = (int) $product->stock_quantity;
        }
        if ($newQty < 1) {
            return $this->respond($request, false, 'Sản phẩm đã hết hàng', 422);
        }

        $cart[$product->id] = $newQty;
        session(['cart' => $cart]);

        return $this->respond($request, true, 'Đã thêm "' . $product->name . '" vào giỏ hàng');
    }

    /**
     * Cập nhật số lượng 1 dòng trong giỏ.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cart = $this->cart();
        $id = $validated['product_id'];
        $qty = (int) $validated['quantity'];

        if ($qty <= 0) {
            unset($cart[$id]);
        } else {
            $stock = (int) DB::table('products')->where('id', $id)->value('stock_quantity');
            $cart[$id] = min($qty, max(1, $stock));
        }
        session(['cart' => $cart]);

        if ($request->expectsJson()) {
            $detail = $this->cartDetail();
            return response()->json([
                'success' => true,
                'cart_count' => $detail['count'],
                'subtotal' => $detail['subtotal'],
            ]);
        }
        return redirect()->route('shop.cart');
    }

    public function remove(Request $request)
    {
        $id = $request->input('product_id');
        $cart = $this->cart();
        unset($cart[$id]);
        session(['cart' => $cart]);

        return $this->respond($request, true, 'Đã xóa sản phẩm khỏi giỏ hàng');
    }

    private function respond(Request $request, bool $success, string $message, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'cart_count' => $this->cartCount(),
            ], $success ? 200 : $status);
        }
        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }
}
