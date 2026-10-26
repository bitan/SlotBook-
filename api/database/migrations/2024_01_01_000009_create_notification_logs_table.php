<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->enum('channel', ['email', 'telegram']);
            $table->enum('status', ['sent', 'failed']);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
