<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('stock_transaction', 'note')) {
            DB::statement('ALTER TABLE `stock_transaction` ADD COLUMN `note` VARCHAR(255) NULL AFTER `receipt_type`');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_transaction', 'note')) {
            DB::statement('ALTER TABLE `stock_transaction` DROP COLUMN `note`');
        }
    }
};
