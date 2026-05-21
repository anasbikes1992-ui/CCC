<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTripRequest;
use App\Http\Requests\Admin\UpdateTripRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Trip::with(['route.originHub', 'route.destinationHub', 'lorry', 'driver']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->input('route_id'));
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $query->whereDate('scheduled_departure', $date);
        }

        $trips = $query->orderBy('scheduled_departure', 'asc')->paginate($request->input('limit', 50));

        return ApiResponse::success([
            'trips' => $trips->items(),
            'meta' => [
                'total' => $trips->total(),
                'page' => $trips->currentPage(),
                'last_page' => $trips->lastPage(),
            ],
        ]);
    }

    public function store(StoreTripRequest $request): JsonResponse
    {
        $trip = Trip::create(array_merge(
            $request->validated(),
            ['status' => TripStatus::SCHEDULED, 'capacity_units_used' => 0]
        ));

        $trip->load(['route.originHub', 'route.destinationHub', 'lorry', 'driver']);

        return ApiResponse::success([
            'trip' => $trip,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $trip = Trip::with([
            'route.originHub', 
            'route.destinationHub', 
            'lorry', 
            'driver', 
            'parcels'
        ])->findOrFail($id);

        return ApiResponse::success([
            'trip' => $trip,
        ]);
    }

    public function update(UpdateTripRequest $request, string $id): JsonResponse
    {
        $trip = Trip::findOrFail($id);

        $trip->update($request->validated());
        
        $trip->loadMissing(['route.originHub', 'route.destinationHub', 'lorry', 'driver']);

        return ApiResponse::success([
            'trip' => $trip,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $trip = Trip::findOrFail($id);

        if ($trip->parcels()->exists()) {
            return ApiResponse::error(
                'CANNOT_DELETE_TRIP',
                'Cannot delete a trip that has assigned parcels.',
                [],
                422
            );
        }

        $trip->delete();

        return ApiResponse::success([
            'message' => 'Trip deleted successfully.',
        ]);
    }
}
