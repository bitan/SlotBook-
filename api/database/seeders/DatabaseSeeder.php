<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SettingKey;
use App\Enums\UserRole;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default settings — use ->value to get string key
        $defaults = [
            SettingKey::BusinessName->value      => env('BUSINESS_NAME', 'My Business'),
            SettingKey::BusinessTimezone->value  => env('BUSINESS_TIMEZONE', 'Africa/Addis_Ababa'),
            SettingKey::AutoConfirm->value       => 'true',
            SettingKey::CancelWindowHours->value => '2',
            SettingKey::MinNoticeMinutes->value  => '60',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
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

        // Seed sample services
        $services = [
            [
                'name'         => 'Haircut',
                'description'  => 'Classic haircut and styling',
                'duration_min' => 30,
                'buffer_min'   => 10,
                'price'        => 25.00,
                'is_active'    => true,
            ],
            [
                'name'         => 'Hair Coloring',
                'description'  => 'Full hair color treatment',
                'duration_min' => 90,
                'buffer_min'   => 15,
                'price'        => 80.00,
                'is_active'    => true,
            ],
            [
                'name'         => 'Beard Trim',
                'description'  => 'Beard shaping and trimming',
                'duration_min' => 20,
                'buffer_min'   => 5,
                'price'        => 15.00,
                'is_active'    => true,
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['name' => $service['name']], $service);
        }
    }
}
