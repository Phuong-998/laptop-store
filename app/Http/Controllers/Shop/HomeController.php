<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use ShopHelpers;

    public function index()
    {
        $categories = DB::table('categorise')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $brands = DB::table('brand')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Sản phẩm nổi bật / bán chạy (mới nhất).
        $featuredProducts = $this->productQuery()
            ->orderByDesc('p.created_at')
            ->limit(12)
            ->get()
            ->map(fn ($p) => $this->presentProduct($p));

        // Đang khuyến mãi.
        $saleProducts = $this->productQuery()
            ->whereNotNull('p.sale_price')
            ->whereColumn('p.sale_price', '<', 'p.price')
            ->orderByDesc('p.created_at')
            ->limit(12)
            ->get()
            ->map(fn ($p) => $this->presentProduct($p));

        // Khối sản phẩm theo từng danh mục (kèm thương hiệu xuất hiện trong danh mục cho sub-tab).
        $categorySections = $categories->map(function ($cat) {
            $rows = $this->productQuery()
                ->where('p.category_id', $cat->id)
                ->orderByDesc('p.created_at')
                ->limit(10)
                ->get();

            $brandIds = $rows->pluck('branch_id')->filter()->unique()->values();
            $catBrands = $brandIds->isNotEmpty()
                ? DB::table('brand')->whereIn('id', $brandIds)->get(['id', 'name'])
                : collect();

            return [
                'category' => $cat,
                'brands' => $catBrands,
                'products' => $rows->map(fn ($p) => $this->presentProduct($p)),
            ];
        })->filter(fn ($s) => $s['products']->isNotEmpty())->values();

        $totalProducts = DB::table('products')->where('status', 1)->count();

        return view('shop.home', compact(
            'categories', 'brands', 'featuredProducts', 'saleProducts', 'categorySections', 'totalProducts'
        ));
    }
}
