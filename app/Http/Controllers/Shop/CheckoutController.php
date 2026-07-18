<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    use ShopHelpers;

    public function index()
    {
        $cart = $this->cartDetail();
        if (empty($cart['items'])) {
            return redirect()->route('shop.cart')->with('error', 'Giỏ hàng đang trống');
        }

        $zones = DB::table('shipping_zones')->where('status', 1)->orderBy('fee')->get();
        $user = Auth::user();

        return view('shop.checkout', compact('cart', 'zones', 'user'));
    }

    /**
     * AJAX: xem trước giảm giá + phí ship khi khách nhập mã / chọn khu vực.
     */
    public function applyCoupon(Request $request)
    {
        $cart = $this->cartDetail();
        $subtotal = $cart['subtotal'];
        $shippingFee = $this->shippingFeeFor($request->input('zone_id'), $subtotal);

        [$coupon, $discount, $error] = $this->resolveCoupon($request->input('code'), $subtotal, $shippingFee);
        if ($error) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        return response()->json([
            'success' => true,
            'code' => $coupon->code ?? null,
            'discount' => $discount,
            'shipping_fee' => $shippingFee,
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discount) + $shippingFee,
        ]);
    }

    /**
     * Đặt hàng: ghi vào order + order_item (không trừ kho — kho đi qua Phiếu xuất bên admin).
     */
    public function place(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'zone_id' => ['required', 'integer', 'exists:shipping_zones,id'],
            'payment_method' => ['required', 'in:cod,bank,card'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'zone_id.required' => 'Vui lòng chọn khu vực giao hàng',
            'zone_id.exists' => 'Khu vực giao hàng không hợp lệ',
        ]);

        $cart = $this->cartDetail();
        if (empty($cart['items'])) {
            return redirect()->route('shop.cart')->with('error', 'Giỏ hàng đang trống');
        }

        $subtotal = $cart['subtotal'];
        $shippingFee = $this->shippingFeeFor($validated['zone_id'] ?? null, $subtotal);
        [$coupon, $discount, $couponError] = $this->resolveCoupon($validated['coupon_code'] ?? null, $subtotal, $shippingFee);
        if ($couponError) {
            return redirect()->route('shop.checkout')->withInput()->with('error', $couponError);
        }

        $total = max(0, $subtotal - $discount) + $shippingFee;
        $now = now();
        $code = 'DH' . $now->format('YmdHis');

        $orderId = DB::transaction(function () use ($validated, $cart, $now, $code, $subtotal, $discount, $shippingFee, $total, $coupon) {
            $id = DB::table('order')->insertGetId([
                'user_id' => Auth::id(),
                'coupon_id' => $coupon->id ?? null,
                'order_code' => $code,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'],
                'shiping_address' => $validated['shipping_address'],
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'shipping_fee' => $shippingFee,
                'total_amount' => $total,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'unpaid',
                'order_status' => 'pending',
                'note' => $validated['note'] ?? null,
                'create_at' => $now,
                'update_at' => $now,
            ]);

            $rows = [];
            foreach ($cart['items'] as $item) {
                $rows[] = [
                    'order_id' => $id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'product_image' => $item['image'],
                    'product_price' => $item['price'],
                    'quanity' => $item['quantity'],
                    'total_price' => $item['line_total'],
                    'create_at' => $now,
                    'update_at' => $now,
                ];
            }
            DB::table('order_item')->insert($rows);

            if ($coupon) {
                DB::table('coupons')->where('id', $coupon->id)->increment('use_count');
            }

            return $id;
        });

        session()->forget('cart');

        return redirect()->route('shop.success', ['code' => $code]);
    }

    public function success($code)
    {
        $order = DB::table('order')->where('order_code', $code)->first();
        if (!$order) {
            abort(404);
        }
        // Khách vãng lai chỉ xem được đơn vừa đặt; khách đăng nhập xem được đơn của mình.
        if ($order->user_id && Auth::id() !== $order->user_id) {
            abort(403);
        }

        $items = DB::table('order_item')->where('order_id', $order->id)->get();

        return view('shop.success', compact('order', 'items'));
    }

    /**
     * Phí ship theo khu vực; miễn phí nếu đạt ngưỡng free_threshold.
     */
    private function shippingFeeFor($zoneId, float $subtotal): float
    {
        if (!$zoneId) {
            return 0.0;
        }
        $zone = DB::table('shipping_zones')->where('id', $zoneId)->where('status', 1)->first();
        if (!$zone) {
            return 0.0;
        }
        if ($zone->free_threshold && $subtotal >= (float) $zone->free_threshold) {
            return 0.0;
        }
        return (float) $zone->fee;
    }
}
