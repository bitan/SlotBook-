<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * time_off covers both individual staff time off and business-wide closures.
     * - staff_id NULL  → applies to the whole business (closed day / holiday)
     * - staff_id set   → applies only to that staff member
     * - starts_at / ends_at are stored in UTC
     */
    public function up(): void
    {
        Schema::create('time_off', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at'); // UTC
            $table->dateTime('ends_at');   // UTC
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_off');
    }
};
