<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ID của người dùng chia sẻ liên kết
            $table->unsignedBigInteger('product_id'); // ID của sản phẩm
            $table->decimal('commission_amount', 8, 2)->default(0); // Số tiền hoa hồng
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_links');
    }
};
