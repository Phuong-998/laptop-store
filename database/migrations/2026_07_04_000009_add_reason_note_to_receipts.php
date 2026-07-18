<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bổ sung lý do (reason) và ghi chú (note) cho phiếu nhập/xuất.
 *  - import.reason: purchase | return | other
 *  - export.reason: sale | warranty | return | damaged | other
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('import_receipts', 'reason')) {
            DB::statement("ALTER TABLE `import_receipts` ADD COLUMN `reason` VARCHAR(50) NULL AFTER `supplier_name`");
        }
        if (!Schema::hasColumn('import_receipts', 'note')) {
            DB::statement("ALTER TABLE `import_receipts` ADD COLUMN `note` VARCHAR(255) NULL AFTER `status`");
        }

        if (!Schema::hasColumn('export_receipt', 'reason')) {
            DB::statement("ALTER TABLE `export_receipt` ADD COLUMN `reason` VARCHAR(50) NULL AFTER `order_id`");
        }
        if (!Schema::hasColumn('export_receipt', 'note')) {
            DB::statement("ALTER TABLE `export_receipt` ADD COLUMN `note` VARCHAR(255) NULL AFTER `status`");
        }
    }

    public function down(): void
    {
        foreach (['reason', 'note'] as $col) {
            if (Schema::hasColumn('import_receipts', $col)) {
                DB::statement("ALTER TABLE `import_receipts` DROP COLUMN `$col`");
            }
            if (Schema::hasColumn('export_receipt', $col)) {
                DB::statement("ALTER TABLE `export_receipt` DROP COLUMN `$col`");
            }
        }
    }
};
