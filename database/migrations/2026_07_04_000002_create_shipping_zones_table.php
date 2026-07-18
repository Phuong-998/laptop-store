<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->json('provinces')->nullable();       // mảng tỉnh/thành áp dụng, null = toàn quốc
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('free_threshold', 12, 2)->nullable(); // miễn phí khi đơn >= ngưỡng này
            $table->string('estimate_days')->nullable();  // ví dụ "2-3"
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
