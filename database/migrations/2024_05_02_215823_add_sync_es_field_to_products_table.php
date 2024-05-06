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
        Schema::table('products', function (Blueprint $table) {
            // Thêm trường sync_es vào bảng products
            $table->string('sync_es')->default('no')->nullable()->after('product_quantity');
        });

        // Thêm trigger để cập nhật sync_es thành 'no' khi các trường khác thay đổi (ngoại trừ sync_es)
        DB::unprepared('
            CREATE TRIGGER trigger_sync_es_product_update
            BEFORE UPDATE ON products
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
        Schema::table('products', function (Blueprint $table) {
            // Xóa trường sync_es khỏi bảng products
            // $table->dropColumn('sync_es');
        });
    }
};
