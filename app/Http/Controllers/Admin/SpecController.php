<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpecController extends Controller
{
    public function index()
    {
        // Danh sách sản phẩm kèm thông số (nếu có)
        $specs = DB::table('products as p')
            ->leftJoin('product_specs as s', 's.product_id', '=', 'p.id')
            ->orderBy('p.name')
            ->get([
                'p.id as product_id',
                'p.name as product_name',
                's.id as spec_id',
                's.cpu', 's.ram', 's.storage', 's.gpu',
                's.screen', 's.battery', 's.weight', 's.os',
            ])
            ->map(fn ($row) => $this->formatSpec($row));

        return view('admin.spec', compact('specs'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateSpec($request, true);

        $product = DB::table('products')->where('id', $validated['product_id'])->first();
        if (!$product) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm'], 404);
        }

        if (DB::table('product_specs')->where('product_id', $validated['product_id'])->exists()) {
            return response()->json([
                'message' => 'Sản phẩm này đã có thông số, hãy chỉnh sửa thay vì thêm mới',
            ], 422);
        }

        $now = now();
        $specId = DB::table('product_specs')->insertGetId([
            'product_id' => $validated['product_id'],
            'cpu' => $validated['cpu'] ?? null,
            'ram' => $validated['ram'] ?? null,
            'storage' => $validated['storage'] ?? null,
            'gpu' => $validated['gpu'] ?? null,
            'screen' => $validated['screen'] ?? null,
            'battery' => $validated['battery'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'os' => $validated['os'] ?? null,
            'created_at' => $now,
            'update_at' => $now,
        ]);

        return response()->json([
            'message' => 'Đã thêm thông số kỹ thuật',
            'spec' => $this->fetchSpecRow($specId),
        ]);
    }

    public function update(Request $request, $id)
    {
        $spec = DB::table('product_specs')->where('id', $id)->first();
        if (!$spec) {
            return response()->json(['message' => 'Không tìm thấy thông số'], 404);
        }

        $validated = $this->validateSpec($request, false);

        DB::table('product_specs')->where('id', $id)->update([
            'cpu' => $validated['cpu'] ?? null,
            'ram' => $validated['ram'] ?? null,
            'storage' => $validated['storage'] ?? null,
            'gpu' => $validated['gpu'] ?? null,
            'screen' => $validated['screen'] ?? null,
            'battery' => $validated['battery'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'os' => $validated['os'] ?? null,
            'update_at' => now(),
        ]);

        return response()->json([
            'message' => 'Cập nhật thông số thành công',
            'spec' => $this->fetchSpecRow($id),
        ]);
    }

    public function delete($id)
    {
        $spec = DB::table('product_specs')->where('id', $id)->first();
        if (!$spec) {
            return response()->json(['message' => 'Không tìm thấy thông số'], 404);
        }

        DB::table('product_specs')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Đã xóa thông số',
            'id' => $id,
            'product_id' => $spec->product_id,
        ]);
    }

    private function validateSpec(Request $request, bool $withProduct): array
    {
        $rules = [
            'cpu' => ['nullable', 'string', 'max:255'],
            'ram' => ['nullable', 'string', 'max:255'],
            'storage' => ['nullable', 'string', 'max:255'],
            'gpu' => ['nullable', 'string', 'max:255'],
            'screen' => ['nullable', 'string', 'max:255'],
            'battery' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'os' => ['nullable', 'string', 'max:255'],
        ];
        if ($withProduct) {
            $rules['product_id'] = ['required', 'integer'];
        }

        return $request->validate($rules);
    }

    private function fetchSpecRow($specId): ?array
    {
        $row = DB::table('products as p')
            ->join('product_specs as s', 's.product_id', '=', 'p.id')
            ->where('s.id', $specId)
            ->first([
                'p.id as product_id',
                'p.name as product_name',
                's.id as spec_id',
                's.cpu', 's.ram', 's.storage', 's.gpu',
                's.screen', 's.battery', 's.weight', 's.os',
            ]);

        return $this->formatSpec($row);
    }

    private function formatSpec($row): ?array
    {
        if (!$row) {
            return null;
        }

        return [
            'product_id' => $row->product_id,
            'product_name' => $row->product_name,
            'spec_id' => $row->spec_id,
            'cpu' => $row->cpu,
            'ram' => $row->ram,
            'storage' => $row->storage,
            'gpu' => $row->gpu,
            'screen' => $row->screen,
            'battery' => $row->battery,
            'weight' => $row->weight,
            'os' => $row->os,
        ];
    }
}
