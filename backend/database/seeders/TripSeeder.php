<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        // Reuse the artisan command — single source of trip-generation logic.
        Artisan::call('trips:generate', ['--days' => 14]);
    }
}
