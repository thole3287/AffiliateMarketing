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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('shipping_address');
            $table->decimal('total_amount', 10, 2);
            $table->date('order_date');
            $table->string('payment_method');
            $table->string('payment_status')->default('pending');
            $table->enum('order_status', ['ordered', 'confirmed', 'cancelled', 'shipping','completed'])->default('ordered')->after('coupon_code');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
