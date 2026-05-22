<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SUPER_ADMIN_EMAIL');
        $phone = (string) env('SUPER_ADMIN_PHONE');
        $name = (string) env('SUPER_ADMIN_NAME', 'Super Admin');
        $password = (string) env('SUPER_ADMIN_PASSWORD');

        if ($email === '' || $phone === '' || $password === '') {
            throw new RuntimeException('SUPER_ADMIN_EMAIL, SUPER_ADMIN_PHONE, and SUPER_ADMIN_PASSWORD must be set.');
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'phone' => $phone,
                'full_name' => $name,
                'password_hash' => Hash::make($password),
                'role' => 'admin_super',
                'preferred_lang' => 'en',
            ]
        );

        $user->syncRoles(['admin_super']);
    }
}
