<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Danh sách đánh giá của khách hàng (kèm tên sản phẩm, người dùng).
     */
    public function index()
    {
        $reviews = DB::table('reviews as r')
            ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->orderByDesc('r.created_at')
            ->get([
                'r.id', 'r.product_id', 'r.user_id', 'r.rating', 'r.comment', 'r.status', 'r.created_at',
                'p.name as product_name', 'p.image as product_image',
                'u.name as user_name', 'u.email as user_email',
            ])
            ->map(fn ($r) => $this->formatReview($r));

        $products = DB::table('products')
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = DB::table('users')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.review', compact('reviews', 'products', 'users'));
    }

    /**
     * Thêm đánh giá (quản trị viên nhập hộ / nhập liệu mẫu).
     */
    public function store(Request $request)
    {
        $validated = $this->validateReview($request);

        $now = now();
        $id = DB::table('reviews')->insertGetId([
            'product_id' => $validated['product_id'],
            'user_id' => $validated['user_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'status' => $request->boolean('status') ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'message' => 'Đã thêm đánh giá',
            'review' => $this->findReview($id),
        ]);
    }

    /**
     * Sửa nội dung / điểm đánh giá.
     */
    public function update(Request $request, $id)
    {
        $review = DB::table('reviews')->where('id', $id)->first();
        if (!$review) {
            return response()->json(['message' => 'Không tìm thấy đánh giá'], 404);
        }

        $validated = $this->validateReview($request);

        DB::table('reviews')->where('id', $id)->update([
            'product_id' => $validated['product_id'],
            'user_id' => $validated['user_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'status' => $request->boolean('status') ? 1 : 0,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Cập nhật đánh giá thành công',
            'review' => $this->findReview($id),
        ]);
    }

    /**
     * Duyệt / ẩn đánh giá (bật tắt hiển thị).
     */
    public function toggle($id)
    {
        $review = DB::table('reviews')->where('id', $id)->first();
        if (!$review) {
            return response()->json(['message' => 'Không tìm thấy đánh giá'], 404);
        }

        $newStatus = (int) $review->status === 1 ? 0 : 1;
        DB::table('reviews')->where('id', $id)->update([
            'status' => $newStatus,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => $newStatus === 1 ? 'Đã duyệt / hiển thị đánh giá' : 'Đã ẩn đánh giá',
            'review' => $this->findReview($id),
        ]);
    }

    public function delete($id)
    {
        $review = DB::table('reviews')->where('id', $id)->first();
        if (!$review) {
            return response()->json(['message' => 'Không tìm thấy đánh giá'], 404);
        }

        DB::table('reviews')->where('id', $id)->delete();

        return response()->json(['message' => 'Đã xóa đánh giá', 'id' => $id]);
    }

    private function validateReview(Request $request): array
    {
        return $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ]);
    }

    private function findReview($id): ?array
    {
        $r = DB::table('reviews as r')
            ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.id', $id)
            ->first([
                'r.id', 'r.product_id', 'r.user_id', 'r.rating', 'r.comment', 'r.status', 'r.created_at',
                'p.name as product_name', 'p.image as product_image',
                'u.name as user_name', 'u.email as user_email',
            ]);

        return $r ? $this->formatReview($r) : null;
    }

    private function formatReview($r): array
    {
        return [
            'id' => $r->id,
            'product_id' => $r->product_id,
            'product_name' => $r->product_name,
            'product_image' => $r->product_image,
            'user_id' => $r->user_id,
            'user_name' => $r->user_name,
            'user_email' => $r->user_email,
            'rating' => (int) $r->rating,
            'comment' => $r->comment,
            'status' => (int) $r->status,
            'created_at' => $r->created_at,
        ];
    }
}
