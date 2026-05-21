<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Hub;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    private function hubId(): string
    {
        return Auth::user()->hub_staff->hub_id;
    }

    /**
     * Parcels currently physically at this hub.
     */
    public function index(Request $request): JsonResponse
    {
        $hubId = $this->hubId();
        $today = now()->toDateString();

        $parcels = Parcel::query()
            ->whereIn('status', ['RECEIVED_AT_ORIGIN_HUB', 'ARRIVED_AT_DESTINATION_HUB'])
            ->with([
                'packageSize:code,label',
                'trip:id,scheduled_departure_at',
                'trip.route:id,code,origin_hub_id,destination_hub_id',
            ])
            ->where(function ($q) use ($hubId) {
                $q->whereHas('trip.route', fn ($r) => $r->where('origin_hub_id', $hubId))
                  ->orWhereHas('trip.route', fn ($r) => $r->where('destination_hub_id', $hubId));
            })
            ->orderBy('created_at')
            ->paginate($request->integer('limit', 50));

        return response()->json([
            'success' => true,
            'data'    => $parcels->items(),
            'meta'    => ['total' => $parcels->total(), 'page' => $parcels->currentPage()],
        ]);
    }

    /**
     * Inbound trips arriving at this hub today.
     */
    public function inbound(Request $request): JsonResponse
    {
        $hubId = $this->hubId();
        $date  = $request->input('date', now()->toDateString());

        $trips = Trip::query()
            ->whereHas('route', fn ($q) => $q->where('destination_hub_id', $hubId))
            ->whereDate('scheduled_arrival_at', $date)
            ->with([
                'route:id,code',
                'lorry:id,plate_number',
                'driver:id,name',
                'parcels:id,parcel_number,status,size_code',
            ])
            ->withCount('parcels')
            ->orderBy('scheduled_arrival_at')
            ->get();

        return response()->json(['success' => true, 'data' => $trips]);
    }

    /**
     * Outbound trips departing from this hub today with unloaded parcels.
     */
    public function outbound(Request $request): JsonResponse
    {
        $hubId = $this->hubId();
        $date  = $request->input('date', now()->toDateString());

        $trips = Trip::query()
            ->whereHas('route', fn ($q) => $q->where('origin_hub_id', $hubId))
            ->whereDate('scheduled_departure_at', $date)
            ->whereIn('status', ['scheduled'])
            ->with([
                'route:id,code',
                'lorry:id,plate_number',
                'driver:id,name',
                'parcels:id,parcel_number,status,size_code',
            ])
            ->withCount('parcels')
            ->orderBy('scheduled_departure_at')
            ->get();

        return response()->json(['success' => true, 'data' => $trips]);
    }

    /**
     * Printable manifest for a specific trip.
     */
    public function manifest(string $tripId): JsonResponse
    {
        $hubId = $this->hubId();

        $trip = Trip::query()
            ->where('id', $tripId)
            ->whereHas('route', function ($q) use ($hubId) {
                $q->where('origin_hub_id', $hubId)
                  ->orWhere('destination_hub_id', $hubId);
            })
            ->with([
                'route:id,code',
                'lorry:id,plate_number,type',
                'driver:id,name,phone',
                'parcels' => fn ($q) => $q->select([
                    'id', 'parcel_number', 'size_code', 'status',
                    'sender_name', 'receiver_name', 'receiver_phone',
                    'pickup_point', 'drop_point', 'declared_value',
                ]),
            ])
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $trip]);
    }
}
