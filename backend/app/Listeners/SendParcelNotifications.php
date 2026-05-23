<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ParcelStatus;
use App\Events\ParcelStatusChanged;
use App\Jobs\SendWhatsAppNotification;
use App\Services\OTPService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendParcelNotifications
{
    public function __construct(
        private WhatsAppService $whatsAppService,
        private OTPService $otpService,
    ) {}

    public function handle(ParcelStatusChanged $event): void
    {
        $parcel = $event->parcel;
        $parcelEvent = $event->parcelEvent;
        $status = ParcelStatus::tryFrom($parcel->status);

        if (!$status) {
            return;
        }

        $trackingUrl = config('app.frontend_url') . '/track/' . $parcel->parcel_number;

        match ($status) {
            ParcelStatus::BOOKED => $this->sendBookingConfirmed($parcel, $trackingUrl),
            ParcelStatus::PICKED_UP => $this->sendPickedUp($parcel, $trackingUrl),
            ParcelStatus::RECEIVED_AT_ORIGIN_HUB => $this->sendArrivedAtOriginHub($parcel, $trackingUrl),
            ParcelStatus::IN_TRANSIT => $this->sendInTransit($parcel, $trackingUrl),
            ParcelStatus::ARRIVED_AT_DESTINATION_HUB => $this->sendReadyForPickup($parcel, $trackingUrl),
            ParcelStatus::OUT_FOR_DELIVERY => $this->sendOutForDelivery($parcel, $trackingUrl),
            ParcelStatus::DELIVERED => $this->sendDelivered($parcel),
            ParcelStatus::DELIVERY_FAILED => $this->sendDeliveryFailed($parcel, $trackingUrl),
            default => null,
        };
    }

    private function sendBookingConfirmed($parcel, string $trackingUrl): void
    {
        $customer = $parcel->customer;
        $route = $parcel->route;
        $trip = $parcel->trip;

        $params = [
            $customer->name,
            $parcel->parcel_number,
            $route->origin_hub_name ?? 'Origin',
            $route->destination_hub_name ?? 'Destination',
            $trip ? $trip->departure_time->format('M d, Y h:i A') : 'Scheduled',
            $trip ? $trip->estimated_arrival->format('M d, Y h:i A') : 'TBD',
            $trackingUrl,
        ];

        SendWhatsAppNotification::dispatch(
            $customer->phone,
            'booking_confirmed',
            'en',
            $params
        );
    }

    private function sendPickedUp($parcel, string $trackingUrl): void
    {
        $params = [
            $parcel->parcel_number,
            $parcel->route->origin_hub_name ?? 'Origin',
            $parcel->route->destination_hub_name ?? 'Destination',
            now()->format('M d, Y h:i A'),
            $trackingUrl,
        ];

        // Notify sender
        SendWhatsAppNotification::dispatch(
            $parcel->customer->phone,
            'parcel_picked_up',
            'en',
            $params
        );

        // Notify receiver
        SendWhatsAppNotification::dispatch(
            $parcel->receiver_phone,
            'parcel_picked_up',
            'en',
            $params
        );
    }

    private function sendArrivedAtOriginHub($parcel, string $trackingUrl): void
    {
        $params = [
            $parcel->parcel_number,
            $parcel->route->origin_hub_name ?? 'Origin Hub',
            $trackingUrl,
        ];

        SendWhatsAppNotification::dispatch(
            $parcel->customer->phone,
            'arrived_at_origin_hub',
            'en',
            $params
        );

        SendWhatsAppNotification::dispatch(
            $parcel->receiver_phone,
            'arrived_at_origin_hub',
            'en',
            $params
        );
    }

    private function sendInTransit($parcel, string $trackingUrl): void
    {
        $trip = $parcel->trip;

        $params = [
            $parcel->parcel_number,
            $parcel->route->origin_hub_name ?? 'Origin',
            $trip ? $trip->departure_time->format('M d, Y h:i A') : now()->format('M d, Y h:i A'),
            $parcel->route->destination_hub_name ?? 'Destination',
            $trip ? $trip->estimated_arrival->format('M d, Y h:i A') : 'TBD',
            $trackingUrl,
        ];

        SendWhatsAppNotification::dispatch(
            $parcel->receiver_phone,
            'in_transit',
            'en',
            $params
        );
    }

    private function sendReadyForPickup($parcel, string $trackingUrl): void
    {
        // Generate OTP for pickup verification
        $otp = $this->otpService->generateDeliveryOTP($parcel);

        $pickupLocation = $parcel->drop_type === 'hub' 
            ? ($parcel->dropHub->name ?? 'Destination Hub')
            : 'Doorstep delivery';

        $params = [
            $parcel->receiver_name,                                    // 1: Receiver Name
            $parcel->customer->name,                                   // 2: Sender Name
            $parcel->parcel_number,                                    // 3: Parcel Number
            $parcel->packageSize->name ?? $parcel->size,              // 4: Size
            number_format($parcel->weight_kg, 2) . ' kg',            // 5: Weight
            $parcel->route->origin_hub_name ?? 'Origin',              // 6: Origin
            $parcel->route->destination_hub_name ?? 'Destination',    // 7: Destination
            $pickupLocation,                                           // 8: Pickup Location
            $otp,                                                      // 9: OTP (6 digits)
            $trackingUrl,                                              // 10: Tracking URL
        ];

        SendWhatsAppNotification::dispatch(
            $parcel->receiver_phone,
            'ready_for_pickup',
            'en',
            $params
        );

        Log::info('Generated delivery OTP for parcel', [
            'parcel_id' => $parcel->id,
            'parcel_number' => $parcel->parcel_number,
            'receiver_phone' => $parcel->receiver_phone,
        ]);
    }

    private function sendOutForDelivery($parcel, string $trackingUrl): void
    {
        $trip = $parcel->trip;
        $driver = $trip?->driver;

        $params = [
            $parcel->parcel_number,
            $driver ? $driver->name : 'Driver',
            $driver ? $driver->phone : 'N/A',
            'Within 2 hours',
            $trackingUrl,
        ];

        SendWhatsAppNotification::dispatch(
            $parcel->receiver_phone,
            'out_for_delivery',
            'en',
            $params
        );
    }

    private function sendDelivered($parcel): void
    {
        $deliveryProof = $parcel->deliveryProof;

        $params = [
            $parcel->parcel_number,
            $parcel->receiver_name,
            $deliveryProof ? ('NIC: ***' . substr($deliveryProof->receiver_nic_encrypted, -4)) : 'Verified',
            now()->format('M d, Y h:i A'),
        ];

        // Notify sender
        SendWhatsAppNotification::dispatch(
            $parcel->customer->phone,
            'delivered',
            'en',
            $params
        );

        // Notify receiver
        SendWhatsAppNotification::dispatch(
            $parcel->receiver_phone,
            'delivered',
            'en',
            $params
        );

        // Notify admin (optional - can be configured)
        $adminPhone = config('services.whatsapp.admin_phone');
        if ($adminPhone) {
            SendWhatsAppNotification::dispatch(
                $adminPhone,
                'delivered',
                'en',
                $params
            );
        }

        Log::info('Parcel delivered successfully', [
            'parcel_id' => $parcel->id,
            'parcel_number' => $parcel->parcel_number,
        ]);
    }

    private function sendDeliveryFailed($parcel, string $trackingUrl): void
    {
        $params = [
            $parcel->parcel_number,
            'Receiver unavailable',
            $trackingUrl,
        ];

        SendWhatsAppNotification::dispatch(
            $parcel->customer->phone,
            'delivery_failed',
            'en',
            $params
        );
    }
}
