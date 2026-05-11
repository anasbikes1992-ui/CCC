<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Hub;
use App\Models\Route;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $cmb = Hub::where('code', 'CMB')->firstOrFail();
        $kdy = Hub::where('code', 'KDY')->firstOrFail();

        Route::firstOrCreate(
            ['code' => 'CMB-KDY'],
            [
                'origin_hub_id' => $cmb->id,
                'destination_hub_id' => $kdy->id,
                'display_name' => 'Colombo → Kandy',
                'estimated_duration_minutes' => 180,
                'is_active' => true,
            ]
        );

        Route::firstOrCreate(
            ['code' => 'KDY-CMB'],
            [
                'origin_hub_id' => $kdy->id,
                'destination_hub_id' => $cmb->id,
                'display_name' => 'Kandy → Colombo',
                'estimated_duration_minutes' => 180,
                'is_active' => true,
            ]
        );
    }
}
