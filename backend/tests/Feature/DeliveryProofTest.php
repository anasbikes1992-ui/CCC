<?php

declare(strict_types=1);

use App\Enums\ParcelEventType;
use App\Models\DeliveryProof;
use App\Models\Driver;
use App\Models\User;
use App\Services\BookingService;
use Database\Seeders\HubSeeder;
use Database\Seeders\LorrySeeder;
use Database\Seeders\PackageSizeSeeder;
use Database\Seeders\PricingMatrixSeeder;
use Database\Seeders\RouteSeeder;
use Database\Seeders\TripSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
        'license_number' => 'DL-DELIVER-100',
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

    // Fast-forward parcel to OUT_FOR_DELIVERY so we can deliver it legally
    $this->parcel->update(['status' => 'OUT_FOR_DELIVERY']);
});

it('accepts valid delivery proof and updates parcel to DELIVERED', function () {
    Storage::fake('public');
    $token = $this->driver->createToken('drv')->plainTextToken;

    // Create a fake PNG > 5KB
    $signature = UploadedFile::fake()->image('signature.png')->size(6); // 6 KB

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('X-Scan-Mode', 'qr')
        ->postJson("/api/v1/driver/parcels/qr-scan/deliver", [
            'qr_token' => $this->parcel->qr_token,
            'receiver_name' => 'John Doe',
            'receiver_nic' => '991234567V',
            'signature' => $signature,
            'geo_lat' => 6.9271,
            'geo_lng' => 79.8612,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.parcel.status', 'DELIVERED');

    $this->parcel->refresh();
    expect($this->parcel->status->value)->toBe('DELIVERED');

    $proof = DeliveryProof::where('parcel_id', $this->parcel->id)->first();
    expect($proof)->not->toBeNull();
    expect($proof->receiver_name_input)->toBe('John Doe');
    expect($proof->receiver_nic_last4)->toBe('567V');
    expect(Crypt::decryptString($proof->receiver_nic_encrypted))->toBe('991234567V');
    expect($proof->delivery_lat)->toBe(6.9271);
    expect($proof->delivery_lng)->toBe(79.8612);

    Storage::disk('public')->assertExists($proof->signature_url);
});

it('validates minimum signature size', function () {
    Storage::fake('public');
    $token = $this->driver->createToken('drv')->plainTextToken;

    // Create a fake PNG < 5KB
    $signature = UploadedFile::fake()->image('signature.png')->size(4); // 4 KB

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('X-Scan-Mode', 'qr')
        ->postJson("/api/v1/driver/parcels/qr-scan/deliver", [
            'qr_token' => $this->parcel->qr_token,
            'receiver_name' => 'John Doe',
            'receiver_nic' => '991234567V',
            'signature' => $signature,
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.signature.0', 'The signature field must be at least 5 kilobytes.');
});

it('fails if parcel not assigned to driver', function () {
    $otherDriver = User::create([
        'full_name' => 'Other', 'phone' => '+94770004444', 'password_hash' => Hash::make('p'), 'role' => 'driver',
    ]);
    Driver::create([
        'user_id' => $otherDriver->id,
        'license_number' => 'DL-DELIVER-200',
        'license_expires_at' => now()->addYear(),
        'is_active' => true,
    ]);

    $token = $otherDriver->createToken('drv2')->plainTextToken;
    $signature = UploadedFile::fake()->image('signature.png')->size(6);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('X-Scan-Mode', 'qr')
        ->postJson("/api/v1/driver/parcels/qr-scan/deliver", [
            'qr_token' => $this->parcel->qr_token,
            'receiver_name' => 'John Doe',
            'receiver_nic' => '991234567V',
            'signature' => $signature,
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'FORBIDDEN');
});
