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
        Schema::table('brands', function (Blueprint $table) {
            $table->string('sync_es')->nullable()->default('no')->after('image');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->string('sync_es')->nullable()->default('no')->after('image');
        });

        DB::unprepared('
            CREATE TRIGGER trigger_sync_es_brands_update
            BEFORE UPDATE ON brands
            FOR EACH ROW
            BEGIN
                IF (NEW.sync_es = \'yes\' AND OLD.sync_es = \'yes\') THEN
                    SET NEW.sync_es = \'no\';
                END IF;
            END
        ');
        DB::unprepared('
            CREATE TRIGGER trigger_sync_es_categories_update
            BEFORE UPDATE ON categories
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
        Schema::table('brands', function (Blueprint $table) {
            //
        });
        Schema::table('categories', function (Blueprint $table) {
            //
        });
    }
};
