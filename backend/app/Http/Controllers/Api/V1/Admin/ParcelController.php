<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Parcel;
use App\Services\ScanService;
use App\Enums\ParcelEventType;
use App\Enums\ParcelStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParcelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Parcel::with(['customer', 'route.originHub', 'route.destinationHub', 'packageSize', 'trip']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->input('route_id'));
        }
        if ($request->filled('trip_id')) {
            $query->where('trip_id', $request->input('trip_id'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('parcel_number', 'ilike', "%{$search}%")
                  ->orWhereHas('customer', fn ($u) => $u->where('full_name', 'ilike', "%{$search}%")
                      ->orWhere('phone', 'ilike', "%{$search}%"));
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $limit   = min((int) $request->input('limit', 50), 100);
        $parcels = $query->orderBy('created_at', 'desc')->paginate($limit);

        return ApiResponse::success([
            'parcels' => $parcels->items(),
            'meta'    => [
                'total'     => $parcels->total(),
                'page'      => $parcels->currentPage(),
                'last_page' => $parcels->lastPage(),
                'limit'     => $limit,
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $parcel = Parcel::with([
            'customer',
            'route.originHub',
            'route.destinationHub',
            'packageSize',
            'trip.lorry',
            'trip.driver.user',
            'events',
            'deliveryProof',
            'payments',
        ])->findOrFail($id);

        return ApiResponse::success(['parcel' => $parcel]);
    }

    public function update(Request $request, string $id, ScanService $scans): JsonResponse
    {
        $parcel = Parcel::findOrFail($id);

        // Allow admin notes update
        if ($request->filled('notes')) {
            $parcel->update(['notes' => $request->input('notes')]);
        }

        // Allow admin status override (admin bypass normal transition rules)
        if ($request->filled('status')) {
            $newStatus = ParcelStatus::from($request->input('status'));
            $parcel->update([
                'status'            => $newStatus,
                'status_changed_at' => now(),
            ]);
        }

        // Allow trip reassignment
        if ($request->filled('trip_id')) {
            $parcel->update(['trip_id' => $request->input('trip_id')]);
        }

        $parcel->refresh()->load(['customer', 'route', 'trip', 'events']);

        return ApiResponse::success(['parcel' => $parcel]);
    }

    public function destroy(string $id): JsonResponse
    {
        $parcel = Parcel::findOrFail($id);
        $parcel->delete();

        return ApiResponse::success(['message' => 'Parcel soft-deleted successfully.']);
    }
}
