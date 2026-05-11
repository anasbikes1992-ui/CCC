<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Parcel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Parcel
 */
class TrackingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'parcel_number' => $this->parcel_number,
            'current_status' => $this->status->value,
            'status_changed_at' => $this->status_changed_at?->toIso8601String(),
            'route' => [
                'code' => $this->route->code,
                'display_name' => $this->route->display_name,
            ],
            'estimated_arrival' => $this->trip?->scheduled_arrival?->toIso8601String(),
            'events' => $this->events->map(fn ($e) => [
                'event_type' => $e->event_type->value,
                'occurred_at' => $e->occurred_at?->toIso8601String(),
                'location' => null, // Phase 2: enrich with hub or city name
            ]),
        ];
    }
}
