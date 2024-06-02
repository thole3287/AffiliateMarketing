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
        Schema::create('withdrawal_tickets', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Liên kết tới bảng users
            $table->decimal('amount', 15, 2); // Số tiền muốn rút
            $table->string('bank_name'); // Tên ngân hàng
            $table->string('account_number'); // Số tài khoản
            $table->text('note')->nullable(); // Ghi chú
            $table->string('status')->default('pending'); // Trạng thái ticket
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_tickets');
    }
};
