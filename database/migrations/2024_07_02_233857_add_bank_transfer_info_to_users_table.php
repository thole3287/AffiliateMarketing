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
            $table->string('bank_account_name')->nullable()->after('membership_level');
            $table->string('bank_account_number')->nullable()->after('membership_level');
            $table->string('bank_name')->nullable()->after('membership_level'); // Tên ngân hàng
            $table->string('bank_branch')->nullable()->after('membership_level'); // Chi nhánh ngân hàng
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
