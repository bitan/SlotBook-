<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * working_hours stores the recurring weekly schedule for each staff member.
     *
     * Design notes:
     * - weekday: 0 = Sunday … 6 = Saturday (matches Carbon::dayOfWeek)
     * - start_time / end_time are stored in LOCAL time (Africa/Addis_Ababa).
     *   AvailabilityService converts them to UTC per calendar date.
     * - A single day can have more than one row (e.g. morning + afternoon split).
     * - end_time can equal '00:00:00' to represent midnight end-of-day.
     *   AvailabilityService treats '00:00:00' as 24:00 (end of that date) when
     *   end_time <= start_time, preventing midnight-crossing confusion.
     */
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0 Sunday - 6 Saturday
            $table->time('start_time');             // local time
            $table->time('end_time');               // local time; '00:00:00' = midnight (24:00)
            $table->timestamps();

            $table->index(['staff_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
