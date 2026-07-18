<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Khách đã đăng nhập gửi đánh giá cho 1 sản phẩm.
     * Mỗi khách chỉ có 1 đánh giá / sản phẩm (gửi lại sẽ cập nhật).
     */
    public function store(Request $request, $productId)
    {
        $product = DB::table('products')->where('id', $productId)->where('status', 1)->first();
        if (!$product) {
            return redirect()->back()->with('error', 'Sản phẩm không tồn tại');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'rating.required' => 'Vui lòng chọn số sao',
        ]);

        $userId = Auth::id();
        $now = now();

        $existing = DB::table('reviews')
            ->where('product_id', $productId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            DB::table('reviews')->where('id', $existing->id)->update([
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'status' => 1,
                'updated_at' => $now,
            ]);
            $message = 'Đã cập nhật đánh giá của bạn';
        } else {
            DB::table('reviews')->insert([
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $message = 'Cảm ơn bạn đã đánh giá sản phẩm';
        }

        return redirect()->route('shop.product', ['slug' => $product->slug])
            ->with('success', $message)
            ->withFragment('reviews');
    }
}
