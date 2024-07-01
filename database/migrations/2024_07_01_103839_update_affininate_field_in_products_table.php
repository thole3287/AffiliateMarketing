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
        Schema::table('products', function (Blueprint $table) {
            $table->float('special_commission_percentage', 8, 2)->default(0.00)->after('commission_percentage');
            $table->enum('product_commission_status', ['special', 'active', 'inactive'])->default('active')->comment('special: special_commission_percentage, active: commission_percentage, inactive: turn off commission product ')->after('commission_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
