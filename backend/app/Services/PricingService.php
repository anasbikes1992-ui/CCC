<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PricingNotConfiguredException;
use App\Models\PackageSize;
use App\Models\PricingMatrix;
use App\Models\Route;
use Illuminate\Validation\ValidationException;

/**
 * Per-package pricing — NOT per-km.
 * Algorithm + worked examples: docs/PRICING_RULES.md.
 *
 * Final price = base + surcharges + cod_fee − discount.
 */
class PricingService
{
    /**
     * @return array{
     *   base_lkr: float,
     *   surcharges_lkr: float,
     *   cod_fee_lkr: float,
     *   discount_lkr: float,
     *   total_lkr: float
     * }
     */
    public function quote(
        string $routeCode,
        string $sizeCode,
        string $pickupType = 'hub',
        string $dropType = 'hub',
        bool $isExpress = false,
        bool $hasInsurance = false,
        ?float $declaredValueLkr = null,
        ?float $codAmountLkr = null,
    ): array {
        $route = Route::where('code', $routeCode)->firstOrFail();
        $size = PackageSize::where('code', $sizeCode)->firstOrFail();

        $row = PricingMatrix::query()
            ->where('route_id', $route->id)
            ->where('package_size_id', $size->id)
            ->whereNull('effective_until')
            ->first();

        if (! $row) {
            throw new PricingNotConfiguredException($routeCode, $sizeCode);
        }

        $base = (float) $row->base_price_lkr;
        $sc = $row->surcharges ?? [];

        $surcharges = 0.0;

        if ($pickupType === 'doorstep') {
            $surcharges += (float) ($sc['doorstep_pickup_lkr'] ?? 0);
        }

        if ($dropType === 'doorstep') {
            $surcharges += (float) ($sc['doorstep_drop_lkr'] ?? 0);
        }

        if ($isExpress) {
            $surcharges += (float) ($sc['express_lkr'] ?? 0);
        }

        if ($hasInsurance) {
            if ($declaredValueLkr === null || $declaredValueLkr <= 0) {
                throw ValidationException::withMessages([
                    'declared_value_lkr' => ['Declared value is required when insurance is selected.'],
                ]);
            }
            $insurance = $this->round2($declaredValueLkr * ((float) ($sc['insurance_pct'] ?? 0)) / 100);
            $surcharges += $insurance;
        }

        $codFee = 0.0;
        if ($codAmountLkr !== null && $codAmountLkr > 0) {
            $pct = $codAmountLkr * ((float) ($sc['cod_pct'] ?? 0)) / 100;
            $min = (float) ($sc['cod_min_lkr'] ?? 0);
            $codFee = $this->round2(max($pct, $min));
        }

        $discount = 0.0; // v1.1

        $total = $this->round2($base + $surcharges + $codFee - $discount);

        return [
            'base_lkr' => $this->round2($base),
            'surcharges_lkr' => $this->round2($surcharges),
            'cod_fee_lkr' => $this->round2($codFee),
            'discount_lkr' => $this->round2($discount),
            'total_lkr' => $total,
        ];
    }

    private function round2(float $v): float
    {
        return round($v, 2, PHP_ROUND_HALF_UP);
    }
}
