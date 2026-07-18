<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = DB::table('products')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($product) => $this->formatProduct($product));

        $categories = DB::table('categorise')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = DB::table('brand')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.product', compact('products', 'categories', 'brands'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateProduct($request);

        $exists = DB::table('products')->where('name', $validated['name'])->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Sản phẩm đã tồn tại',
            ], 422);
        }

        $now = now();
        $productId = DB::table('products')->insertGetId([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'category_id' => $validated['category_id'],
            'branch_id' => $validated['branch_id'],
            'sku' => $validated['sku'] ?? null,
            'price' => $validated['price'],
            'sale_price' => $validated['sale_price'] ?? null,
            'description' => $validated['description'] ?? null,
            'image' => null,
            'warranty' => $validated['warranty'] ?? null,
            'stock_quantity' => $validated['stock_quantity'] ?? 0,
            'status' => $request->boolean('status') ? 1 : 0,
            'created_at' => $now,
            'update_at' => $now,
        ]);

        $uploaded = $this->storeImages($request, $productId, $now);
        if (!empty($uploaded)) {
            DB::table('products')->where('id', $productId)->update(['image' => $uploaded[0]]);
        }

        return response()->json([
            'message' => 'Đã thêm sản phẩm',
            'product' => $this->formatProduct(DB::table('products')->find($productId)),
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm',
            ], 404);
        }

        $validated = $this->validateProduct($request);

        $exists = DB::table('products')
            ->where('name', $validated['name'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Sản phẩm đã tồn tại',
            ], 422);
        }

        $now = now();
        DB::table('products')->where('id', $id)->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'category_id' => $validated['category_id'],
            'branch_id' => $validated['branch_id'],
            'sku' => $validated['sku'] ?? null,
            'price' => $validated['price'],
            'sale_price' => $validated['sale_price'] ?? null,
            'description' => $validated['description'] ?? null,
            'warranty' => $validated['warranty'] ?? null,
            'stock_quantity' => $validated['stock_quantity'] ?? 0,
            'status' => $request->boolean('status') ? 1 : 0,
            'update_at' => $now,
        ]);

        // Xóa các ảnh cũ mà người dùng đã bỏ trong form
        $removed = $validated['removed_images'] ?? [];
        if (!empty($removed)) {
            $rows = DB::table('product_images')
                ->where('product_id', $id)
                ->whereIn('id', $removed)
                ->get();
            foreach ($rows as $row) {
                $this->deleteImageFile($row->image);
            }
            DB::table('product_images')
                ->where('product_id', $id)
                ->whereIn('id', $removed)
                ->delete();
        }

        $this->storeImages($request, (int) $id, $now);

        // Đảm bảo ảnh đại diện vẫn còn tồn tại, nếu không thì lấy ảnh đầu còn lại
        $fresh = DB::table('products')->where('id', $id)->first();
        $primaryStillExists = $fresh->image
            && DB::table('product_images')->where('product_id', $id)->where('image', $fresh->image)->exists();
        if (!$primaryStillExists) {
            $firstImage = DB::table('product_images')->where('product_id', $id)->orderBy('id')->value('image');
            DB::table('products')->where('id', $id)->update(['image' => $firstImage]);
        }

        return response()->json([
            'message' => 'Cập nhật sản phẩm thành công',
            'product' => $this->formatProduct(DB::table('products')->find($id)),
        ]);
    }

    public function delete($id)
    {
        $product = DB::table('products')->where('id', $id)->first();
        if (!$product) {
            return response()->json([
                'message' => 'Không tìm thấy sản phẩm',
            ], 404);
        }

        // Xóa file ảnh vật lý gắn với sản phẩm
        $images = DB::table('product_images')->where('product_id', $id)->pluck('image');
        foreach ($images as $image) {
            $this->deleteImageFile($image);
        }
        $this->deleteImageFile($product->image);

        DB::table('product_images')->where('product_id', $id)->delete();
        DB::table('products')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Đã xóa sản phẩm',
            'id' => $id,
        ]);
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer'],
            'branch_id' => ['required', 'integer'],
            'sku' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:4096'],
            'removed_images' => ['nullable', 'array'],
            'removed_images.*' => ['integer'],
        ]);
    }

    /**
     * Lưu các file ảnh upload vào storage/public/products và ghi vào bảng product_images.
     * Trả về mảng URL public của các ảnh vừa lưu.
     */
    private function storeImages(Request $request, int $productId, $now): array
    {
        if (!$request->hasFile('images')) {
            return [];
        }

        $urls = [];
        foreach ($request->file('images') as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $path = $file->store('products', 'public');
            $url = Storage::url($path); // /storage/products/xxx.jpg
            DB::table('product_images')->insert([
                'product_id' => $productId,
                'image' => $url,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $urls[] = $url;
        }

        return $urls;
    }

    private function deleteImageFile(?string $url): void
    {
        if (!$url) {
            return;
        }
        // /storage/products/xxx.jpg -> products/xxx.jpg trên disk public
        $path = Str::after($url, '/storage/');
        if ($path !== $url && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function formatProduct($product): ?array
    {
        if (!$product) {
            return null;
        }

        $images = DB::table('product_images')
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->get(['id', 'image'])
            ->map(fn ($row) => ['id' => $row->id, 'url' => $row->image])
            ->toArray();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category_id' => $product->category_id,
            'branch_id' => $product->branch_id,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'image' => $product->image,
            'images' => $images,
            'description' => $product->description,
            'warranty' => $product->warranty,
            'stock_quantity' => $product->stock_quantity,
            'status' => $product->status,
            'created_at' => $product->created_at,
        ];
    }
}
