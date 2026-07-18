<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    //
    public function index()
    {
       $brand = DB::table('brand')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'active' => $brand->status,
                    'created_at' => $brand->created_at,
                ];
            });

        return view('admin.brand', compact('brand'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);
        $now = now();
        $exitsBrand = DB::table('brand')->where('name',$validated['name'])->exists();
        if($exitsBrand){
            return response()->json([
               'message' => 'Danh muc da ton tai',
            ]);
        }
        $insertBrand = DB::table('brand')->insertGetId([
            'name' => $validated['name'],
            'status' => $request->boolean('active') ? 1 : 0,
            'created_at' => $now,
            'update_at' => $now,
        ]);
        return response()->json([
            'message' => 'Da them thuong hieu',
            'brand' => [
                'id' => $insertBrand,
                'name' => $validated['name'],
                'active' => $request->boolean('active'),
                'createdAt' => $now->toDateTimeString(),
            ]
        ]);
    }

    public function updateBrand(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);
        $exitsBrand = DB::table('brand')->where('name',$validated['name'])->where('id','!=',$id)->exists();
        if($exitsBrand){
            return response()->json([
                'message' => 'Thương hiệu đã tồn tại'
            ]);
        }
        $now = now();
        DB::table('brand')->where('id',$id)->update([
            'name' => $validated['name'],
            'status' => $request->boolean('active') ? 1 : 0,
            'update_at' => $now
        ]);
        return response()->json([
            'message' => 'Cập nhật thương hiệu thành công',
            'brand' => [
                'id' => (int)$id,
                'name' => $validated['name'],
                'active' => $request->boolean('active') ? 1: 0,
                'update' => $now->toDateTimeString()
            ]
        ]);
    }

    public function delete($id)
    {
        DB::table('brand')->where('id',$id)->delete();
        return response()->json([
            'message' => 'Đã xóa thành công',
            'id' => $id
        ]);
    }
}
