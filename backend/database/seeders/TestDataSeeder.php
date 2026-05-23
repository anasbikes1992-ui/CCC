<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Route;
use App\Models\Parcel;
use App\Models\PackageSize;
use App\Enums\ParcelStatus;
use App\Enums\PaymentMethod;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sender = User::where('email', 'sender@test.com')->first();
        $route = Route::where('code', 'CMB-KDY')->first();
        $size = PackageSize::where('code', 'M')->first();

        if (!$sender || !$route || !$size) {
            $this->command->error('Required users, routes, or package sizes not found.');
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
                'parcel_number' => $parcelNumber,
                'qr_token' => 'TEST-QR-' . $parcelNumber,
                'customer_id' => $sender->id,
                'route_id' => $route->id,
                'package_size_id' => $size->id,
                'status' => $status->value,
                'weight_kg' => 5.5,
                'length_cm' => 30,
                'width_cm' => 25,
                'height_cm' => 20,
                'pickup_type' => 'hub',
                'pickup_address' => '123 Main Street, Colombo 03',
                'drop_type' => 'hub',
                'drop_address' => 'Test Address Kandy ' . ($index + 1),
                'receiver_name' => 'Receiver Test ' . ($index + 1),
                'receiver_phone' => '+9477' . rand(1000000, 9999999),
                'is_express' => false,
                'has_insurance' => false,
                'declared_value_lkr' => null,
                'cod_amount_lkr' => null,
                'base_price_lkr' => 700,
                'surcharges_lkr' => 0,
                'discount_lkr' => 0,
                'total_price_lkr' => 700,
                'capacity_units' => 4, // Medium size = 4 CU
            ]);

            $parcels[] = $parcel;
        }

        $this->command->info('✓ Created ' . count($parcels) . ' test parcels in various statuses');
    }
}
