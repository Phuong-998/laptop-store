<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');                 // khớp products.id (INT)
            $table->unsignedBigInteger('user_id');          // khớp users.id (BIGINT UNSIGNED)
            $table->tinyInteger('rating');                  // điểm đánh giá 1..5
            $table->text('comment')->nullable();            // nội dung nhận xét
            $table->tinyInteger('status')->default(1);      // 1 = hiển thị, 0 = ẩn/chờ duyệt
            $table->timestamps();

            $table->index('product_id');
            $table->index('user_id');

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->onDelete('cascade');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
