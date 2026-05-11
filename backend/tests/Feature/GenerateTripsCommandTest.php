<?php

declare(strict_types=1);

use App\Models\Trip;
use Database\Seeders\HubSeeder;
use Database\Seeders\LorrySeeder;
use Database\Seeders\RouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([HubSeeder::class, RouteSeeder::class, LorrySeeder::class]);
});

it('generates 14 days × 2 departures × 2 routes = 56 trips by default', function () {
    $this->artisan('trips:generate')->assertExitCode(0);

    expect(Trip::count())->toBe(14 * 2 * 2);
});

it('is idempotent — re-running creates no duplicates', function () {
    $this->artisan('trips:generate')->assertExitCode(0);
    $first = Trip::count();
    $this->artisan('trips:generate')->assertExitCode(0);
    expect(Trip::count())->toBe($first);
});

it('honours the --days option', function () {
    $this->artisan('trips:generate', ['--days' => 3])->assertExitCode(0);
    expect(Trip::count())->toBe(3 * 2 * 2);
});
