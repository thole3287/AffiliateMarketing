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
        Schema::table('brands', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('image');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            //
        });

        Schema::table('categories', function (Blueprint $table) {
            // $table->dropColumn('status');
        });
    }
};
