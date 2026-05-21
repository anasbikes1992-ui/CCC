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
        'phone' => '+94770007770',
        'password_hash' => Hash::make('password'),
        'role' => 'customer',
    ]);

    $this->parcel = app(\App\Services\BookingService::class)->book($this->customer, [
        'route_code' => 'CMB-KDY',
        'package_size_code' => 'S',
        'weight_kg' => 2.0,
        'pickup_type' => 'hub',
        'pickup_hub_code' => 'CMB',
        'drop_type' => 'hub',
        'drop_hub_code' => 'KDY',
        'receiver_name' => 'Receiver',
        'receiver_phone' => '+94712223344',
        'payment_method' => 'cod',
    ]);
});

it('returns parcel label pdf for the owning customer', function () {
    $token = $this->customer->createToken('cust')->plainTextToken;

    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->get("/api/v1/customer/parcels/{$this->parcel->id}/label.pdf");

    $r->assertOk();
    expect($r->headers->get('content-type'))->toContain('application/pdf');
    expect($r->headers->get('content-disposition'))->toContain('label-'.$this->parcel->parcel_number.'.pdf');
});
