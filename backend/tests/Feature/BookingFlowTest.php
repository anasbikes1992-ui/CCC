<?php

declare(strict_types=1);

use App\Models\User;
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
        'phone' => '+94770007777',
        'password_hash' => Hash::make('password'),
        'role' => 'customer',
    ]);
});

it('books a parcel, assigns it to a trip, computes the right price, and returns a tracking URL', function () {
    $token = $this->customer->createToken('test')->plainTextToken;

    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/customer/parcels', [
            'route_code' => 'CMB-KDY',
            'package_size_code' => 'M',
            'weight_kg' => 12.5,
            'pickup_type' => 'doorstep',
            'pickup_address' => '23/4 Galle Rd',
            'drop_type' => 'hub',
            'drop_hub_code' => 'KDY',
            'receiver_name' => 'Receiver',
            'receiver_phone' => '+94712223344',
            'payment_method' => 'cod',
        ]);

    $r->assertCreated();
    $r->assertJsonPath('success', true);
    $r->assertJsonPath('data.parcel.price.total_lkr', 1050);
    expect($r->json('data.parcel.parcel_number'))->toMatch('/^CCC-\d{8}-\d{6}-\d$/');
    expect($r->json('data.parcel.trip.trip_code'))->toBeString();
});

it('returns TRIP_FULL when no trip can fit the parcel', function () {
    // Drain capacity by booking until full. Easier: max out the lorry size used by trips.
    \App\Models\Trip::query()->update(['capacity_units_used' => \Illuminate\Support\Facades\DB::raw('capacity_units_max')]);

    $token = $this->customer->createToken('test')->plainTextToken;
    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/customer/parcels', [
            'route_code' => 'CMB-KDY',
            'package_size_code' => 'S',
            'weight_kg' => 1.0,
            'pickup_type' => 'hub',
            'pickup_hub_code' => 'CMB',
            'drop_type' => 'hub',
            'drop_hub_code' => 'KDY',
            'receiver_name' => 'R',
            'receiver_phone' => '+94712223344',
            'payment_method' => 'cod',
        ]);

    $r->assertStatus(409)->assertJsonPath('error.code', 'TRIP_FULL');
});
