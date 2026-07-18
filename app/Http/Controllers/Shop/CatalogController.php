<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    use ShopHelpers;

    /**
     * Danh sách sản phẩm: lọc theo danh mục / thương hiệu / khoảng giá, tìm kiếm, sắp xếp, phân trang.
     */
    public function index(Request $request)
    {
        $query = $this->productQuery();

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('p.name', 'like', "%{$q}%")
                    ->orWhere('p.sku', 'like', "%{$q}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('p.category_id', (int) $request->input('category'));
        }
        if ($request->filled('brand')) {
            $query->where('p.branch_id', (int) $request->input('brand'));
        }
        if ($request->filled('min_price')) {
            $query->where(DB::raw('COALESCE(NULLIF(p.sale_price,0), p.price)'), '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where(DB::raw('COALESCE(NULLIF(p.sale_price,0), p.price)'), '<=', (float) $request->input('max_price'));
        }

        switch ($request->input('sort')) {
            case 'price_asc':
                $query->orderBy(DB::raw('COALESCE(NULLIF(p.sale_price,0), p.price)'), 'asc');
                break;
            case 'price_desc':
                $query->orderBy(DB::raw('COALESCE(NULLIF(p.sale_price,0), p.price)'), 'desc');
                break;
            case 'name':
                $query->orderBy('p.name');
                break;
            default:
                $query->orderByDesc('p.created_at');
        }

        $paginator = $query->paginate(12)->withQueryString();
        $products = collect($paginator->items())->map(fn ($p) => $this->presentProduct($p));

        $categories = DB::table('categorise')->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $brands = DB::table('brand')->where('status', 1)->orderBy('name')->get(['id', 'name']);

        $filters = $request->only(['q', 'category', 'brand', 'min_price', 'max_price', 'sort']);

        return view('shop.products', compact('products', 'paginator', 'categories', 'brands', 'filters'));
    }

    /**
     * Chi tiết sản phẩm: ảnh, thông số, sản phẩm liên quan, đánh giá.
     */
    public function show($slug)
    {
        $product = DB::table('products')->where('slug', $slug)->where('status', 1)->first();
        if (!$product) {
            abort(404);
        }

        $data = $this->presentProduct($product);
        $data['category_name'] = $product->category_id
            ? DB::table('categorise')->where('id', $product->category_id)->value('name')
            : null;
        $data['brand_name'] = $product->branch_id
            ? DB::table('brand')->where('id', $product->branch_id)->value('name')
            : null;

        $images = DB::table('product_images')
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->pluck('image')
            ->toArray();
        if (empty($images)) {
            $images = [$data['image']];
        }

        $specs = DB::table('product_specs')->where('product_id', $product->id)->first();

        $reviews = DB::table('reviews as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.product_id', $product->id)
            ->where('r.status', 1)
            ->orderByDesc('r.created_at')
            ->get(['r.id', 'r.rating', 'r.comment', 'r.created_at', 'u.name as user_name']);

        $reviewStats = [
            'count' => $reviews->count(),
            'avg' => $reviews->count() ? round($reviews->avg('rating'), 1) : 0,
        ];

        $related = $this->productQuery()
            ->where('p.id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('p.category_id', $product->category_id))
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->map(fn ($p) => $this->presentProduct($p));

        // Người dùng hiện tại đã mua sản phẩm này chưa (để gợi ý đánh giá).
        $canReview = auth()->check();

        return view('shop.detail', [
            'product' => $data,
            'images' => $images,
            'specs' => $specs,
            'reviews' => $reviews,
            'reviewStats' => $reviewStats,
            'related' => $related,
            'canReview' => $canReview,
        ]);
    }
}
