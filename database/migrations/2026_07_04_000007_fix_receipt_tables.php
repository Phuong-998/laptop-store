<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chuẩn hoá các bảng phiếu nhập/xuất để dùng được với insertGetId:
 *  - Bật AUTO_INCREMENT cho khoá chính (đang là INT NOT NULL thường).
 *  - Sửa cột sai kiểu / sai chính tả: total_amout, cretae_at, export_receipt.code, quanity.
 */
return new class extends Migration
{
    public function up(): void
    {
        // suppliers
        DB::statement('ALTER TABLE `suppliers` MODIFY `id` INT NOT NULL AUTO_INCREMENT');

        // import_receipts
        DB::statement('ALTER TABLE `import_receipts` MODIFY `id` INT NOT NULL AUTO_INCREMENT');
        if (Schema::hasColumn('import_receipts', 'total_amout')) {
            DB::statement('ALTER TABLE `import_receipts` CHANGE `total_amout` `total_amount` DECIMAL(12,2) NULL DEFAULT NULL');
        }
        if (Schema::hasColumn('import_receipts', 'cretae_at')) {
            DB::statement('ALTER TABLE `import_receipts` CHANGE `cretae_at` `created_at` TIMESTAMP NULL DEFAULT NULL');
        }

        // import_receipt_items
        DB::statement('ALTER TABLE `import_receipt_items` MODIFY `id` INT NOT NULL AUTO_INCREMENT');

        // export_receipt
        DB::statement('ALTER TABLE `export_receipt` MODIFY `id` INT NOT NULL AUTO_INCREMENT');
        DB::statement('ALTER TABLE `export_receipt` MODIFY `code` VARCHAR(255) NULL DEFAULT NULL');

        // export_receipt_item
        DB::statement('ALTER TABLE `export_receipt_item` MODIFY `id` INT NOT NULL AUTO_INCREMENT');
        if (Schema::hasColumn('export_receipt_item', 'quanity')) {
            DB::statement('ALTER TABLE `export_receipt_item` CHANGE `quanity` `quantity` INT NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        // Không đảo ngược AUTO_INCREMENT (không cần cho môi trường phát triển).
        if (Schema::hasColumn('import_receipts', 'total_amount')) {
            DB::statement('ALTER TABLE `import_receipts` CHANGE `total_amount` `total_amout` VARCHAR(255) NULL DEFAULT NULL');
        }
        if (Schema::hasColumn('import_receipts', 'created_at')) {
            DB::statement('ALTER TABLE `import_receipts` CHANGE `created_at` `cretae_at` VARCHAR(255) NULL DEFAULT NULL');
        }
        if (Schema::hasColumn('export_receipt_item', 'quantity')) {
            DB::statement('ALTER TABLE `export_receipt_item` CHANGE `quantity` `quanity` INT NULL DEFAULT NULL');
        }
    }
};
