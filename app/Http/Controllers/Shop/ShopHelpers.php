<?php

namespace App\Http\Controllers\Shop;

use Illuminate\Support\Facades\DB;

/**
 * Các hàm dùng chung cho storefront: format sản phẩm, đọc giỏ hàng (session),
 * tính giảm giá coupon và phí vận chuyển.
 */
trait ShopHelpers
{
    /**
     * Giá bán hiệu lực của sản phẩm (ưu tiên giá khuyến mãi nếu có).
     */
    protected function effectivePrice($product): float
    {
        $sale = $product->sale_price;
        if ($sale !== null && (float) $sale > 0) {
            return (float) $sale;
        }
        return (float) ($product->price ?? 0);
    }

    /**
     * Query sản phẩm đang bán, kèm thông số kỹ thuật (cho dải spec trên card).
     */
    protected function productQuery()
    {
        return DB::table('products as p')
            ->leftJoin('product_specs as ps', 'ps.product_id', '=', 'p.id')
            ->where('p.status', 1)
            ->select('p.*', 'ps.cpu', 'ps.ram', 'ps.storage', 'ps.gpu');
    }

    /**
     * Chuẩn hóa 1 sản phẩm để render ra view.
     */
    protected function presentProduct($product): array
    {
        $price = $this->effectivePrice($product);
        $original = (float) ($product->price ?? 0);
        $hasDiscount = $product->sale_price !== null && (float) $product->sale_price > 0 && $original > $price;

        // Dải thông số ngắn (chỉ giữ token có giá trị).
        $specs = [];
        foreach (['cpu' => 'CPU', 'ram' => 'RAM', 'storage' => 'SSD', 'gpu' => 'GPU'] as $field => $label) {
            $val = $product->$field ?? null;
            if ($val) {
                $specs[] = ['label' => $label, 'value' => $this->shortSpec($val)];
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku ?? null,
            'image' => $product->image ?: 'https://placehold.co/600x450?text=Laptop',
            'price' => $price,
            'original_price' => $original,
            'has_discount' => $hasDiscount,
            'discount_percent' => $hasDiscount && $original > 0 ? (int) round(($original - $price) / $original * 100) : 0,
            'stock_quantity' => (int) ($product->stock_quantity ?? 0),
            'category_id' => $product->category_id ?? null,
            'branch_id' => $product->branch_id ?? null,
            'description' => $product->description ?? null,
            'warranty' => $product->warranty ?? null,
            'specs' => $specs,
        ];
    }

    /**
     * Rút gọn giá trị thông số để hiển thị gọn trên dải spec.
     */
    private function shortSpec(string $value): string
    {
        $value = trim($value);
        // Tránh chuỗi quá dài phá vỡ layout (đủ chỗ cho tên CPU đầy đủ).
        if (mb_strlen($value) > 22) {
            $value = mb_substr($value, 0, 21) . '…';
        }
        return $value;
    }

    /**
     * Giỏ hàng lưu trong session dạng [product_id => quantity].
     */
    protected function cart(): array
    {
        return session('cart', []);
    }

    /**
     * Lấy chi tiết giỏ hàng (join với sản phẩm hiện tại) + tổng tiền.
     */
    protected function cartDetail(): array
    {
        $cart = $this->cart();
        if (empty($cart)) {
            return ['items' => [], 'subtotal' => 0.0, 'count' => 0];
        }

        $products = DB::table('products')
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotal = 0.0;
        $count = 0;
        foreach ($cart as $productId => $qty) {
            $product = $products[$productId] ?? null;
            if (!$product) {
                continue;
            }
            $qty = (int) $qty;
            $price = $this->effectivePrice($product);
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;
            $count += $qty;
            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image ?: 'https://placehold.co/120x90?text=Laptop',
                'price' => $price,
                'quantity' => $qty,
                'stock_quantity' => (int) $product->stock_quantity,
                'line_total' => $lineTotal,
            ];
        }

        return ['items' => $items, 'subtotal' => $subtotal, 'count' => $count];
    }

    /**
     * Số lượng sản phẩm trong giỏ (tổng quantity).
     */
    protected function cartCount(): int
    {
        return (int) array_sum($this->cart());
    }

    /**
     * Tính giảm giá từ coupon. Trả về [couponRow|null, discount, errorMessage|null].
     */
    protected function resolveCoupon(?string $code, float $subtotal, float $shippingFee): array
    {
        if (!$code) {
            return [null, 0.0, null];
        }

        $coupon = DB::table('coupons')->whereRaw('UPPER(code) = ?', [strtoupper($code)])->first();
        if (!$coupon || (string) $coupon->status !== '1') {
            return [null, 0.0, 'Mã giảm giá không tồn tại hoặc đã ngừng áp dụng'];
        }

        $now = now();
        if ($coupon->start_date && $now->lt($coupon->start_date)) {
            return [null, 0.0, 'Mã giảm giá chưa đến thời gian áp dụng'];
        }
        if ($coupon->end_date && $now->gt($coupon->end_date)) {
            return [null, 0.0, 'Mã giảm giá đã hết hạn'];
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
}
