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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 8, 2)->default(0); // Số dư
            $table->string('affiliate_code')->unique()->nullable(); // Mã tiếp thị liên kết
            // $table->unsignedBigInteger('referral_user_id')->nullable(); // ID người dùng giới thiệu
            // $table->foreign('referral_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
