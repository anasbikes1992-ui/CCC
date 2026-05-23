<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin Users
        User::create([
            'id' => Str::uuid(),
            'name' => 'Super Admin',
            'email' => 'admin@ccc.lk',
            'phone' => '+94771234567',
            'password' => Hash::make('password'),
            'role' => UserRole::SUPER_ADMIN->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        User::create([
            'id' => Str::uuid(),
            'name' => 'Operations Admin',
            'email' => 'ops@ccc.lk',
            'phone' => '+94771234568',
            'password' => Hash::make('password'),
            'role' => UserRole::OPS_ADMIN->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Test Sender (Customer)
        User::create([
            'id' => Str::uuid(),
            'name' => 'John Sender',
            'email' => 'sender@test.com',
            'phone' => '+94771111111',
            'password' => Hash::make('password'),
            'role' => UserRole::CUSTOMER->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        User::create([
            'id' => Str::uuid(),
            'name' => 'Jane Sender',
            'email' => 'sender2@test.com',
            'phone' => '+94772222222',
            'password' => Hash::make('password'),
            'role' => UserRole::CUSTOMER->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Test Drivers
        $driver1 = User::create([
            'id' => Str::uuid(),
            'name' => 'Driver Kumar',
            'email' => 'driver@test.com',
            'phone' => '+94773333333',
            'password' => Hash::make('password'),
            'role' => UserRole::DRIVER->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $driver2 = User::create([
            'id' => Str::uuid(),
            'name' => 'Driver Silva',
            'email' => 'driver2@test.com',
            'phone' => '+94774444444',
            'password' => Hash::make('password'),
            'role' => UserRole::DRIVER->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        // Hub Staff
        User::create([
            'id' => Str::uuid(),
            'name' => 'Hub Staff Colombo',
            'email' => 'hub.colombo@ccc.lk',
            'phone' => '+94775555555',
            'password' => Hash::make('password'),
            'role' => UserRole::HUB_STAFF->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        User::create([
            'id' => Str::uuid(),
            'name' => 'Hub Staff Kandy',
            'email' => 'hub.kandy@ccc.lk',
            'phone' => '+94776666666',
            'password' => Hash::make('password'),
            'role' => UserRole::HUB_STAFF->value,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $this->command->info('✓ Created 8 users (2 admins, 2 senders, 2 drivers, 2 hub staff)');
    }
}
