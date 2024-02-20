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
        Schema::create('products', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('product_name');
            $table->string('product_code');
            $table->text('product_tags')->nullable();
            $table->text('product_colors')->nullable();
            $table->text('product_short_description')->nullable();
            $table->text('product_long_description')->nullable();
            $table->string('product_slug');
            $table->decimal('product_price', 10, 2);
            $table->string('product_thumbbail')->nullable();
            $table->enum('product_status', ['active', 'inactive']);
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('vendor_id');
            $table->integer('product_quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
