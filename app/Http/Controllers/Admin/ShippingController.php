<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    public function index()
    {
        $zones = DB::table('shipping_zones')
            ->orderBy('fee')
            ->get()
            ->map(fn ($zone) => $this->formatZone($zone));

        return view('admin.shipping', compact('zones'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateZone($request);

        $now = now();
        $id = DB::table('shipping_zones')->insertGetId([
            'region' => $validated['region'],
            'provinces' => json_encode($this->cleanProvinces($validated['provinces'] ?? []), JSON_UNESCAPED_UNICODE),
            'fee' => $validated['fee'],
            'free_threshold' => $validated['free_threshold'] ?? null,
            'estimate_days' => $validated['estimate_days'] ?? null,
            'status' => $request->boolean('active') ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'message' => 'Đã thêm khu vực giao hàng',
            'zone' => $this->formatZone(DB::table('shipping_zones')->find($id)),
        ]);
    }

    public function update(Request $request, $id)
    {
        $zone = DB::table('shipping_zones')->where('id', $id)->first();
        if (!$zone) {
            return response()->json([
                'message' => 'Không tìm thấy khu vực',
            ], 404);
        }

        $validated = $this->validateZone($request);

        DB::table('shipping_zones')->where('id', $id)->update([
            'region' => $validated['region'],
            'provinces' => json_encode($this->cleanProvinces($validated['provinces'] ?? []), JSON_UNESCAPED_UNICODE),
            'fee' => $validated['fee'],
            'free_threshold' => $validated['free_threshold'] ?? null,
            'estimate_days' => $validated['estimate_days'] ?? null,
            'status' => $request->boolean('active') ? 1 : 0,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Cập nhật khu vực thành công',
            'zone' => $this->formatZone(DB::table('shipping_zones')->find($id)),
        ]);
    }

    public function delete($id)
    {
        $zone = DB::table('shipping_zones')->where('id', $id)->first();
        if (!$zone) {
            return response()->json([
                'message' => 'Không tìm thấy khu vực',
            ], 404);
        }

        DB::table('shipping_zones')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Đã xóa khu vực',
            'id' => $id,
        ]);
    }

    private function validateZone(Request $request): array
    {
        return $request->validate([
            'region' => ['required', 'string', 'max:255'],
            'provinces' => ['nullable', 'array'],
            'provinces.*' => ['string', 'max:255'],
            'fee' => ['required', 'numeric', 'min:0'],
            'free_threshold' => ['nullable', 'numeric', 'min:0'],
            'estimate_days' => ['nullable', 'string', 'max:50'],
            'active' => ['nullable', 'boolean'],
        ]);
    }

    private function cleanProvinces(array $provinces): array
    {
        return array_values(array_filter(array_map('trim', $provinces), fn ($p) => $p !== ''));
    }

    private function formatZone($zone): ?array
    {
        if (!$zone) {
            return null;
        }

        return [
            'id' => $zone->id,
            'region' => $zone->region,
            'provinces' => json_decode($zone->provinces ?? '[]', true) ?: [],
            'fee' => $zone->fee,
            'free_threshold' => $zone->free_threshold,
            'estimate_days' => $zone->estimate_days,
            'status' => $zone->status,
            'created_at' => $zone->created_at,
        ];
    }
}
