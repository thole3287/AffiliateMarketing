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
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('withdrawal_tickets')->onDelete('cascade'); // Liên kết tới bảng withdrawal_tickets
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Liên kết tới bảng users
            $table->text('message'); // Nội dung trả lời
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
    }
};
