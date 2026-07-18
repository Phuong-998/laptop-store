<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = DB::table('categorise')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'parent_id' => $category->parent_id,
                    'description' => '',
                    'icon' => 'bi-tag',
                    'active' => $category->status,
                    'createdAt' => $category->created_at,
                ];
            });

        return view('admin.category', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
        ]);

        $now = now();
        $id = DB::table('categorise')->insertGetId([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => $request->boolean('active') ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'message' => 'Da them danh muc',
            'category' => [
                'id' => $id,
                'name' => $validated['name'],
                'parent_id' => $validated['parent_id'] ?? null,
                'description' => '',
                'icon' => 'bi-tag',
                'active' => $request->boolean('active'),
                'createdAt' => $now->toDateTimeString(),
            ],
        ]);
    }

    public function delete($id)
    {
        $category = DB::table('categorise')->where('id', $id)->first();

        if (!$category) {
            return response()->json([
                'message' => 'Khong tim thay danh muc',
            ], 404);
        }

        $hasChildren = DB::table('categorise')->where('parent_id', $id)->exists();

        if ($hasChildren) {
            return response()->json([
                'message' => 'Khong the xoa danh muc dang co danh muc con',
            ], 422);
        }

        DB::table('categorise')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Da xoa danh muc',
            'id' => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = DB::table('categorise')->where('id', $id)->first();

        if (!$category) {
            return response()->json([
                'message' => 'Khong tim thay danh muc',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
        ]);

        $now = now();

        if (!empty($validated['parent_id']) && (int) $validated['parent_id'] === (int) $id) {
            return response()->json([
                'message' => 'Danh muc cha khong hop le',
            ], 422);
        }

        DB::table('categorise')->where('id', $id)->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'status' => $request->boolean('active') ? 1 : 0,
            'updated_at' => $now,
        ]);

        return response()->json([
            'message' => 'Cap nhat danh muc thanh cong',
            'category' => [
                'id' => (int) $id,
                'name' => $validated['name'],
                'parent_id' => $validated['parent_id'] ?? null,
                'description' => '',
                'icon' => 'bi-tag',
                'active' => $request->boolean('active'),
                'createdAt' => $category->created_at,
            ],
        ]);
    }
}
