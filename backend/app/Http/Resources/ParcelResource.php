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
        return [
            'id' => $this->id,
            'parcel_number' => $this->parcel_number,
            'status' => $this->status->value,
            'status_changed_at' => $this->status_changed_at?->toIso8601String(),
            'route' => $this->whenLoaded('route', fn () => [
                'code' => $this->route->code,
                'display_name' => $this->route->display_name,
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
                'total_lkr' => (float) $this->total_price_lkr,
            ],
            'capacity_units' => $this->capacity_units,
            'qr_token' => $this->when($request->routeIs('customer.parcels.store'), $this->qr_token),
            'tracking_url' => config('app.url').'/track/'.$this->parcel_number,
        ];
    }
}
