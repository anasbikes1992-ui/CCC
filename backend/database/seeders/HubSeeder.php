<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Hub;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HubSeeder extends Seeder
{
    public function run(): void
    {
        $hubs = [
            ['code' => 'CMB', 'name' => 'Colombo Hub', 'address' => '1 Hub Lane, Colombo 14', 'city' => 'Colombo', 'district' => 'Colombo', 'lat' => 6.9271, 'lng' => 79.8612],
            ['code' => 'KDY', 'name' => 'Kandy Hub',   'address' => '1 Hub Rd, Kandy',         'city' => 'Kandy',   'district' => 'Kandy',   'lat' => 7.2906, 'lng' => 80.6337],
        ];

        foreach ($hubs as $h) {
            Hub::firstOrCreate(
                ['code' => $h['code']],
                [
                    'name' => $h['name'],
                    'address' => $h['address'],
                    'city' => $h['city'],
                    'district' => $h['district'],
                    'hub_lat' => $h['lat'],
                    'hub_lng' => $h['lng'],
                    'is_active' => true,
                ]
            );
        }
    }
}
