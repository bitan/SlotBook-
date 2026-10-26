<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SettingKey;
use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default settings
        $defaults = [
            SettingKey::BusinessName        => env('BUSINESS_NAME', 'My Business'),
            SettingKey::BusinessTimezone    => env('BUSINESS_TIMEZONE', 'Africa/Addis_Ababa'),
            SettingKey::AutoConfirm         => 'true',
            SettingKey::CancelWindowHours   => '2',
            SettingKey::MinNoticeMinutes    => '60',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key->value], ['value' => $value]);
        }

        // Seed admin account (only if none exists)
        if (! User::where('role', UserRole::Admin->value)->exists()) {
            User::create([
                'name'     => 'Admin',
                'email'    => env('ADMIN_EMAIL', 'admin@slotbook.app'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'change-me-now')),
                'role'     => UserRole::Admin->value,
            ]);
        }
    }
}
