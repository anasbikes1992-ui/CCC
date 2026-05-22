<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
            HubSeeder::class,
            RouteSeeder::class,
            PackageSizeSeeder::class,
            LorrySeeder::class,
            PricingMatrixSeeder::class,
            DemoUserSeeder::class,
            TripSeeder::class,
        ]);
    }
}
