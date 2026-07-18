<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $orderCount = DB::table('order')->where('user_id', $user->id)->count();
        return view('shop.account', compact('user', 'orderCount'));
    }

    /**
     * Cập nhật thông tin cá nhân.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::table('users')->where('id', $user->id)->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect()->route('shop.account')->with('success', 'Đã cập nhật thông tin');
    }

    /**
     * Lịch sử đơn hàng của khách.
     */
    public function orders()
    {
        $userId = Auth::id();
        $counts = DB::table('order_item')
            ->select('order_id', DB::raw('COUNT(*) as c'))
            ->groupBy('order_id')
            ->pluck('c', 'order_id');

        $orders = DB::table('order')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($o) => (array) $o + ['item_count' => (int) ($counts[$o->id] ?? 0)]);

        return view('shop.orders', compact('orders'));
    }

    /**
     * Chi tiết 1 đơn hàng của khách.
     */
    public function orderDetail($code)
    {
        $order = DB::table('order')
            ->where('order_code', $code)
            ->where('user_id', Auth::id())
            ->first();
        if (!$order) {
            abort(404);
        }

        $items = DB::table('order_item')->where('order_id', $order->id)->get();

        return view('shop.order-detail', compact('order', 'items'));
    }
}
