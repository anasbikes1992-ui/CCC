<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PackageSize;
use Illuminate\Database\Seeder;

class PackageSizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['code' => 'S',    'display_name' => 'Small',       'max_weight_kg' => 5,   'l' => 30,  'w' => 25,  'h' => 15,  'cu' => 1,  'sort' => 1],
            ['code' => 'M',    'display_name' => 'Medium',      'max_weight_kg' => 25,  'l' => 60,  'w' => 45,  'h' => 40,  'cu' => 4,  'sort' => 2],
            ['code' => 'L',    'display_name' => 'Large',       'max_weight_kg' => 75,  'l' => 120, 'w' => 80,  'h' => 80,  'cu' => 10, 'sort' => 3],
            ['code' => 'XL',   'display_name' => 'Extra Large', 'max_weight_kg' => 200, 'l' => 200, 'w' => 120, 'h' => 120, 'cu' => 30, 'sort' => 4],
            ['code' => 'BALE', 'display_name' => 'Bale/Pallet', 'max_weight_kg' => 500, 'l' => 120, 'w' => 100, 'h' => 150, 'cu' => 50, 'sort' => 5],
        ];

        foreach ($sizes as $s) {
            PackageSize::firstOrCreate(
                ['code' => $s['code']],
                [
                    'display_name' => $s['display_name'],
                    'max_weight_kg' => $s['max_weight_kg'],
                    'max_length_cm' => $s['l'],
                    'max_width_cm' => $s['w'],
                    'max_height_cm' => $s['h'],
                    'capacity_units' => $s['cu'],
                    'sort_order' => $s['sort'],
                    'is_active' => true,
                ]
            );
        }
    }
}
