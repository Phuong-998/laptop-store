<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'low_stock_threshold')) {
            DB::statement('ALTER TABLE `products` ADD COLUMN `low_stock_threshold` INT NOT NULL DEFAULT 10 AFTER `stock_quantity`');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'low_stock_threshold')) {
            DB::statement('ALTER TABLE `products` DROP COLUMN `low_stock_threshold`');
        }
    }
};
