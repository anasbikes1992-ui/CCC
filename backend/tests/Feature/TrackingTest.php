<?php

declare(strict_types=1);

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
    $this->seed([HubSeeder::class, RouteSeeder::class, PackageSizeSeeder::class, LorrySeeder::class, PricingMatrixSeeder::class, TripSeeder::class]);
});

it('returns 404 for a parcel number that does not exist', function () {
    $r = $this->getJson('/api/v1/public/parcels/CCC-20990101-000001-7/track');
    $r->assertStatus(404)->assertJsonPath('error.code', 'NOT_FOUND');
});

it('returns 404 for a parcel number with bad check digit', function () {
    $r = $this->getJson('/api/v1/public/parcels/CCC-20260601-000042-9/track');
    $r->assertStatus(404);
});

it('serves a tracking response with a Cache-Control header', function () {
    $customer = User::create(['full_name' => 'C', 'phone' => '+94770004444', 'password_hash' => Hash::make('p'), 'role' => 'customer']);
    $parcel = app(BookingService::class)->book($customer, [
        'route_code' => 'CMB-KDY', 'package_size_code' => 'S', 'weight_kg' => 1,
        'pickup_type' => 'hub', 'pickup_hub_code' => 'CMB',
        'drop_type' => 'hub', 'drop_hub_code' => 'KDY',
        'receiver_name' => 'R', 'receiver_phone' => '+94712223344', 'payment_method' => 'cod',
    ]);

    $r = $this->getJson("/api/v1/public/parcels/{$parcel->parcel_number}/track");
    $r->assertOk();
    $r->assertHeader('Cache-Control', 'max-age=30, public');
    expect($r->json('data.parcel_number'))->toBe($parcel->parcel_number);
});
