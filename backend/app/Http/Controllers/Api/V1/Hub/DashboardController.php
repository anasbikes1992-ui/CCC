<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Hub;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $hubId = Auth::user()->hub_staff?->hub_id;

        if (! $hubId) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'NO_HUB_ASSIGNED', 'message' => 'User has no hub assignment.'],
            ], 403);
        }

        $today = now()->toDateString();

        // Parcels physically at this hub right now
        $atHub = Parcel::query()
            ->whereIn('status', ['RECEIVED_AT_ORIGIN_HUB', 'ARRIVED_AT_DESTINATION_HUB'])
            ->where(function ($q) use ($hubId) {
                $q->whereHas('trip.route', fn ($r) => $r->where('origin_hub_id', $hubId))
                  ->orWhereHas('trip.route', fn ($r) => $r->where('destination_hub_id', $hubId));
            })
            ->count();

        // Inbound trips arriving today (destination = this hub, not yet arrived)
        $inboundToday = Trip::query()
            ->whereHas('route', fn ($q) => $q->where('destination_hub_id', $hubId))
            ->whereDate('scheduled_arrival_at', $today)
            ->whereIn('status', ['scheduled', 'in_transit'])
            ->count();

        // Outbound trips departing today (origin = this hub, parcels still to load)
        $outboundToday = Trip::query()
            ->whereHas('route', fn ($q) => $q->where('origin_hub_id', $hubId))
            ->whereDate('scheduled_departure_at', $today)
            ->whereIn('status', ['scheduled'])
            ->count();

        return response()->json([
            'success' => true,
            'data'    => compact('atHub', 'inboundToday', 'outboundToday'),
        ]);
    }
}
