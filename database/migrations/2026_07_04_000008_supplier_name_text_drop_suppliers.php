<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nhà cung cấp chuyển sang nhập tay dạng text trên phiếu nhập,
 * bỏ hẳn bảng suppliers và cột khoá ngoại supplier_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('import_receipts', 'supplier_name')) {
            DB::statement('ALTER TABLE `import_receipts` ADD COLUMN `supplier_name` VARCHAR(255) NULL AFTER `code`');
        }
        if (Schema::hasColumn('import_receipts', 'supplier_id')) {
            DB::statement('ALTER TABLE `import_receipts` DROP COLUMN `supplier_id`');
        }

        Schema::dropIfExists('suppliers');
    }

    public function down(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->dateTime('create_at')->nullable();
            $table->dateTime('update_at')->nullable();
        });

        if (!Schema::hasColumn('import_receipts', 'supplier_id')) {
            DB::statement('ALTER TABLE `import_receipts` ADD COLUMN `supplier_id` INT NULL AFTER `code`');
        }
        if (Schema::hasColumn('import_receipts', 'supplier_name')) {
            DB::statement('ALTER TABLE `import_receipts` DROP COLUMN `supplier_name`');
        }
    }
};
