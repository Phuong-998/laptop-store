<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportReceiptController extends Controller
{
    /** Nhãn tiếng Việt cho lý do nhập. */
    private const REASONS = [
        'purchase' => 'Nhập mua hàng',
        'return' => 'Khách/NCC trả lại',
        'other' => 'Khác',
    ];


    /**
     * Danh sách phiếu nhập + dữ liệu để lập phiếu mới.
     */
    public function index()
    {
        $counts = DB::table('import_receipt_items')
            ->select('receipt_id', DB::raw('COUNT(*) as c'))
            ->groupBy('receipt_id')
            ->pluck('c', 'receipt_id');

        $receipts = DB::table('import_receipts as r')
            ->orderByDesc('r.id')
            ->get(['r.id', 'r.code', 'r.supplier_name', 'r.reason', 'r.note', 'r.total_amount', 'r.import_date', 'r.status'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'code' => $r->code,
                'supplier_name' => $r->supplier_name,
                'reason' => $r->reason,
                'note' => $r->note,
                'total_amount' => $r->total_amount,
                'import_date' => $r->import_date,
                'status' => $r->status,
                'item_count' => (int) ($counts[$r->id] ?? 0),
            ]);

        $products = DB::table('products')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity']);

        $categories = DB::table('categorise')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = DB::table('brand')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.import', compact('receipts', 'products', 'categories', 'brands'));
    }

    /**
     * Tạo phiếu nhập ở trạng thái "chờ duyệt" (chưa cộng kho).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'in:' . implode(',', array_keys(self::REASONS))],
            'note' => ['nullable', 'string', 'max:255'],
            'import_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $now = now();
        $total = 0;
        foreach ($validated['items'] as $item) {
            $total += $item['quantity'] * $item['unit_price'];
        }

        $code = 'PN' . $now->format('YmdHis');

        $receiptId = DB::transaction(function () use ($validated, $now, $total, $code) {
            $id = DB::table('import_receipts')->insertGetId([
                'code' => $code,
                'supplier_name' => $validated['supplier_name'] ?? null,
                'reason' => $validated['reason'] ?? null,
                'note' => $validated['note'] ?? null,
                'total_amount' => $total,
                'import_date' => $validated['import_date'] ?? $now,
                'status' => 'pending',
                'created_at' => $now,
                'update_at' => $now,
            ]);

            $rows = [];
            foreach ($validated['items'] as $item) {
                $rows[] = [
                    'receipt_id' => $id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                    'create_at' => $now,
                    'update_at' => $now,
                ];
            }
            DB::table('import_receipt_items')->insert($rows);

            return $id;
        });

        return response()->json([
            'message' => 'Đã tạo phiếu nhập (chờ duyệt)',
            'receipt' => $this->findReceipt($receiptId),
        ]);
    }

    /**
     * Duyệt phiếu: cộng tồn kho + ghi nhật ký stock_transaction (gắn receipt_id).
     */
    public function confirm($id)
    {
        $receipt = DB::table('import_receipts')->where('id', $id)->first();
        if (!$receipt) {
            return response()->json(['message' => 'Không tìm thấy phiếu nhập'], 404);
        }
        if ($receipt->status === 'completed') {
            return response()->json(['message' => 'Phiếu đã được duyệt trước đó'], 422);
        }

        $items = DB::table('import_receipt_items')->where('receipt_id', $id)->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Phiếu không có sản phẩm'], 422);
        }

        $now = now();
        DB::transaction(function () use ($id, $items, $now, $receipt) {
            foreach ($items as $item) {
                $product = DB::table('products')->where('id', $item->product_id)->first();
                if (!$product) {
                    continue;
                }
                $before = (int) $product->stock_quantity;
                $after = $before + (int) $item->quantity;

                DB::table('products')->where('id', $item->product_id)->update([
                    'stock_quantity' => $after,
                    'update_at' => $now,
                ]);

                DB::table('stock_transaction')->insert([
                    'product_id' => $item->product_id,
                    'transaction_type' => 'in',
                    'quanity_change' => $item->quantity,
                    'quanity_before' => $before,
                    'quanity_after' => $after,
                    'receipt_id' => $id,
                    'receipt_type' => 'import',
                    'note' => 'Nhập theo phiếu ' . $receipt->code
                        . ($receipt->reason ? ' — ' . (self::REASONS[$receipt->reason] ?? $receipt->reason) : ''),
                    'create_at' => $now,
                    'update_at' => $now,
                ]);
            }

            DB::table('import_receipts')->where('id', $id)->update([
                'status' => 'completed',
                'update_at' => $now,
            ]);
        });

        return response()->json([
            'message' => 'Đã duyệt phiếu & cập nhật tồn kho',
            'receipt' => $this->findReceipt($id),
        ]);
    }

    /**
     * Chi tiết phiếu (dùng cho modal xem).
     */
    public function show($id)
    {
        $receipt = $this->findReceipt($id);
        if (!$receipt) {
            return response()->json(['message' => 'Không tìm thấy phiếu nhập'], 404);
        }

        return response()->json(['receipt' => $receipt]);
    }

    /**
     * Xóa phiếu — chỉ khi còn "chờ duyệt" (chưa tác động tồn kho).
     */
    public function destroy($id)
    {
        $receipt = DB::table('import_receipts')->where('id', $id)->first();
        if (!$receipt) {
            return response()->json(['message' => 'Không tìm thấy phiếu nhập'], 404);
        }
        if ($receipt->status === 'completed') {
            return response()->json(['message' => 'Không thể xóa phiếu đã duyệt (đã cộng kho)'], 422);
        }

        DB::transaction(function () use ($id) {
            DB::table('import_receipt_items')->where('receipt_id', $id)->delete();
            DB::table('import_receipts')->where('id', $id)->delete();
        });

        return response()->json(['message' => 'Đã xóa phiếu nhập', 'id' => $id]);
    }

    /**
     * Tạo nhanh sản phẩm mới ngay khi lập phiếu nhập (khi chưa có trong catalog).
     * Sản phẩm khởi tạo với tồn kho = 0; số lượng thực tế sẽ được cộng khi duyệt phiếu nhập.
     */
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categorise,id'],
            'branch_id' => ['required', 'integer', 'exists:brand,id'],
            'sku' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ]);

        if (DB::table('products')->where('name', $validated['name'])->exists()) {
            return response()->json(['message' => 'Sản phẩm đã tồn tại'], 422);
        }

        $now = now();
        $id = DB::table('products')->insertGetId([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'category_id' => $validated['category_id'],
            'branch_id' => $validated['branch_id'],
            'sku' => $validated['sku'] ?? null,
            'price' => $validated['price'],
            'sale_price' => $validated['sale_price'] ?? null,
            'stock_quantity' => 0,
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 10,
            'status' => $request->boolean('status') ? 1 : 0,
            'created_at' => $now,
            'update_at' => $now,
        ]);

        return response()->json([
            'message' => 'Đã thêm sản phẩm mới (tồn kho 0)',
            'product' => DB::table('products')->where('id', $id)->first(['id', 'name', 'sku', 'price', 'stock_quantity']),
        ]);
    }

    private function findReceipt($id): ?array
    {
        $r = DB::table('import_receipts')
            ->where('id', $id)
            ->first(['id', 'code', 'supplier_name', 'reason', 'note', 'total_amount', 'import_date', 'status']);

        if (!$r) {
            return null;
        }

        $items = DB::table('import_receipt_items as i')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->where('i.receipt_id', $id)
            ->get(['i.id', 'i.product_id', 'p.name as product_name', 'p.sku', 'i.quantity', 'i.unit_price', 'i.subtotal']);

        return [
            'id' => $r->id,
            'code' => $r->code,
            'supplier_name' => $r->supplier_name,
            'reason' => $r->reason,
            'reason_label' => $r->reason ? (self::REASONS[$r->reason] ?? $r->reason) : null,
            'note' => $r->note,
            'total_amount' => $r->total_amount,
            'import_date' => $r->import_date,
            'status' => $r->status,
            'item_count' => $items->count(),
            'items' => $items,
        ];
    }
}
