<?php

declare(strict_types=1);

use App\Exceptions\PricingNotConfiguredException;
use App\Services\PricingService;
use Database\Seeders\HubSeeder;
use Database\Seeders\PackageSizeSeeder;
use Database\Seeders\PricingMatrixSeeder;
use Database\Seeders\RouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([HubSeeder::class, RouteSeeder::class, PackageSizeSeeder::class, PricingMatrixSeeder::class]);
});

/**
 * Worked examples from docs/PRICING_RULES.md §5.
 */

it('Example A — plain hub-to-hub Small = 350', function () {
    $q = app(PricingService::class)->quote('CMB-KDY', 'S', 'hub', 'hub');
    expect($q['total_lkr'])->toBe(350.0);
});

it('Example B — doorstep both ends Medium = 1400', function () {
    $q = app(PricingService::class)->quote('CMB-KDY', 'M', 'doorstep', 'doorstep');
    expect($q['total_lkr'])->toBe(1400.0);
});

it('Example C — doorstep pickup Medium = 1050 (ARCHITECTURE.md reference)', function () {
    $q = app(PricingService::class)->quote('CMB-KDY', 'M', 'doorstep', 'hub');
    expect($q['total_lkr'])->toBe(1050.0);
});

it('Example D — express Large with insurance and COD = 4625', function () {
    $q = app(PricingService::class)->quote(
        routeCode: 'CMB-KDY',
        sizeCode: 'L',
        pickupType: 'doorstep',
        dropType: 'doorstep',
        isExpress: true,
        hasInsurance: true,
        declaredValueLkr: 25000,
        codAmountLkr: 25000,
    );
    expect($q['total_lkr'])->toBe(4625.0);
});

it('Example E — COD floor of 100 LKR kicks in for small COD amounts', function () {
    $q = app(PricingService::class)->quote(
        routeCode: 'CMB-KDY',
        sizeCode: 'S',
        codAmountLkr: 1000,
    );
    expect($q['cod_fee_lkr'])->toBe(100.0);
    expect($q['total_lkr'])->toBe(450.0);
});

it('Example F — Bale all surcharges + big insurance + COD = 16000', function () {
    $q = app(PricingService::class)->quote(
        routeCode: 'CMB-KDY',
        sizeCode: 'BALE',
        pickupType: 'doorstep',
        dropType: 'doorstep',
        isExpress: true,
        hasInsurance: true,
        declaredValueLkr: 200000,
        codAmountLkr: 100000,
    );
    expect($q['total_lkr'])->toBe(16000.0);
});

it('throws when pricing matrix is missing', function () {
    \App\Models\PricingMatrix::query()->delete();
    expect(fn () => app(PricingService::class)->quote('CMB-KDY', 'S'))
        ->toThrow(PricingNotConfiguredException::class);
});

it('rejects insurance without declared value', function () {
    expect(fn () => app(PricingService::class)->quote(
        'CMB-KDY', 'S', hasInsurance: true,
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('returns 0 cod_fee when no COD requested', function () {
    $q = app(PricingService::class)->quote('CMB-KDY', 'S');
    expect($q['cod_fee_lkr'])->toBe(0.0);
});
