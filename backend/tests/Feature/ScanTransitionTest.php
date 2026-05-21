<?php

declare(strict_types=1);

use App\Enums\ParcelEventType;
use App\Models\Driver;
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
    $this->driverProfile = Driver::create([
        'user_id' => $this->driver->id,
        'license_number' => 'DL-SCAN-100',
        'license_expires_at' => now()->addYear(),
        'is_active' => true,
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

    $this->parcel->trip()->update(['driver_id' => $this->driverProfile->id]);
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

it('returns 422 for invalid scan mode header value', function () {
    $token = $this->driver->createToken('drv')->plainTextToken;

    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('X-Scan-Mode', 'invalid')
        ->postJson("/api/v1/driver/parcels/{$this->parcel->id}/scan", [
            'qr_token' => $this->parcel->qr_token,
            'event_type' => ParcelEventType::PICKED_UP->value,
        ]);

    $r->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('returns 422 when qr scan mode omits qr_token', function () {
    $token = $this->driver->createToken('drv')->plainTextToken;

    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/driver/parcels/{$this->parcel->id}/scan", [
            'event_type' => ParcelEventType::PICKED_UP->value,
        ]);

    $r->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('returns 403 when scanning parcel not assigned to driver', function () {
    $otherDriverUser = User::create([
        'full_name' => 'Other D',
        'phone' => '+94770004444',
        'password_hash' => Hash::make('p'),
        'role' => 'driver',
    ]);

    Driver::create([
        'user_id' => $otherDriverUser->id,
        'license_number' => 'DL-SCAN-200',
        'license_expires_at' => now()->addYear(),
        'is_active' => true,
    ]);

    $token = $otherDriverUser->createToken('drv2')->plainTextToken;
    $r = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/driver/parcels/{$this->parcel->id}/scan", [
            'qr_token' => $this->parcel->qr_token,
            'event_type' => ParcelEventType::PICKED_UP->value,
        ]);

    $r->assertStatus(403)
        ->assertJsonPath('error.code', 'FORBIDDEN');
});
