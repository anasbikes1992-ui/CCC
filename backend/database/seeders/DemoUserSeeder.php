<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['phone' => '+94770000001'],
            [
                'full_name' => 'Demo Admin',
                'email' => 'admin@cargo.lk',
                'password_hash' => Hash::make('password123'),
                'role' => 'admin_super',
                'preferred_lang' => 'en',
            ]
        );
        $admin->syncRoles(['admin_super']);

        $driverUser = User::firstOrCreate(
            ['phone' => '+94770000002'],
            [
                'full_name' => 'Demo Driver',
                'email' => 'driver@cargo.lk',
                'password_hash' => Hash::make('password123'),
                'role' => 'driver',
                'preferred_lang' => 'en',
            ]
        );
        $driverUser->syncRoles(['driver']);

        Driver::firstOrCreate(
            ['user_id' => $driverUser->id],
            [
                'license_number' => 'DL-DEMO-0001',
                'license_expires_at' => now()->addYears(2),
                'is_active' => true,
            ]
        );

        $customer = User::firstOrCreate(
            ['phone' => '+94770000003'],
            [
                'full_name' => 'Demo Customer',
                'email' => 'customer@cargo.lk',
                'password_hash' => Hash::make('password123'),
                'role' => 'customer',
                'preferred_lang' => 'en',
            ]
        );
        $customer->syncRoles(['customer']);
    }
}
