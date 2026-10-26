<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('display_name');
            $table->string('avatar_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // Many-to-many: which services a staff member can perform
        Schema::create('service_staff', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_id')->constrained()->restrictOnDelete();
            $table->primary(['service_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_staff');
        Schema::dropIfExists('staff');
    }
};
