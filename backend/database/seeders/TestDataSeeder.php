<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Route;
use App\Models\Parcel;
use App\Enums\UserRole;
use App\Enums\PackageSize;
use App\Enums\ParcelStatus;
use App\Enums\PaymentMethod;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sender = User::where('email', 'sender@test.com')->first();
        $route = Route::where('code', 'CMB-KDY')->first();

        if (!$sender || !$route) {
            $this->command->error('Required users or routes not found. Run UserSeeder and RouteSeeder first.');
            return;
        }

        // Create test parcels in various statuses
        $statuses = [
            ParcelStatus::BOOKED,
            ParcelStatus::LABEL_PRINTED,
            ParcelStatus::PICKED_UP,
            ParcelStatus::RECEIVED_AT_ORIGIN_HUB,
            ParcelStatus::IN_TRANSIT,
            ParcelStatus::ARRIVED_AT_DESTINATION_HUB,
        ];

        $parcels = [];
        foreach ($statuses as $index => $status) {
            $parcelNumber = 'CCC-' . date('Ymd') . '-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT) . '-' . rand(0, 9);
            
            $parcel = Parcel::create([
                'id' => Str::uuid(),
                'parcel_number' => $parcelNumber,
                'sender_id' => $sender->id,
                'route_id' => $route->id,
                'status' => $status->value,
                'size' => PackageSize::MEDIUM->value,
                'weight_kg' => 5.5,
                'length_cm' => 30,
                'width_cm' => 25,
                'height_cm' => 20,
                'sender_name' => $sender->name,
                'sender_phone' => $sender->phone,
                'sender_address' => '123 Main Street, Colombo 03',
                'receiver_name' => 'Receiver Test ' . ($index + 1),
                'receiver_phone' => '+9477' . rand(1000000, 9999999),
                'receiver_address' => 'Test Address Kandy ' . ($index + 1),
                'pickup_type' => 'hub',
                'drop_type' => 'hub',
                'is_express' => false,
                'has_insurance' => false,
                'has_cod' => false,
                'base_price' => 700,
                'total_surcharges' => 0,
                'total_discounts' => 0,
                'final_price' => 700,
                'payment_method' => PaymentMethod::CARD->value,
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            $parcels[] = $parcel;
        }

        $this->command->info('✓ Created ' . count($parcels) . ' test parcels in various statuses');
    }
}
