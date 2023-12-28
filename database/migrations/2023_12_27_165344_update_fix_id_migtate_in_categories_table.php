<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('migrations', function (Blueprint $table) {
            DB::statement('ALTER TABLE migrations MODIFY id INTEGER NOT NULL AUTO_INCREMENT');        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('migrations', function (Blueprint $table) {
            //
        });
    }
};
