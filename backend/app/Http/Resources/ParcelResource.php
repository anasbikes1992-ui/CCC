<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Parcel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Parcel
 */
class ParcelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pricingNotes = [];
        if (is_string($this->notes) && $this->notes !== '') {
            $decoded = json_decode($this->notes, true);
            if (is_array($decoded)) {
                $pricingNotes = $decoded;
            }
        }

        $receiverCharge = (float) ($pricingNotes['receiver_charge_lkr'] ?? ((float) $this->base_price_lkr + (float) $this->surcharges_lkr - (float) $this->discount_lkr));

        return [
            'id' => $this->id,
            'parcel_number' => $this->parcel_number,
            'status' => $this->status->value,
            'status_changed_at' => $this->status_changed_at?->toIso8601String(),
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'pickup_address' => $this->pickup_address,
            'drop_address' => $this->drop_address,
            'route_code' => $this->route?->code,
            'package_size_code' => $this->packageSize?->code,
            'total_price_lkr' => (float) $this->total_price_lkr,
            'created_at' => $this->created_at?->toIso8601String(),
            'route' => $this->whenLoaded('route', fn () => [
                'code' => $this->route->code,
                'display_name' => $this->route->display_name,
                'origin_hub' => $this->route->originHub?->name,
                'destination_hub' => $this->route->destinationHub?->name,
            ]),
            'package_size' => $this->whenLoaded('packageSize', fn () => [
                'code' => $this->packageSize->code,
                'display_name' => $this->packageSize->display_name,
            ]),
            'trip' => $this->whenLoaded('trip', fn () => [
                'id' => $this->trip?->id,
                'trip_code' => $this->trip?->trip_code,
                'scheduled_departure' => $this->trip?->scheduled_departure?->toIso8601String(),
                'scheduled_arrival' => $this->trip?->scheduled_arrival?->toIso8601String(),
            ]),
            'price' => [
                'base_lkr' => (float) $this->base_price_lkr,
                'surcharges_lkr' => (float) $this->surcharges_lkr,
                'discount_lkr' => (float) $this->discount_lkr,
                'receiver_charge_lkr' => $receiverCharge,
                'sender_fee_lkr' => (float) $this->total_price_lkr,
                'total_lkr' => (float) $this->total_price_lkr,
            ],
            'capacity_units' => $this->capacity_units,
            'qr_token' => $this->qr_token,
            'tracking_url' => config('app.url').'/track/'.$this->parcel_number,
            'ops_pricing' => [
                'mode' => $pricingNotes['pricing_mode'] ?? 'hub_to_hub_colombo_pilot',
                'charge_timing' => $pricingNotes['charge_timing'] ?? 'receiver_at_collection_or_delivery',
                'assignment_status' => $pricingNotes['assignment_status'] ?? 'assigned',
            ],
        ];
    }
}
