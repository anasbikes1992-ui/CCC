<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Driver;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Driver $driver */
        $driver = $request->attributes->get('driverProfile');

        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $request->query('date')
            ? Carbon::parse((string) $request->query('date'), 'Asia/Colombo')
            : Carbon::now('Asia/Colombo');

        $startUtc = $date->copy()->startOfDay()->setTimezone('UTC');
        $endUtc = $date->copy()->endOfDay()->setTimezone('UTC');

        $trips = Trip::query()
            ->where('driver_id', $driver->id)
            ->whereBetween('scheduled_departure', [$startUtc, $endUtc])
            ->with(['route', 'lorry'])
            ->withCount('parcels')
            ->orderBy('scheduled_departure')
            ->get();

        return ApiResponse::success([
            'items' => $trips->map(fn (Trip $trip) => [
                'id' => $trip->id,
                'trip_code' => $trip->trip_code,
                'status' => $trip->status->value,
                'scheduled_departure' => $trip->scheduled_departure?->toIso8601String(),
                'scheduled_arrival' => $trip->scheduled_arrival?->toIso8601String(),
                'route' => [
                    'code' => $trip->route?->code,
                    'display_name' => $trip->route?->display_name,
                ],
                'lorry' => $trip->lorry ? [
                    'id' => $trip->lorry->id,
                    'registration_number' => $trip->lorry->registration_number,
                ] : null,
                'parcels_count' => $trip->parcels_count,
                'capacity_units_max' => $trip->capacity_units_max,
                'capacity_units_used' => $trip->capacity_units_used,
                'capacity_units_remaining' => $trip->capacityRemaining(),
            ])->values(),
        ]);
    }

    public function parcels(Request $request, string $id): JsonResponse
    {
        /** @var Driver $driver */
        $driver = $request->attributes->get('driverProfile');

        $trip = Trip::query()
            ->where('id', $id)
            ->where('driver_id', $driver->id)
            ->with(['route', 'parcels'])
            ->firstOrFail();

        return ApiResponse::success([
            'trip' => [
                'id' => $trip->id,
                'trip_code' => $trip->trip_code,
                'status' => $trip->status->value,
                'route' => [
                    'code' => $trip->route?->code,
                    'display_name' => $trip->route?->display_name,
                ],
                'scheduled_departure' => $trip->scheduled_departure?->toIso8601String(),
                'scheduled_arrival' => $trip->scheduled_arrival?->toIso8601String(),
            ],
            'items' => $trip->parcels->map(fn ($parcel) => [
                'id' => $parcel->id,
                'parcel_number' => $parcel->parcel_number,
                'status' => $parcel->status->value,
                'receiver_name' => $parcel->receiver_name,
                'receiver_phone' => $parcel->receiver_phone,
                'capacity_units' => $parcel->capacity_units,
            ])->values(),
        ]);
    }
}
