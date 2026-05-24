<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ParcelEventType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\TripFullException;
use App\Models\Hub;
use App\Models\PackageSize;
use App\Models\Parcel;
use App\Models\ParcelEvent;
use App\Models\Payment;
use App\Models\Route;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates the full booking flow:
 *   validate → quote → assign trip → insert parcel → sign QR → create payment → record BOOKED event.
 *
 * One DB transaction so partial failures don't leave orphan rows or wrongly-decremented capacity.
 */
class BookingService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly TripAssignmentService $tripAssignment,
        private readonly ParcelNumberService $numbers,
        private readonly QrTokenService $qr,
        private readonly ScanService $scans,
    ) {}

    /**
     * @param  array<string, mixed>  $input  validated request body
     */
    public function book(User $customer, array $input): Parcel
    {
        $route = Route::where('code', $input['route_code'])->firstOrFail();
        $size = PackageSize::where('code', $input['package_size_code'])->firstOrFail();
        $pickupType = 'hub';
        $dropType = 'hub';

        $quote = $this->pricing->quote(
            routeCode: $input['route_code'],
            sizeCode: $input['package_size_code'],
            pickupType: $pickupType,
            dropType: $dropType,
            isExpress: (bool) ($input['is_express'] ?? false),
            hasInsurance: (bool) ($input['has_insurance'] ?? false),
            declaredValueLkr: $input['declared_value_lkr'] ?? null,
            codAmountLkr: $input['cod_amount_lkr'] ?? null,
        );

        return DB::transaction(function () use ($customer, $route, $size, $quote, $input, $pickupType, $dropType) {
            try {
                $trip = $this->tripAssignment->nextAvailable(
                    routeCode: $input['route_code'],
                    capacityUnits: $size->capacity_units,
                );
            } catch (TripFullException) {
                $trip = null;
            }

            $parcelNumber = $this->numbers->generate();
            $parcelId = (string) \Illuminate\Support\Str::uuid();

            $pickupHubId = $route->origin_hub_id ?? Hub::where('code', 'CMB')->value('id');
            $dropHubId = $route->destination_hub_id;

            $qrToken = $this->qr->sign($parcelId, $parcelNumber);
            $pricingNotes = [
                'pricing_mode' => 'hub_to_hub_colombo_pilot',
                'sender_fee_lkr' => $quote['sender_fee_lkr'],
                'receiver_charge_lkr' => $quote['receiver_charge_lkr'],
                'charge_timing' => 'receiver_at_collection_or_delivery',
                'matrix_adjustable_by' => ['admin', 'driver'],
            ];
            if ($trip === null) {
                $pricingNotes['assignment_status'] = 'pending_dispatch_assignment';
            }

            $parcel = new Parcel([
                'parcel_number' => $parcelNumber,
                'qr_token' => $qrToken,
                'customer_id' => $customer->id,
                'trip_id' => $trip?->id,
                'route_id' => $route->id,
                'package_size_id' => $size->id,
                'weight_kg' => $input['weight_kg'],
                'length_cm' => $input['length_cm'] ?? null,
                'width_cm' => $input['width_cm'] ?? null,
                'height_cm' => $input['height_cm'] ?? null,
                'pickup_type' => $pickupType,
                'pickup_address' => null,
                'pickup_hub_id' => $pickupHubId,
                'drop_type' => $dropType,
                'drop_address' => null,
                'drop_hub_id' => $dropHubId,
                'receiver_name' => $input['receiver_name'],
                'receiver_phone' => $input['receiver_phone'],
                'declared_value_lkr' => $input['declared_value_lkr'] ?? null,
                'cod_amount_lkr' => $input['cod_amount_lkr'] ?? null,
                'is_express' => (bool) ($input['is_express'] ?? false),
                'has_insurance' => (bool) ($input['has_insurance'] ?? false),
                'base_price_lkr' => $quote['base_lkr'],
                'surcharges_lkr' => $quote['surcharges_lkr'],
                'discount_lkr' => $quote['discount_lkr'],
                'total_price_lkr' => $quote['sender_fee_lkr'],
                'capacity_units' => $size->capacity_units,
                'status' => 'BOOKED',
                'status_changed_at' => now(),
                'notes' => json_encode($pricingNotes, JSON_UNESCAPED_SLASHES),
            ]);
            $parcel->id = $parcelId;
            $parcel->save();

            // Optional pickup/drop geos.
            if (! empty($input['pickup_geo']['lat']) && ! empty($input['pickup_geo']['lng'])) {
                $parcel->pickup_lat = (float) $input['pickup_geo']['lat'];
                $parcel->pickup_lng = (float) $input['pickup_geo']['lng'];
            }
            if (! empty($input['drop_geo']['lat']) && ! empty($input['drop_geo']['lng'])) {
                $parcel->drop_lat = (float) $input['drop_geo']['lat'];
                $parcel->drop_lng = (float) $input['drop_geo']['lng'];
            }
            $parcel->save();

            Payment::create([
                'parcel_id' => $parcel->id,
                'method' => PaymentMethod::from($input['payment_method']),
                'status' => PaymentStatus::PENDING,
                'amount_lkr' => $parcel->total_price_lkr,
                'metadata' => [
                    'sender_fee_lkr' => $quote['sender_fee_lkr'],
                    'receiver_charge_lkr' => $quote['receiver_charge_lkr'],
                    'charge_timing' => 'receiver_at_collection_or_delivery',
                ],
            ]);

            // Initial BOOKED event — written directly because there is no prior status to transition from.
            // Subsequent transitions all go through ScanService::record() which validates against ADR 0002.
            ParcelEvent::create([
                'parcel_id' => $parcel->id,
                'event_type' => ParcelEventType::BOOKED,
                'from_status' => null,
                'to_status' => 'BOOKED',
                'actor_user_id' => $customer->id,
                'actor_role' => $customer->role,
                'trip_id' => $trip?->id,
                'scan_mode' => 'system',
                'metadata' => ['initial' => true, 'trip_assignment_pending' => $trip === null],
                'occurred_at' => now(),
            ]);

            return $parcel->fresh(['trip', 'payments']);
        });
    }
}
