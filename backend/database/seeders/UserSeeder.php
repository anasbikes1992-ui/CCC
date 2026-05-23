<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Driver;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin Users
        $admin = User::firstOrCreate(
            ['phone' => '+94771234567'],
            [
                'full_name' => 'Super Admin',
                'email' => 'admin@ccc.lk',
                'password_hash' => Hash::make('password'),
                'role' => 'admin_super',
                'preferred_lang' => 'en',
            ]
        );
        $admin->syncRoles(['admin_super']);

        $finance = User::firstOrCreate(
            ['phone' => '+94771234568'],
            [
                'full_name' => 'Finance Admin',
                'email' => 'finance@ccc.lk',
                'password_hash' => Hash::make('password'),
                'role' => 'finance_admin',
                'preferred_lang' => 'en',
            ]
        );
        $finance->syncRoles(['finance_admin']);

        // Customer Users (Senders)
        User::firstOrCreate(
            ['phone' => '+94777777001'],
            [
                'full_name' => 'Test Sender',
                'email' => 'sender@test.com',
                'password_hash' => Hash::make('password'),
                'role' => 'customer',
                'preferred_lang' => 'en',
            ]
        );

        User::firstOrCreate(
            ['phone' => '+94777777002'],
            [
                'full_name' => 'Test Sender 2',
                'email' => 'sender2@test.com',
                'password_hash' => Hash::make('password'),
                'role' => 'customer',
                'preferred_lang' => 'en',
            ]
        );

        // Driver Users
        $driver1User = User::firstOrCreate(
            ['phone' => '+94777777003'],
            [
                'full_name' => 'Test Driver',
                'email' => 'driver@test.com',
                'password_hash' => Hash::make('password'),
                'role' => 'driver',
                'preferred_lang' => 'en',
            ]
        );
        $driver1User->syncRoles(['driver']);

        Driver::firstOrCreate(
            ['user_id' => $driver1User->id],
            [
                'license_number' => 'DL-TEST-0001',
                'license_expires_at' => now()->addYears(2),
                'is_active' => true,
            ]
        );

        $driver2User = User::firstOrCreate(
            ['phone' => '+94777777004'],
            [
                'full_name' => 'Test Driver 2',
                'email' => 'driver2@test.com',
                'password_hash' => Hash::make('password'),
                'role' => 'driver',
                'preferred_lang' => 'en',
            ]
        );
        $driver2User->syncRoles(['driver']);

        Driver::firstOrCreate(
            ['user_id' => $driver2User->id],
            [
                'license_number' => 'DL-TEST-0002',
                'license_expires_at' => now()->addYears(2),
                'is_active' => true,
            ]
        );

        // Hub Staff Users
        $hubColombo = User::firstOrCreate(
            ['phone' => '+94777777005'],
            [
                'full_name' => 'Colombo Hub Staff',
                'email' => 'hub.colombo@ccc.lk',
                'password_hash' => Hash::make('password'),
                'role' => 'hub_staff',
                'preferred_lang' => 'en',
            ]
        );
        $hubColombo->syncRoles(['hub_staff']);

        $hubKandy = User::firstOrCreate(
            ['phone' => '+94777777006'],
            [
                'full_name' => 'Kandy Hub Staff',
                'email' => 'hub.kandy@ccc.lk',
                'password_hash' => Hash::make('password'),
                'role' => 'hub_staff',
                'preferred_lang' => 'en',
            ]
        );
        $hubKandy->syncRoles(['hub_staff']);

        $this->command->info('✓ Created 8 test users successfully');
    }
}
