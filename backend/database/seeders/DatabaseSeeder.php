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
            UserSeeder::class,        // Test users for E2E testing
            PricingSeeder::class,     // Pricing matrix for routes
            TestDataSeeder::class,    // Sample parcels for testing
            LorrySeeder::class,
            PricingMatrixSeeder::class,
            DemoUserSeeder::class,
            TripSeeder::class,
        ]);
    }
}
