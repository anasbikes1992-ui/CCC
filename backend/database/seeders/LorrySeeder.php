<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Lorry;
use Illuminate\Database\Seeder;

class LorrySeeder extends Seeder
{
    public function run(): void
    {
        $lorries = [
            ['registration_number' => 'LX-1001', 'type' => 'small',  'max_weight_kg' => 1000, 'max_capacity_units' => 100],
            ['registration_number' => 'LX-1002', 'type' => 'medium', 'max_weight_kg' => 2000, 'max_capacity_units' => 200],
            ['registration_number' => 'LX-1003', 'type' => 'large',  'max_weight_kg' => 5000, 'max_capacity_units' => 300],
        ];

        foreach ($lorries as $l) {
            Lorry::firstOrCreate(
                ['registration_number' => $l['registration_number']],
                [
                    'type' => $l['type'],
                    'max_weight_kg' => $l['max_weight_kg'],
                    'max_capacity_units' => $l['max_capacity_units'],
                    'is_active' => true,
                ]
            );
        }
    }
}
