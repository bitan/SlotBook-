<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * bookings table design notes:
     *
     * - starts_at / ends_at stored in UTC. ends_at = starts_at + service.duration_min.
     *   Buffer is NOT included in ends_at; it is added during overlap checks so
     *   a buffer overrun does not show as part of the booked window to the customer.
     *
     * - reference: 8-char uppercase alphanumeric shown to the customer.
     *
     * - manage_token_hash: SHA-256 of a 64-char random token. The token itself is
     *   emailed to the customer and never stored. The token is valid (reusable)
     *   until the booking reaches a terminal status or starts_at has passed.
     *
     * - Indexes for the two hot query paths:
     *     (staff_id, starts_at, ends_at) → overlap check in BookingService
     *     (status, starts_at)            → dashboard listing and reminder scheduler
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 8)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');                 // UTC
            $table->dateTime('ends_at');                   // UTC (starts_at + duration_min)
            $table->enum('status', [
                'pending', 'confirmed', 'completed', 'cancelled', 'no_show',
            ])->default('pending');
            $table->enum('source', ['online', 'manual'])->default('online');
            $table->string('manage_token_hash', 64);       // SHA-256 hex
            $table->text('note')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            // Overlap-check index
            $table->index(['staff_id', 'starts_at', 'ends_at'], 'bookings_overlap_idx');
            // Dashboard + reminder scheduler index
            $table->index(['status', 'starts_at'], 'bookings_status_starts_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
