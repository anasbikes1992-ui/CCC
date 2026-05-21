<?php

declare(strict_types=1);

use App\Models\Trip;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\HubSeeder;
use Database\Seeders\LorrySeeder;
use Database\Seeders\PackageSizeSeeder;
use Database\Seeders\PricingMatrixSeeder;
use Database\Seeders\RouteSeeder;
use Database\Seeders\TripSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        HubSeeder::class,
        RouteSeeder::class,
        PackageSizeSeeder::class,
        LorrySeeder::class,
        PricingMatrixSeeder::class,
        TripSeeder::class,
    ]);

    $this->customer = User::create([
        'full_name' => 'Booker',
        'phone' => '+94770007888',
        'password_hash' => Hash::make('password'),
        'role' => 'customer',
    ]);

    $this->driverUser = User::create([
        'full_name' => 'Driver User',
        'phone' => '+94770008888',
        'password_hash' => Hash::make('password'),
        'role' => 'driver',
    ]);

    $this->driver = \App\Models\Driver::create([
        'user_id' => $this->driverUser->id,
        'license_number' => 'DL-TEST-999',
        'license_expires_at' => now()->addYear(),
        'is_active' => true,
    ]);

    Trip::query()->update(['driver_id' => $this->driver->id]);

    $this->parcel = app(BookingService::class)->book($this->customer, [
        'route_code' => 'CMB-KDY',
        'package_size_code' => 'M',
        'weight_kg' => 8.0,
        'pickup_type' => 'hub',
        'pickup_hub_code' => 'CMB',
        'drop_type' => 'hub',
        'drop_hub_code' => 'KDY',
        'receiver_name' => 'Receiver',
        'receiver_phone' => '+94712223344',
        'payment_method' => 'cod',
    ]);
});

it('returns driver trips for today', function () {
    $token = $this->driverUser->createToken('drv')->plainTextToken;

    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/driver/trips');

    $r->assertOk()
        ->assertJsonPath('success', true);

    $items = $r->json('data.items');
    expect($items)->toBeArray();
    expect(count($items))->toBeGreaterThan(0);
    expect($items[0])->toHaveKeys(['id', 'trip_code', 'status', 'route', 'capacity_units_remaining']);
});

it('returns parcels for a driver trip', function () {
    $token = $this->driverUser->createToken('drv')->plainTextToken;
    $tripId = $this->parcel->trip_id;

    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/driver/trips/{$tripId}/parcels");

    $r->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.trip.id', $tripId);

    $items = $r->json('data.items');
    expect($items)->toBeArray();
    expect(collect($items)->pluck('parcel_number')->contains($this->parcel->parcel_number))->toBeTrue();
});

it('returns 501 for deliver stub endpoint', function () {
    $token = $this->driverUser->createToken('drv')->plainTextToken;

    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/driver/parcels/{$this->parcel->id}/deliver", []);

    $r->assertStatus(501)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_IMPLEMENTED');
});

it('returns 422 for invalid date format on driver trips endpoint', function () {
    $token = $this->driverUser->createToken('drv')->plainTextToken;

    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/driver/trips?date=11-05-2026');

    $r->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('returns 403 when another driver attempts deliver stub on unassigned parcel', function () {
    $otherDriverUser = User::create([
        'full_name' => 'Other Driver',
        'phone' => '+94770009999',
        'password_hash' => Hash::make('password'),
        'role' => 'driver',
    ]);

    \App\Models\Driver::create([
        'user_id' => $otherDriverUser->id,
        'license_number' => 'DL-TEST-111',
        'license_expires_at' => now()->addYear(),
        'is_active' => true,
    ]);

    $token = $otherDriverUser->createToken('drv2')->plainTextToken;
    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/driver/parcels/{$this->parcel->id}/deliver", []);

    $r->assertStatus(403)
        ->assertJsonPath('error.code', 'FORBIDDEN');
});
