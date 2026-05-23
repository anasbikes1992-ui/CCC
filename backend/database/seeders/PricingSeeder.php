<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PricingMatrix;
use App\Models\Route;
use App\Models\PackageSize;

class PricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all routes and package sizes
        $routes = Route::all()->keyBy('code');
        $sizes = PackageSize::all()->keyBy('code');

        // Base pricing matrix per route
        $pricingData = [
            'CMB-KDY' => ['S' => 350, 'M' => 700, 'L' => 1500, 'XL' => 3000, 'BALE' => 5000],
            'KDY-CMB' => ['S' => 350, 'M' => 700, 'L' => 1500, 'XL' => 3000, 'BALE' => 5000],
            'CMB-GAL' => ['S' => 300, 'M' => 600, 'L' => 1200, 'XL' => 2500, 'BALE' => 4500],
            'GAL-CMB' => ['S' => 300, 'M' => 600, 'L' => 1200, 'XL' => 2500, 'BALE' => 4500],
            'CMB-JAF' => ['S' => 800, 'M' => 1600, 'L' => 3200, 'XL' => 6400, 'BALE' => 10000],
            'JAF-CMB' => ['S' => 800, 'M' => 1600, 'L' => 3200, 'XL' => 6400, 'BALE' => 10000],
        ];

        $count = 0;
        foreach ($pricingData as $routeCode => $prices) {
            if (!isset($routes[$routeCode])) {
                $this->command->warn("Route $routeCode not found, skipping");
                continue;
            }

            $route = $routes[$routeCode];

            foreach ($prices as $sizeCode => $basePrice) {
                if (!isset($sizes[$sizeCode])) {
                    $this->command->warn("Package size $sizeCode not found, skipping");
                    continue;
                }

                $size = $sizes[$sizeCode];

                PricingMatrix::firstOrCreate(
                    [
                        'route_id' => $route->id,
                        'package_size_id' => $size->id,
                        'effective_from' => now()->startOfDay(),
                    ],
                    [
                        'base_price_lkr' => $basePrice,
                        'effective_until' => null,
                        'surcharges' => [],
                    ]
                );
                $count++;
            }
        }

        $this->command->info("✓ Created $count pricing entries successfully");
    }
}
