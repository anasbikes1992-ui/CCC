<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PackageSize;
use App\Models\PricingMatrix;
use App\Models\Route;
use Illuminate\Database\Seeder;

class PricingMatrixSeeder extends Seeder
{
    public function run(): void
    {
        // Per CLAUDE.md §3 / docs/PRICING_RULES.md.
        // CMB↔KDY uses identical numbers in both directions.
        $matrix = [
            'S'    => ['base' => 350,  'pickup' => 200,  'drop' => 200,  'express' => 300],
            'M'    => ['base' => 700,  'pickup' => 350,  'drop' => 350,  'express' => 500],
            'L'    => ['base' => 1500, 'pickup' => 600,  'drop' => 600,  'express' => 800],
            'XL'   => ['base' => 3000, 'pickup' => 1000, 'drop' => 1000, 'express' => 1500],
            'BALE' => ['base' => 5000, 'pickup' => 1500, 'drop' => 1500, 'express' => 2000],
        ];

        $insurancePct = 1.5;
        $codPct = 3.0;
        $codMin = 100;

        $routes = Route::whereIn('code', ['CMB-KDY', 'KDY-CMB'])->get();
        $sizes = PackageSize::whereIn('code', array_keys($matrix))->get()->keyBy('code');

        foreach ($routes as $route) {
            foreach ($matrix as $code => $row) {
                $size = $sizes[$code];

                PricingMatrix::firstOrCreate(
                    [
                        'route_id' => $route->id,
                        'package_size_id' => $size->id,
                        'effective_from' => now()->toDateString(),
                    ],
                    [
                        'base_price_lkr' => $row['base'],
                        'surcharges' => [
                            'doorstep_pickup_lkr' => $row['pickup'],
                            'doorstep_drop_lkr' => $row['drop'],
                            'express_lkr' => $row['express'],
                            'insurance_pct' => $insurancePct,
                            'cod_pct' => $codPct,
                            'cod_min_lkr' => $codMin,
                        ],
                        'effective_until' => null,
                    ]
                );
            }
        }
    }
}
