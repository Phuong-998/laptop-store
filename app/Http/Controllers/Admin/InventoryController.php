<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $products = DB::table('products as p')
            ->leftJoin('categorise as c', 'c.id', '=', 'p.category_id')
            ->orderBy('p.stock_quantity')
            ->get([
                'p.id', 'p.name', 'p.sku', 'p.image',
                'p.stock_quantity', 'p.low_stock_threshold', 'p.update_at',
                'p.category_id', 'c.name as category_name',
            ])
            ->map(fn ($row) => $this->formatRow($row));

        $categories = DB::table('categorise')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.inventory', compact('products', 'categories'));
    }

    public function history($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm'], 404);
        }

        $history = DB::table('stock_transaction')
            ->where('product_id', $id)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($t) => [
                'date' => $t->create_at,
                'type' => $t->transaction_type,
                'quantity' => $t->quanity_change,
                'before' => $t->quanity_before,
                'after' => $t->quanity_after,
                'note' => $t->note,
            ]);

        return response()->json(['history' => $history]);
    }

    private function formatRow($row): ?array
    {
        if (!$row) {
            return null;
        }

        return [
            'id' => $row->id,
            'name' => $row->name,
            'sku' => $row->sku,
            'image' => $row->image,
            'stock_quantity' => (int) $row->stock_quantity,
            'low_stock_threshold' => (int) $row->low_stock_threshold,
            'update_at' => $row->update_at,
            'category_id' => $row->category_id,
            'category_name' => $row->category_name,
        ];
    }
}
