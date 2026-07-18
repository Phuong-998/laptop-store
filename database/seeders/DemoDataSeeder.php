<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dữ liệu mẫu cho storefront: thương hiệu, danh mục, sản phẩm + thông số + ảnh,
 * mã giảm giá, khu vực giao hàng. Idempotent — chạy lại không tạo trùng.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        /* ---------- Danh mục ---------- */
        $catNames = ['Laptop Văn Phòng', 'Laptop Gaming', 'Laptop Đồ Họa', 'Laptop Mỏng Nhẹ'];
        $cat = [];
        foreach ($catNames as $name) {
            $row = DB::table('categorise')->where('name', $name)->first();
            $cat[$name] = $row->id ?? DB::table('categorise')->insertGetId([
                'name' => $name, 'parent_id' => 0, 'slug' => Str::slug($name), 'status' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        /* ---------- Thương hiệu ---------- */
        $brandNames = ['Dell', 'Asus', 'Lenovo', 'HP', 'Acer', 'MSI', 'Apple'];
        $brand = [];
        foreach ($brandNames as $name) {
            $row = DB::table('brand')->where('name', $name)->first();
            $brand[$name] = $row->id ?? DB::table('brand')->insertGetId([
                'name' => $name, 'slug' => Str::slug($name), 'description' => 'Laptop ' . $name . ' chính hãng',
                'status' => 1, 'created_at' => $now, 'update_at' => $now,
            ]);
        }

        /* ---------- Sản phẩm ---------- */
        // [name, brand, category, price, sale(0=không), stock, cpu, ram, storage, gpu, screen, os, weight]
        $VP = 'Laptop Văn Phòng'; $GM = 'Laptop Gaming'; $DH = 'Laptop Đồ Họa'; $MN = 'Laptop Mỏng Nhẹ';
        $items = [
            ['Dell Inspiron 15 3520', 'Dell', $VP, 15490000, 14290000, 30, 'Intel Core i5-1235U', '8GB DDR4', '512GB SSD NVMe', 'Intel Iris Xe', '15.6" FHD', 'Windows 11', 1.65],
            ['Dell Latitude 3440', 'Dell', $VP, 18990000, 0, 18, 'Intel Core i5-1335U', '16GB DDR4', '512GB SSD NVMe', 'Intel Iris Xe', '14" FHD', 'Windows 11', 1.45],
            ['Dell XPS 13 9340', 'Dell', $MN, 38990000, 35690000, 12, 'Intel Core Ultra 7 155H', '16GB LPDDR5', '1TB SSD NVMe', 'Intel Arc', '13.4" FHD+', 'Windows 11', 1.19],
            ['Asus Vivobook 15 X1504', 'Asus', $VP, 13990000, 12490000, 40, 'Intel Core i3-1215U', '8GB DDR4', '512GB SSD NVMe', 'Intel UHD', '15.6" FHD', 'Windows 11', 1.7],
            ['Asus TUF Gaming F15', 'Asus', $GM, 24990000, 22990000, 22, 'Intel Core i7-12700H', '16GB DDR5', '512GB SSD NVMe', 'RTX 4050 6GB', '15.6" FHD 144Hz', 'Windows 11', 2.2],
            ['Asus ROG Zephyrus G14', 'Asus', $GM, 42990000, 39990000, 9, 'AMD Ryzen 9 7940HS', '32GB DDR5', '1TB SSD NVMe', 'RTX 4060 8GB', '14" QHD+ 165Hz', 'Windows 11', 1.5],
            ['Asus Zenbook 14 OLED', 'Asus', $MN, 27990000, 25490000, 16, 'Intel Core Ultra 5 125H', '16GB LPDDR5', '512GB SSD NVMe', 'Intel Arc', '14" 2.8K OLED', 'Windows 11', 1.28],
            ['Asus ProArt P16', 'Asus', $DH, 52990000, 49990000, 6, 'AMD Ryzen AI 9 HX370', '32GB LPDDR5X', '1TB SSD NVMe', 'RTX 4070 8GB', '16" 4K OLED', 'Windows 11', 1.85],
            ['Lenovo IdeaPad Slim 3', 'Lenovo', $VP, 12990000, 11490000, 45, 'Intel Core i5-12450H', '16GB DDR4', '512GB SSD NVMe', 'Intel UHD', '15.6" FHD', 'Windows 11', 1.62],
            ['Lenovo ThinkPad E14 Gen 5', 'Lenovo', $VP, 18490000, 0, 20, 'Intel Core i5-1335U', '16GB DDR4', '512GB SSD NVMe', 'Intel Iris Xe', '14" FHD', 'Windows 11', 1.43],
            ['Lenovo LOQ 15IRX9', 'Lenovo', $GM, 21990000, 19990000, 25, 'Intel Core i5-12450HX', '16GB DDR5', '512GB SSD NVMe', 'RTX 3050 6GB', '15.6" FHD 144Hz', 'Windows 11', 2.4],
            ['Lenovo Yoga Slim 7', 'Lenovo', $MN, 26990000, 23990000, 14, 'AMD Ryzen 7 8840U', '16GB LPDDR5', '1TB SSD NVMe', 'AMD Radeon 780M', '14" 2.8K OLED', 'Windows 11', 1.28],
            ['HP Pavilion 15 eg3xxx', 'HP', $VP, 16490000, 14990000, 28, 'Intel Core i5-1340P', '16GB DDR4', '512GB SSD NVMe', 'Intel Iris Xe', '15.6" FHD', 'Windows 11', 1.75],
            ['HP Victus 16', 'HP', $GM, 25490000, 23490000, 17, 'Intel Core i7-13700H', '16GB DDR5', '512GB SSD NVMe', 'RTX 4060 8GB', '16.1" FHD 144Hz', 'Windows 11', 2.3],
            ['HP Spectre x360 14', 'HP', $MN, 41990000, 37990000, 8, 'Intel Core Ultra 7 155H', '16GB LPDDR5', '1TB SSD NVMe', 'Intel Arc', '14" 2.8K OLED', 'Windows 11', 1.4],
            ['Acer Aspire 5 A515', 'Acer', $VP, 13490000, 11990000, 35, 'Intel Core i5-1235U', '16GB DDR4', '512GB SSD NVMe', 'Intel Iris Xe', '15.6" FHD', 'Windows 11', 1.78],
            ['Acer Nitro 5 AN515', 'Acer', $GM, 22490000, 20490000, 19, 'Intel Core i7-12650H', '16GB DDR5', '512GB SSD NVMe', 'RTX 4050 6GB', '15.6" FHD 144Hz', 'Windows 11', 2.5],
            ['MSI Modern 14 C13M', 'MSI', $VP, 14990000, 0, 24, 'Intel Core i5-1335U', '16GB DDR4', '512GB SSD NVMe', 'Intel Iris Xe', '14" FHD', 'Windows 11', 1.4],
            ['MSI Katana 15 B13V', 'MSI', $GM, 26990000, 24990000, 15, 'Intel Core i7-13620H', '16GB DDR5', '1TB SSD NVMe', 'RTX 4060 8GB', '15.6" FHD 144Hz', 'Windows 11', 2.25],
            ['Apple MacBook Air M2 13"', 'Apple', $MN, 27990000, 25990000, 26, 'Apple M2 8-core', '8GB Unified', '256GB SSD', 'Apple GPU 8-core', '13.6" Retina', 'macOS', 1.24],
            ['Apple MacBook Pro 14 M3', 'Apple', $DH, 45990000, 0, 10, 'Apple M3 8-core', '16GB Unified', '512GB SSD', 'Apple GPU 10-core', '14.2" Liquid Retina XDR', 'macOS', 1.55],
            ['Gigabyte Aero 16 OLED', 'MSI', $DH, 48990000, 45990000, 7, 'Intel Core i7-13700H', '16GB DDR5', '1TB SSD NVMe', 'RTX 4070 8GB', '16" 4K OLED', 'Windows 11', 2.1],
        ];

        foreach ($items as $it) {
            [$name, $bn, $cn, $price, $sale, $stock, $cpu, $ram, $storage, $gpu, $screen, $os, $weight] = $it;
            $slug = Str::slug($name);
            // Tên hiển thị đầy đủ kiểu retail: "Laptop <Model> (CPU | RAM | SSD | GPU | Màn hình | OS)".
            $specSummary = implode(' | ', array_filter([$cpu, $ram, $storage, $gpu, $screen, $os]));
            $fullName = 'Laptop ' . $name . ($specSummary ? ' (' . $specSummary . ')' : '');

            // Đã tồn tại (theo slug) → chỉ cập nhật lại tên đầy đủ, giữ nguyên slug/URL.
            $existing = DB::table('products')->where('slug', $slug)->first();
            if ($existing) {
                DB::table('products')->where('id', $existing->id)->update(['name' => $fullName, 'update_at' => $now]);
                continue;
            }

            $img = 'https://placehold.co/600x600/eef0f2/E1121D?text=' . rawurlencode($name);
            $desc = '<p><strong>' . e($fullName) . '</strong></p>'
                . '<p>Máy chính hãng, nguyên seal, đầy đủ phụ kiện và hóa đơn VAT. Bảo hành chính hãng 24 tháng, hỗ trợ trả góp 0%.</p>';

            $pid = DB::table('products')->insertGetId([
                'category_id' => $cat[$cn],
                'branch_id' => $brand[$bn],
                'name' => $fullName,
                'slug' => $slug,
                'sku' => strtoupper(Str::slug($bn, '')) . '-' . strtoupper(Str::random(5)),
                'price' => $price,
                'sale_price' => $sale ?: null,
                'description' => $desc,
                'image' => $img,
                'warranty' => '24 tháng chính hãng',
                'stock_quantity' => $stock,
                'low_stock_threshold' => 5,
                'status' => 1,
                'created_at' => $now,
                'update_at' => $now,
            ]);

            DB::table('product_specs')->insert([
                'product_id' => $pid, 'cpu' => $cpu, 'ram' => $ram, 'storage' => $storage, 'gpu' => $gpu,
                'screen' => $screen, 'battery' => '3-cell Li-ion', 'weight' => $weight, 'os' => $os,
                'created_at' => $now, 'update_at' => $now,
            ]);

            DB::table('product_images')->insert([
                'product_id' => $pid, 'image' => $img, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        /* ---------- Gắn lại danh mục cho sản phẩm mồ côi (danh mục cũ đã xóa) ---------- */
        DB::table('products')
            ->whereNotIn('category_id', array_values($cat))
            ->update(['category_id' => $cat['Laptop Đồ Họa'], 'update_at' => $now]);

        /* ---------- Mã giảm giá ---------- */
        $coupons = [
            ['WELCOME10', 'percent', 10, 10000000, 2000000, 200],
            ['GIAM500K', 'fixed', 500000, 15000000, 0, 100],
            ['FREESHIP', 'shipping', 0, 0, 0, 500],
        ];
        foreach ($coupons as [$code, $type, $value, $min, $max, $limit]) {
            if (DB::table('coupons')->where('code', $code)->exists()) {
                continue;
            }
            DB::table('coupons')->insert([
                'code' => $code, 'type' => $type, 'value' => $value,
                'min_order_amount' => $min, 'max_discount_amount' => $max,
                'start_date' => $now, 'end_date' => $now->copy()->addMonths(3),
                'use_limit' => $limit, 'use_count' => 0, 'status' => '1',
                'create_at' => $now, 'update_at' => $now,
            ]);
        }

        /* ---------- Khu vực giao hàng ---------- */
        $zones = [
            ['Nội thành Hà Nội / TP.HCM', ['Hà Nội', 'TP. Hồ Chí Minh'], 0, 0, '1-2 ngày'],
            ['Miền Bắc', ['Hải Phòng', 'Bắc Ninh', 'Quảng Ninh'], 25000, 10000000, '2-4 ngày'],
            ['Miền Trung', ['Đà Nẵng', 'Huế', 'Nghệ An'], 35000, 15000000, '3-5 ngày'],
            ['Miền Nam', ['Cần Thơ', 'Bình Dương', 'Đồng Nai'], 30000, 12000000, '2-4 ngày'],
        ];
        foreach ($zones as [$region, $provinces, $fee, $free, $days]) {
            if (DB::table('shipping_zones')->where('region', $region)->exists()) {
                continue;
            }
            DB::table('shipping_zones')->insert([
                'region' => $region, 'provinces' => json_encode($provinces, JSON_UNESCAPED_UNICODE),
                'fee' => $fee, 'free_threshold' => $free, 'estimate_days' => $days, 'status' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $this->command?->info('DemoDataSeeder: hoàn tất — sản phẩm, danh mục, thương hiệu, coupon, khu vực giao hàng.');
    }
}
