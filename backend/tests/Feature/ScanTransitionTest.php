<?php

declare(strict_types=1);

use App\Enums\ParcelEventType;
use App\Models\Parcel;
use App\Models\User;
use App\Services\BookingService;
use App\Services\ScanService;
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
    $this->customer = User::create([
        'full_name' => 'C', 'phone' => '+94770006666', 'password_hash' => Hash::make('p'), 'role' => 'customer',
    ]);
    $this->driver = User::create([
        'full_name' => 'D', 'phone' => '+94770005555', 'password_hash' => Hash::make('p'), 'role' => 'driver',
    ]);

    $this->parcel = app(BookingService::class)->book($this->customer, [
        'route_code' => 'CMB-KDY',
        'package_size_code' => 'M',
        'weight_kg' => 5.0,
        'pickup_type' => 'hub', 'pickup_hub_code' => 'CMB',
        'drop_type' => 'hub', 'drop_hub_code' => 'KDY',
        'receiver_name' => 'R', 'receiver_phone' => '+94712223344',
        'payment_method' => 'cod',
    ]);
});

it('walks a parcel through 4 sequential scans and the tracking endpoint shows them all', function () {
    $token = $this->driver->createToken('drv')->plainTextToken;
    $stages = [
        ParcelEventType::PICKED_UP->value,
        ParcelEventType::RECEIVED_AT_ORIGIN_HUB->value,
        ParcelEventType::LOADED_ON_LORRY->value,
        ParcelEventType::IN_TRANSIT->value,
    ];
    foreach ($stages as $stage) {
        $r = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/driver/parcels/{$this->parcel->id}/scan", [
                'qr_token' => $this->parcel->qr_token,
                'event_type' => $stage,
            ]);
        $r->assertOk()->assertJsonPath('data.parcel.status', $stage);
    }

    $track = $this->getJson("/api/v1/public/parcels/{$this->parcel->parcel_number}/track");
    $track->assertOk()
        ->assertJsonPath('data.current_status', 'IN_TRANSIT');

    $events = collect($track->json('data.events'))->pluck('event_type')->all();
    expect($events)->toContain('BOOKED', 'PICKED_UP', 'RECEIVED_AT_ORIGIN_HUB', 'LOADED_ON_LORRY', 'IN_TRANSIT');
});

it('rejects illegal transition BOOKED → DELIVERED with 422 ILLEGAL_STATUS_TRANSITION', function () {
    $token = $this->driver->createToken('drv')->plainTextToken;

    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/driver/parcels/{$this->parcel->id}/scan", [
            'qr_token' => $this->parcel->qr_token,
            'event_type' => 'DELIVERED',
        ]);

    $r->assertStatus(422)
        ->assertJsonPath('error.code', 'ILLEGAL_STATUS_TRANSITION')
        ->assertJsonPath('error.details.from', 'BOOKED')
        ->assertJsonPath('error.details.to', 'DELIVERED');
});

it('writes an ILLEGAL_TRANSITION_ATTEMPT audit row on illegal scans', function () {
    expect($this->parcel->events()->where('event_type', 'ILLEGAL_TRANSITION_ATTEMPT')->count())->toBe(0);

    try {
        app(ScanService::class)->record(
            $this->parcel,
            ParcelEventType::DELIVERED,
            $this->driver,
        );
    } catch (\Throwable) {
        // expected
    }

    expect($this->parcel->events()->where('event_type', 'ILLEGAL_TRANSITION_ATTEMPT')->count())->toBe(1);
});
