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
        Schema::create('product_offer', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->tinyInteger('hot_deal')->default(0);
            $table->tinyInteger('featured_product')->default(0);
            $table->tinyInteger('special_offer')->default(0);
            $table->tinyInteger('special_deal')->default(0);
            $table->unsignedBigInteger('offer_product_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_offer');
    }
};
