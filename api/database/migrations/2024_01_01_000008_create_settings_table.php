<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SettingKey;

return new class extends Migration
{
    /**
     * settings table uses an ENUM column whose values are derived from SettingKey.
     * This enforces at the database level that only known keys can be inserted,
     * preventing the "three spellings of auto_confirm" problem.
     *
     * To add a new setting: add it to SettingKey enum, then create a migration
     * that modifies this column. Never insert raw strings.
     */
    public function up(): void
    {
        $keys = array_column(SettingKey::cases(), 'value');

        Schema::create('settings', function (Blueprint $table) use ($keys) {
            $table->enum('key', $keys)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
