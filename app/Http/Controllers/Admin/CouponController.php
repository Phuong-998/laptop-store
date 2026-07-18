<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = DB::table('coupons')
            ->orderByDesc('create_at')
            ->get()
            ->map(fn ($coupon) => $this->formatCoupon($coupon));

        return view('admin.coupon', compact('coupons'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCoupon($request);
        $code = strtoupper($validated['code']);

        if ($this->codeExists($code)) {
            return response()->json([
                'message' => 'Mã giảm giá đã tồn tại',
            ], 422);
        }

        $now = now();
        $id = DB::table('coupons')->insertGetId([
            'code' => $code,
            'type' => $validated['type'],
            'value' => $validated['value'] ?? 0,
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'max_discount_amount' => $validated['max_discount_amount'] ?? 0,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'use_limit' => $validated['use_limit'] ?? null,
            'use_count' => 0,
            'status' => $request->boolean('active') ? '1' : '0',
            'create_at' => $now,
            'update_at' => $now,
        ]);

        return response()->json([
            'message' => 'Đã thêm mã giảm giá',
            'coupon' => $this->formatCoupon(DB::table('coupons')->find($id)),
        ]);
    }

    public function update(Request $request, $id)
    {
        $coupon = DB::table('coupons')->where('id', $id)->first();
        if (!$coupon) {
            return response()->json([
                'message' => 'Không tìm thấy mã giảm giá',
            ], 404);
        }

        $validated = $this->validateCoupon($request);
        $code = strtoupper($validated['code']);

        if ($this->codeExists($code, (int) $id)) {
            return response()->json([
                'message' => 'Mã giảm giá đã tồn tại',
            ], 422);
        }

        DB::table('coupons')->where('id', $id)->update([
            'code' => $code,
            'type' => $validated['type'],
            'value' => $validated['value'] ?? 0,
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'max_discount_amount' => $validated['max_discount_amount'] ?? 0,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'use_limit' => $validated['use_limit'] ?? null,
            'status' => $request->boolean('active') ? '1' : '0',
            'update_at' => now(),
        ]);

        return response()->json([
            'message' => 'Cập nhật mã giảm giá thành công',
            'coupon' => $this->formatCoupon(DB::table('coupons')->find($id)),
        ]);
    }

    public function delete($id)
    {
        $coupon = DB::table('coupons')->where('id', $id)->first();
        if (!$coupon) {
            return response()->json([
                'message' => 'Không tìm thấy mã giảm giá',
            ], 404);
        }

        DB::table('coupons')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Đã xóa mã giảm giá',
            'id' => $id,
        ]);
    }

    private function validateCoupon(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percent', 'fixed', 'shipping'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'use_limit' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    private function codeExists(string $code, ?int $ignoreId = null): bool
    {
        return DB::table('coupons')
            ->whereRaw('UPPER(code) = ?', [$code])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function formatCoupon($coupon): ?array
    {
        if (!$coupon) {
            return null;
        }

        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'min_order_amount' => $coupon->min_order_amount,
            'max_discount_amount' => $coupon->max_discount_amount,
            'start_date' => $coupon->start_date,
            'end_date' => $coupon->end_date,
            'use_limit' => $coupon->use_limit,
            'use_count' => $coupon->use_count,
            'status' => $coupon->status,
            'create_at' => $coupon->create_at,
        ];
    }
}
