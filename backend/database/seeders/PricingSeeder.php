<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pricing;
use App\Models\Route;
use App\Enums\PackageSize;
use Illuminate\Support\Str;

class PricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = Route::all();

        // Base pricing matrix per route
        $pricingMatrix = [
            'CMB-KDY' => [
                PackageSize::SMALL->value => 350,
                PackageSize::MEDIUM->value => 700,
                PackageSize::LARGE->value => 1500,
                PackageSize::EXTRA_LARGE->value => 3000,
                PackageSize::BALE->value => 5000,
            ],
            'KDY-CMB' => [
                PackageSize::SMALL->value => 350,
                PackageSize::MEDIUM->value => 700,
                PackageSize::LARGE->value => 1500,
                PackageSize::EXTRA_LARGE->value => 3000,
                PackageSize::BALE->value => 5000,
            ],
            'CMB-GAL' => [
                PackageSize::SMALL->value => 300,
                PackageSize::MEDIUM->value => 600,
                PackageSize::LARGE->value => 1200,
                PackageSize::EXTRA_LARGE->value => 2500,
                PackageSize::BALE->value => 4500,
            ],
            'GAL-CMB' => [
                PackageSize::SMALL->value => 300,
                PackageSize::MEDIUM->value => 600,
                PackageSize::LARGE->value => 1200,
                PackageSize::EXTRA_LARGE->value => 2500,
                PackageSize::BALE->value => 4500,
            ],
            'CMB-JAF' => [
                PackageSize::SMALL->value => 800,
                PackageSize::MEDIUM->value => 1600,
                PackageSize::LARGE->value => 3200,
                PackageSize::EXTRA_LARGE->value => 6400,
                PackageSize::BALE->value => 10000,
            ],
        ];

        $count = 0;
        foreach ($routes as $route) {
            if (!isset($pricingMatrix[$route->code])) {
                continue;
            }

            foreach ($pricingMatrix[$route->code] as $size => $price) {
                Pricing::create([
                    'id' => Str::uuid(),
                    'route_id' => $route->id,
                    'size' => $size,
                    'base_price' => $price,
                    'doorstep_pickup_surcharge' => $this->getDoorstepPickupSurcharge($size),
                    'doorstep_drop_surcharge' => $this->getDoorstepDropSurcharge($size),
                    'express_surcharge' => $this->getExpressSurcharge($size),
                    'insurance_rate_percentage' => 1.5,
                    'cod_fee_percentage' => 3.0,
                    'cod_min_fee' => 100,
                    'effective_from' => now(),
                ]);
                $count++;
            }
        }

        $this->command->info('✓ Created ' . $count . ' pricing entries');
    }

    private function getDoorstepPickupSurcharge(string $size): int
    {
        return match ($size) {
            PackageSize::SMALL->value => 200,
            PackageSize::MEDIUM->value => 350,
            PackageSize::LARGE->value => 600,
            PackageSize::EXTRA_LARGE->value => 1000,
            PackageSize::BALE->value => 1500,
        };
    }

    private function getDoorstepDropSurcharge(string $size): int
    {
        return match ($size) {
            PackageSize::SMALL->value => 200,
            PackageSize::MEDIUM->value => 350,
            PackageSize::LARGE->value => 600,
            PackageSize::EXTRA_LARGE->value => 1000,
            PackageSize::BALE->value => 1500,
        };
    }

    private function getExpressSurcharge(string $size): int
    {
        return match ($size) {
            PackageSize::SMALL->value => 300,
            PackageSize::MEDIUM->value => 500,
            PackageSize::LARGE->value => 800,
            PackageSize::EXTRA_LARGE->value => 1500,
            PackageSize::BALE->value => 2500,
        };
    }
}
