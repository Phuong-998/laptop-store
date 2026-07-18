<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng products/product_images được tạo tay bằng Navicat nên thiếu:
     *  - products.id chưa AUTO_INCREMENT  => không insert được sản phẩm mới
     *  - product_images chưa có product_id => không gắn được nhiều ảnh cho 1 sản phẩm
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `products` MODIFY `id` INT NOT NULL AUTO_INCREMENT');

        if (!Schema::hasColumn('product_images', 'product_id')) {
            DB::statement('ALTER TABLE `product_images` ADD COLUMN `product_id` INT NULL AFTER `id`');
            DB::statement('ALTER TABLE `product_images` ADD INDEX `product_images_product_id_index` (`product_id`)');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_images', 'product_id')) {
            DB::statement('ALTER TABLE `product_images` DROP INDEX `product_images_product_id_index`');
            DB::statement('ALTER TABLE `product_images` DROP COLUMN `product_id`');
        }

        DB::statement('ALTER TABLE `products` MODIFY `id` INT NOT NULL');
    }
};
