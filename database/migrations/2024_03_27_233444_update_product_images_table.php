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
        Schema::table('product_images', function (Blueprint $table) {
            // Drop the column 'image_prodict_id'
            $table->dropColumn('image_prodict_id');
            // Add new column 'product_id'
            $table->unsignedBigInteger('product_id')->after('id');

            // Drop the column 'product_image'
            $table->dropColumn('product_image');
            // Add new column 'image_path'
            $table->string('image_path')->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn('product_id');
            $table->dropColumn('image_path');

            // Recreate the old columns
            $table->unsignedBigInteger('image_prodict_id');
            $table->string('product_image');
        });
    }
};
