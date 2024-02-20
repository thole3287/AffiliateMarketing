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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('coupon_code', 250);
            $table->smallInteger('discount_amount');
            $table->timestamp('expiration_date')->useCurrent();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->tinyInteger('coupon_status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
