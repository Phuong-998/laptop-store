<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /** Trạng thái đơn hàng + nhãn tiếng Việt (đồng thời định nghĩa luồng hợp lệ). */
    private const ORDER_STATUSES = [
        'pending' => 'Chờ xác nhận',
        'processing' => 'Đang xử lý',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];

    /** Trạng thái thanh toán. */
    private const PAYMENT_STATUSES = [
        'unpaid' => 'Chưa thanh toán',
        'paid' => 'Đã thanh toán',
        'refunded' => 'Đã hoàn tiền',
    ];

    /** Phương thức thanh toán. */
    private const PAYMENT_METHODS = [
        'cod' => 'Thanh toán khi nhận (COD)',
        'bank' => 'Chuyển khoản ngân hàng',
        'card' => 'Thẻ tín dụng / ghi nợ',
    ];

    /**
     * Danh sách đơn hàng + dữ liệu cần cho form tạo đơn.
     */
    public function index()
    {
        $counts = DB::table('order_item')
            ->select('order_id', DB::raw('COUNT(*) as c'))
            ->groupBy('order_id')
            ->pluck('c', 'order_id');

        $orders = DB::table('order')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($o) => $this->formatOrder($o, (int) ($counts[$o->id] ?? 0)));

        $products = DB::table('products')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'sale_price', 'image', 'stock_quantity']);

        $users = DB::table('users')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'address']);

        $coupons = DB::table('coupons')
            ->where('status', '1')
            ->orderByDesc('create_at')
            ->get(['id', 'code', 'type', 'value', 'min_order_amount', 'max_discount_amount', 'use_limit', 'use_count']);

        return view('admin.order', compact('orders', 'products', 'users', 'coupons'));
    }

    /**
     * Tạo đơn hàng thủ công từ trang quản trị.
     * Lưu ý: tồn kho không bị trừ ở đây — biến động kho đi qua Phiếu xuất (có order_id).
     */
    public function store(Request $request)
    {
        $validated = $this->validateOrder($request);

        $now = now();
        $priceMap = DB::table('products')
            ->whereIn('id', array_column($validated['items'], 'product_id'))
            ->get(['id', 'name', 'price', 'sale_price', 'image'])
            ->keyBy('id');

        // Tính tiền phía server để tránh gian lận từ client.
        $subtotal = 0;
        $lines = [];
        foreach ($validated['items'] as $item) {
            $product = $priceMap[$item['product_id']] ?? null;
            if (!$product) {
                return response()->json(['message' => 'Sản phẩm không tồn tại'], 422);
            }
            $unitPrice = (float) ($product->sale_price ?? $product->price ?? 0);
            $lineTotal = $unitPrice * $item['quantity'];
            $subtotal += $lineTotal;
            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image' => $product->image,
                'product_price' => $unitPrice,
                'quanity' => $item['quantity'],
                'total_price' => $lineTotal,
            ];
        }

        $shippingFee = (float) ($validated['shipping_fee'] ?? 0);
        [$coupon, $discount, $couponError] = $this->resolveCoupon($validated['coupon_id'] ?? null, $subtotal, $shippingFee);
        if ($couponError) {
            return response()->json(['message' => $couponError], 422);
        }

        $total = max(0, $subtotal - $discount) + $shippingFee;
        $code = 'DH' . $now->format('YmdHis');

        $orderId = DB::transaction(function () use ($validated, $lines, $now, $code, $subtotal, $discount, $shippingFee, $total, $coupon) {
            $id = DB::table('order')->insertGetId([
                'user_id' => $validated['user_id'] ?? null,
                'coupon_id' => $coupon->id ?? null,
                'order_code' => $code,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'shiping_address' => $validated['shipping_address'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'shipping_fee' => $shippingFee,
                'total_amount' => $total,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_status'],
                'order_status' => $validated['order_status'],
                'note' => $validated['note'] ?? null,
                'create_at' => $now,
                'update_at' => $now,
            ]);

            foreach ($lines as &$line) {
                $line['order_id'] = $id;
                $line['create_at'] = $now;
                $line['update_at'] = $now;
            }
            unset($line);
            DB::table('order_item')->insert($lines);

            if ($coupon) {
                DB::table('coupons')->where('id', $coupon->id)->increment('use_count');
            }

            return $id;
        });

        return response()->json([
            'message' => 'Đã tạo đơn hàng',
            'order' => $this->findOrder($orderId),
        ]);
    }

    /**
     * Chi tiết đơn hàng (dùng cho modal xem).
     */
    public function show($id)
    {
        $order = $this->findOrder($id);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        return response()->json(['order' => $order]);
    }

    /**
     * Cập nhật trạng thái đơn hàng và/hoặc trạng thái thanh toán.
     */
    public function updateStatus(Request $request, $id)
    {
        $order = DB::table('order')->where('id', $id)->first();
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        $validated = $request->validate([
            'order_status' => ['required', Rule::in(array_keys(self::ORDER_STATUSES))],
            'payment_status' => ['required', Rule::in(array_keys(self::PAYMENT_STATUSES))],
        ]);

        if ($order->order_status === 'completed' && $validated['order_status'] === 'cancelled') {
            return response()->json(['message' => 'Không thể hủy đơn đã hoàn thành'], 422);
        }

        DB::table('order')->where('id', $id)->update([
            'order_status' => $validated['order_status'],
            'payment_status' => $validated['payment_status'],
            'update_at' => now(),
        ]);

        return response()->json([
            'message' => 'Đã cập nhật trạng thái đơn hàng',
            'order' => $this->findOrder($id),
        ]);
    }

    /**
     * Xóa đơn hàng — chỉ cho phép khi đơn đã hủy (tránh xóa nhầm đơn đang xử lý).
     */
    public function destroy($id)
    {
        $order = DB::table('order')->where('id', $id)->first();
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }
        if ($order->order_status !== 'cancelled') {
            return response()->json(['message' => 'Chỉ có thể xóa đơn đã hủy'], 422);
        }

        DB::transaction(function () use ($id) {
            DB::table('order_item')->where('order_id', $id)->delete();
            DB::table('order')->where('id', $id)->delete();
        });

        return response()->json(['message' => 'Đã xóa đơn hàng', 'id' => $id]);
    }

    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'shipping_address' => ['nullable', 'string'],
            'coupon_id' => ['nullable', 'integer', 'exists:coupons,id'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(array_keys(self::PAYMENT_METHODS))],
            'payment_status' => ['required', Rule::in(array_keys(self::PAYMENT_STATUSES))],
            'order_status' => ['required', Rule::in(array_keys(self::ORDER_STATUSES))],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);
    }

    /**
     * Tính giảm giá từ mã coupon. Trả về [couponRow|null, discount, errorMessage|null].
     */
    private function resolveCoupon($couponId, float $subtotal, float $shippingFee): array
    {
        if (!$couponId) {
            return [null, 0.0, null];
        }

        $coupon = DB::table('coupons')->where('id', $couponId)->first();
        if (!$coupon || (string) $coupon->status !== '1') {
            return [null, 0.0, 'Mã giảm giá không hợp lệ'];
        }
        if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
            return [null, 0.0, 'Đơn hàng chưa đạt giá trị tối thiểu để dùng mã'];
        }
        if ($coupon->use_limit && $coupon->use_count >= $coupon->use_limit) {
            return [null, 0.0, 'Mã giảm giá đã hết lượt sử dụng'];
        }

        $discount = match ($coupon->type) {
            'percent' => $subtotal * ((float) $coupon->value) / 100,
            'fixed' => (float) $coupon->value,
            'shipping' => $shippingFee,
            default => 0,
        };

        if ($coupon->max_discount_amount && $coupon->type !== 'shipping') {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }
        $discount = min($discount, $subtotal + ($coupon->type === 'shipping' ? $shippingFee : 0));

        return [$coupon, round($discount, 2), null];
    }

    private function formatOrder($order, int $itemCount): array
    {
        return [
            'id' => $order->id,
            'order_code' => $order->order_code,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'total_amount' => $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_method_label' => self::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_status_label' => self::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status,
            'order_status' => $order->order_status,
            'order_status_label' => self::ORDER_STATUSES[$order->order_status] ?? $order->order_status,
            'item_count' => $itemCount,
            'create_at' => $order->create_at,
        ];
    }

    private function findOrder($id): ?array
    {
        $o = DB::table('order')->where('id', $id)->first();
        if (!$o) {
            return null;
        }

        $items = DB::table('order_item')
            ->where('order_id', $id)
            ->get(['id', 'product_id', 'product_name', 'product_image', 'product_price', 'quanity', 'total_price'])
            ->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'product_name' => $i->product_name,
                'product_image' => $i->product_image,
                'product_price' => $i->product_price,
                'quantity' => (int) $i->quanity,
                'total_price' => (float) $i->total_price,
            ]);

        $coupon = $o->coupon_id ? DB::table('coupons')->where('id', $o->coupon_id)->value('code') : null;

        return [
            'id' => $o->id,
            'order_code' => $o->order_code,
            'user_id' => $o->user_id,
            'coupon_id' => $o->coupon_id,
            'coupon_code' => $coupon,
            'customer_name' => $o->customer_name,
            'customer_email' => $o->customer_email,
            'customer_phone' => $o->customer_phone,
            'shipping_address' => $o->shiping_address,
            'subtotal' => $o->subtotal,
            'discount_amount' => $o->discount_amount,
            'shipping_fee' => $o->shipping_fee,
            'total_amount' => $o->total_amount,
            'payment_method' => $o->payment_method,
            'payment_method_label' => self::PAYMENT_METHODS[$o->payment_method] ?? $o->payment_method,
            'payment_status' => $o->payment_status,
            'payment_status_label' => self::PAYMENT_STATUSES[$o->payment_status] ?? $o->payment_status,
            'order_status' => $o->order_status,
            'order_status_label' => self::ORDER_STATUSES[$o->order_status] ?? $o->order_status,
            'note' => $o->note,
            'create_at' => $o->create_at,
            'item_count' => $items->count(),
            'items' => $items,
        ];
    }
}
