<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->string('sync_es')->nullable()->default('no')->after('quantity');
        });

        DB::unprepared('
            CREATE TRIGGER trigger_sync_es_product_variation_update
            BEFORE UPDATE ON product_variations
            FOR EACH ROW
            BEGIN
                IF (NEW.sync_es = \'yes\' AND OLD.sync_es = \'yes\') THEN
                    SET NEW.sync_es = \'no\';
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            //
        });
    }
};
