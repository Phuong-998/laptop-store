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
       $brand = DB::table('brand')->orderByDesc('created_at')->get();
       return view('admin.brand',compact('brand'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);
        $now = now();
        $exitsBrand = DB::table('brand')->where('name',$$validated['name'])->exists();
        if($exitsBrand){
            return response()->json([
               'message' => 'Danh muc da ton tai',
            ]);
        }
        $insertBrand = DB::table('brand')->insertGetId([
            'name' => $validated['name'],
            'status' => $request->boolean('status') ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
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
}
