<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Hub;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ScanController extends Controller
{
    public function __construct(private readonly ScanService $scanService) {}

    /**
     * Hub staff performs a scan action on a parcel.
     *
     * event options:
     *   RECEIVED_AT_ORIGIN_HUB      — parcel dropped off / collected at origin hub
     *   RECEIVED_AT_DESTINATION_HUB — parcel arrived at destination hub (in-transit trip arrived)
     *   LOADED_ON_LORRY             — parcel loaded onto an outbound lorry/trip
     *   OUT_FOR_DELIVERY            — parcel handed to delivery driver
     */
    public function scan(Request $request, string $idOrNumber): JsonResponse
    {
        $hubId = Auth::user()->hub_staff->hub_id;

        $parcel = Parcel::query()
            ->where('id', $idOrNumber)
            ->orWhere('parcel_number', $idOrNumber)
            ->firstOrFail();

        $validated = $request->validate([
            'event'   => ['required', Rule::in([
                'RECEIVED_AT_ORIGIN_HUB',
                'RECEIVED_AT_DESTINATION_HUB',
                'LOADED_ON_LORRY',
                'OUT_FOR_DELIVERY',
            ])],
            'trip_id' => ['required_if:event,LOADED_ON_LORRY', 'nullable', 'uuid', 'exists:trips,id'],
            'notes'   => ['nullable', 'string', 'max:500'],
            'lat'     => ['nullable', 'numeric', 'between:-90,90'],
            'lng'     => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->scanService->handle(
            parcel:    $parcel,
            event:     $validated['event'],
            scannedBy: Auth::user(),
            hubId:     $hubId,
            tripId:    $validated['trip_id'] ?? null,
            notes:     $validated['notes'] ?? null,
            lat:       isset($validated['lat']) ? (float) $validated['lat'] : null,
            lng:       isset($validated['lng']) ? (float) $validated['lng'] : null,
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'error'   => [
                    'code'    => $result['code'] ?? 'SCAN_FAILED',
                    'message' => $result['message'],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'parcel_number' => $parcel->parcel_number,
                'new_status'    => $result['status'],
                'event_id'      => $result['event_id'],
            ],
            'error'   => null,
        ]);
    }

    /**
     * Look up a parcel by number before scanning — shows current status summary.
     */
    public function lookup(string $idOrNumber): JsonResponse
    {
        $parcel = Parcel::query()
            ->where('id', $idOrNumber)
            ->orWhere('parcel_number', $idOrNumber)
            ->with([
                'packageSize:code,label',
                'trip:id,scheduled_departure_at,status',
                'trip.route:id,code',
            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $parcel->id,
                'parcel_number'  => $parcel->parcel_number,
                'status'         => $parcel->status,
                'size'           => $parcel->packageSize?->label,
                'sender_name'    => $parcel->sender_name,
                'receiver_name'  => $parcel->receiver_name,
                'pickup_point'   => $parcel->pickup_point,
                'drop_point'     => $parcel->drop_point,
                'trip'           => $parcel->trip ? [
                    'id'                    => $parcel->trip->id,
                    'route'                 => $parcel->trip->route->code,
                    'scheduled_departure'   => $parcel->trip->scheduled_departure_at,
                    'status'                => $parcel->trip->status,
                ] : null,
            ],
        ]);
    }
}
