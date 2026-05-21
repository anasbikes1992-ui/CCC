<?php

use App\Models\Driver;
use App\Models\Hub;
use App\Models\Lorry;
use App\Models\Route;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::create([
        'full_name' => 'Admin User',
        'email' => 'admin@example.com',
        'phone' => '+94770000001',
        'password_hash' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $this->origin = Hub::create([
        'name' => 'Colombo Hub',
        'code' => 'CMB',
        'address' => 'Colombo',
        'is_active' => true,
    ]);

    $this->dest = Hub::create([
        'name' => 'Kandy Hub',
        'code' => 'KND',
        'address' => 'Kandy',
        'is_active' => true,
    ]);

    $this->route = Route::create([
        'origin_hub_id' => $this->origin->id,
        'destination_hub_id' => $this->dest->id,
        'code' => 'CMB-KND',
        'distance_km' => 115.00,
        'estimated_duration_minutes' => 180,
        'is_active' => true,
    ]);

    $this->lorry = Lorry::create([
        'registration_number' => 'LX-1234',
        'type' => 'Medium',
        'max_weight_kg' => 2000,
        'max_capacity_units' => 300,
        'is_active' => true,
    ]);

    $this->driverUser = User::create([
        'full_name' => 'Driver User',
        'email' => 'driver@example.com',
        'phone' => '+94770000002',
        'password_hash' => bcrypt('password'),
        'role' => 'driver',
    ]);

    $this->driver = Driver::create([
        'user_id' => $this->driverUser->id,
        'license_number' => 'B1234567',
        'is_active' => true,
    ]);
});

it('can list trips', function () {
    Trip::create([
        'trip_code' => 'TRIP-1',
        'route_id' => $this->route->id,
        'lorry_id' => $this->lorry->id,
        'driver_id' => $this->driver->id,
        'scheduled_departure' => now()->addDay(),
        'scheduled_arrival' => now()->addDay()->addHours(3),
        'status' => 'SCHEDULED',
        'capacity_units_max' => 300,
        'capacity_units_used' => 0,
    ]);

    $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/trips');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'trips' => [
                    '*' => [
                        'id', 'trip_code', 'status'
                    ]
                ],
                'meta'
            ]
        ]);
});

it('can create a trip', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/trips', [
        'trip_code' => 'TRIP-2',
        'route_id' => $this->route->id,
        'lorry_id' => $this->lorry->id,
        'driver_id' => $this->driver->id,
        'scheduled_departure' => now()->addDay()->toDateTimeString(),
        'scheduled_arrival' => now()->addDay()->addHours(3)->toDateTimeString(),
        'capacity_units_max' => 300,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.trip.trip_code', 'TRIP-2');

    $this->assertDatabaseHas('trips', ['trip_code' => 'TRIP-2']);
});
