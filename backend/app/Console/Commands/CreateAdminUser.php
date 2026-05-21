<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--email=anasbikes1992@gmail.com : Admin email}
                            {--password=123456 : Admin password}
                            {--name=Super Admin : Admin full name}
                            {--phone=+94000000000 : Admin phone}';

    protected $description = 'Create the super admin user';

    public function handle(): int
    {
        $email    = $this->option('email');
        $password = $this->option('password');
        $name     = $this->option('name');
        $phone    = $this->option('phone');

        $existing = User::where('email', $email)->first();

        if ($existing) {
            $this->info("Admin user already exists: {$email}");
            return self::SUCCESS;
        }

        $user = User::create([
            'full_name'     => $name,
            'phone'         => $phone,
            'email'         => $email,
            'password_hash' => Hash::make($password),
            'role'          => 'admin_super',
            'preferred_lang' => 'en',
        ]);

        // Assign Spatie permission role
        if (method_exists($user, 'assignRole')) {
            try {
                $user->assignRole('admin_super');
            } catch (\Throwable $e) {
                $this->warn('Could not assign Spatie role: ' . $e->getMessage());
            }
        }

        $this->info("✅ Admin user created successfully!");
        $this->table(
            ['Field', 'Value'],
            [
                ['Email',    $email],
                ['Password', $password],
                ['Name',     $name],
                ['Role',     'admin_super'],
            ]
        );

        return self::SUCCESS;
    }
}
