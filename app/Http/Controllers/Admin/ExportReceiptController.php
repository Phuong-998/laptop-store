<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportReceiptController extends Controller
{
    /** Nhãn tiếng Việt cho lý do xuất. */
    private const REASONS = [
        'sale' => 'Xuất bán',
        'warranty' => 'Xuất bảo hành',
        'return' => 'Trả nhà cung cấp',
        'damaged' => 'Hủy / Hỏng',
        'other' => 'Khác',
    ];


    /**
     * Danh sách phiếu xuất + dữ liệu để lập phiếu mới.
     */
    public function index()
    {
        $sums = DB::table('export_receipt_item')
            ->select('receipt_id', DB::raw('COUNT(*) as c'), DB::raw('SUM(subtotal) as total'))
            ->groupBy('receipt_id')
            ->get()
            ->keyBy('receipt_id');

        $receipts = DB::table('export_receipt as r')
            ->orderByDesc('r.id')
            ->get(['r.id', 'r.code', 'r.order_id', 'r.reason', 'r.note', 'r.export_date', 'r.status'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'code' => $r->code,
                'order_id' => $r->order_id,
                'reason' => $r->reason,
                'reason_label' => $r->reason ? (self::REASONS[$r->reason] ?? $r->reason) : null,
                'note' => $r->note,
                'export_date' => $r->export_date,
                'status' => $r->status,
                'item_count' => (int) ($sums[$r->id]->c ?? 0),
                'total_amount' => (float) ($sums[$r->id]->total ?? 0),
            ]);

        $products = DB::table('products')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'sale_price', 'stock_quantity']);

        return view('admin.export', compact('receipts', 'products'));
    }

    /**
     * Tạo phiếu xuất ở trạng thái "chờ duyệt" (chưa trừ kho).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['nullable', 'integer'],
            'reason' => ['nullable', 'string', 'in:' . implode(',', array_keys(self::REASONS))],
            'note' => ['nullable', 'string', 'max:255'],
            'export_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $now = now();
        $code = 'PX' . $now->format('YmdHis');

        // Giá xuất mặc định lấy theo giá bán của sản phẩm nếu không nhập.
        $priceMap = DB::table('products')
            ->whereIn('id', array_column($validated['items'], 'product_id'))
            ->get(['id', 'price', 'sale_price'])
            ->keyBy('id');

        $receiptId = DB::transaction(function () use ($validated, $now, $code, $priceMap) {
            $id = DB::table('export_receipt')->insertGetId([
                'code' => $code,
                'order_id' => $validated['order_id'] ?? null,
                'reason' => $validated['reason'] ?? null,
                'note' => $validated['note'] ?? null,
                'export_date' => $validated['export_date'] ?? $now,
                'status' => 'pending',
                'create_at' => $now,
                'update_at' => $now,
            ]);

            $rows = [];
            foreach ($validated['items'] as $item) {
                $product = $priceMap[$item['product_id']] ?? null;
                $unitPrice = $item['unit_price']
                    ?? ($product->sale_price ?? $product->price ?? 0);
                $rows[] = [
                    'receipt_id' => $id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $item['quantity'] * $unitPrice,
                    'create_at' => $now,
                    'update_at' => $now,
                ];
            }
            DB::table('export_receipt_item')->insert($rows);

            return $id;
        });

        return response()->json([
            'message' => 'Đã tạo phiếu xuất (chờ duyệt)',
            'receipt' => $this->findReceipt($receiptId),
        ]);
    }

    /**
     * Duyệt phiếu: kiểm tra đủ tồn → trừ kho + ghi nhật ký stock_transaction.
     */
    public function confirm($id)
    {
        $receipt = DB::table('export_receipt')->where('id', $id)->first();
        if (!$receipt) {
            return response()->json(['message' => 'Không tìm thấy phiếu xuất'], 404);
        }
        if ($receipt->status === 'completed') {
            return response()->json(['message' => 'Phiếu đã được duyệt trước đó'], 422);
        }

        $items = DB::table('export_receipt_item')->where('receipt_id', $id)->get();
        if ($items->isEmpty()) {
            return response()->json(['message' => 'Phiếu không có sản phẩm'], 422);
        }

        // Gộp số lượng theo sản phẩm rồi kiểm tra tồn kho.
        $need = [];
        foreach ($items as $item) {
            $need[$item->product_id] = ($need[$item->product_id] ?? 0) + (int) $item->quantity;
        }
        foreach ($need as $productId => $qty) {
            $product = DB::table('products')->where('id', $productId)->first();
            $stock = (int) ($product->stock_quantity ?? 0);
            if (!$product || $stock < $qty) {
                $name = $product->name ?? ('#' . $productId);
                return response()->json([
                    'message' => "Không đủ tồn kho cho \"{$name}\" (cần {$qty}, còn {$stock})",
                ], 422);
            }
        }

        $now = now();
        DB::transaction(function () use ($id, $items, $now, $receipt) {
            foreach ($items as $item) {
                $product = DB::table('products')->where('id', $item->product_id)->first();
                if (!$product) {
                    continue;
                }
                $before = (int) $product->stock_quantity;
                $after = $before - (int) $item->quantity;

                DB::table('products')->where('id', $item->product_id)->update([
                    'stock_quantity' => $after,
                    'update_at' => $now,
                ]);

                DB::table('stock_transaction')->insert([
                    'product_id' => $item->product_id,
                    'transaction_type' => 'out',
                    'quanity_change' => $item->quantity,
                    'quanity_before' => $before,
                    'quanity_after' => $after,
                    'receipt_id' => $id,
                    'receipt_type' => 'export',
                    'note' => 'Xuất theo phiếu ' . $receipt->code
                        . ($receipt->reason ? ' — ' . (self::REASONS[$receipt->reason] ?? $receipt->reason) : ''),
                    'create_at' => $now,
                    'update_at' => $now,
                ]);
            }

            DB::table('export_receipt')->where('id', $id)->update([
                'status' => 'completed',
                'update_at' => $now,
            ]);
        });

        return response()->json([
            'message' => 'Đã duyệt phiếu & trừ tồn kho',
            'receipt' => $this->findReceipt($id),
        ]);
    }

    public function show($id)
    {
        $receipt = $this->findReceipt($id);
        if (!$receipt) {
            return response()->json(['message' => 'Không tìm thấy phiếu xuất'], 404);
        }

        return response()->json(['receipt' => $receipt]);
    }

    public function destroy($id)
    {
        $receipt = DB::table('export_receipt')->where('id', $id)->first();
        if (!$receipt) {
            return response()->json(['message' => 'Không tìm thấy phiếu xuất'], 404);
        }
        if ($receipt->status === 'completed') {
            return response()->json(['message' => 'Không thể xóa phiếu đã duyệt (đã trừ kho)'], 422);
        }

        DB::transaction(function () use ($id) {
            DB::table('export_receipt_item')->where('receipt_id', $id)->delete();
            DB::table('export_receipt')->where('id', $id)->delete();
        });

        return response()->json(['message' => 'Đã xóa phiếu xuất', 'id' => $id]);
    }

    private function findReceipt($id): ?array
    {
        $r = DB::table('export_receipt')->where('id', $id)->first();
        if (!$r) {
            return null;
        }
        $reasonLabel = $r->reason ? (self::REASONS[$r->reason] ?? $r->reason) : null;

        $items = DB::table('export_receipt_item as i')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->where('i.receipt_id', $id)
            ->get(['i.id', 'i.product_id', 'p.name as product_name', 'p.sku', 'i.quantity', 'i.unit_price', 'i.subtotal']);

        return [
            'id' => $r->id,
            'code' => $r->code,
            'order_id' => $r->order_id,
            'reason' => $r->reason,
            'reason_label' => $reasonLabel,
            'note' => $r->note,
            'export_date' => $r->export_date,
            'status' => $r->status,
            'item_count' => $items->count(),
            'total_amount' => (float) $items->sum('subtotal'),
            'items' => $items,
        ];
    }
}
