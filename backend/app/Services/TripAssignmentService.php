<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TripFullException;
use App\Models\Route;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Picks the next available trip on a route that has at least N capacity-units free,
 * within a 7-day forward window, and increments the capacity counter atomically.
 */
class TripAssignmentService
{
    public function nextAvailable(string $routeCode, int $capacityUnits): Trip
    {
        $route = Route::where('code', $routeCode)->firstOrFail();
        $now = Carbon::now();

        return DB::transaction(function () use ($route, $capacityUnits, $now, $routeCode) {
            $trip = Trip::query()
                ->where('route_id', $route->id)
                ->where('status', 'SCHEDULED')
                ->where('bookings_close_at', '>', $now)
                ->where('scheduled_departure', '<=', $now->copy()->addDays(7))
                ->whereRaw('(capacity_units_max - capacity_units_used) >= ?', [$capacityUnits])
                ->orderBy('scheduled_departure')
                ->lockForUpdate()
                ->first();

            if (! $trip) {
                throw new TripFullException($routeCode);
            }

            $trip->capacity_units_used += $capacityUnits;
            $trip->save();

            return $trip;
        });
    }

    public function release(Trip $trip, int $capacityUnits): void
    {
        DB::transaction(function () use ($trip, $capacityUnits) {
            $fresh = Trip::query()->lockForUpdate()->find($trip->id);
            if (! $fresh) {
                return;
            }
            $fresh->capacity_units_used = max(0, $fresh->capacity_units_used - $capacityUnits);
            $fresh->save();
        });
    }
}
