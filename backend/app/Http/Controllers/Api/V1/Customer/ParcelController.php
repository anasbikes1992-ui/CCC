<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\BookParcelRequest;
use App\Http\Resources\ParcelResource;
use App\Http\Responses\ApiResponse;
use App\Models\Parcel;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParcelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $parcels = Parcel::query()
            ->where('customer_id', $request->user()->id)
            ->with(['route', 'trip'])
            ->orderByDesc('created_at')
            ->limit((int) min($request->query('limit', 50), 100))
            ->offset((int) $request->query('offset', 0))
            ->get();

        return ApiResponse::success(
            ParcelResource::collection($parcels)->resolve(),
            ['count' => $parcels->count()]
        );
    }

    public function store(BookParcelRequest $request, BookingService $booking): JsonResponse
    {
        $parcel = $booking->book($request->user(), $request->validated());

        return ApiResponse::success([
            'parcel' => (new ParcelResource($parcel))->resolve($request),
            'payment' => [
                'id' => $parcel->payments->first()?->id,
                'method' => $parcel->payments->first()?->method->value,
                'status' => $parcel->payments->first()?->status->value,
                'amount_lkr' => (float) ($parcel->payments->first()?->amount_lkr ?? 0),
            ],
        ], status: 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $parcel = Parcel::query()
            ->where('id', $id)
            ->where('customer_id', $request->user()->id)
            ->with(['route', 'trip', 'events'])
            ->firstOrFail();

        return ApiResponse::success([
            'parcel' => (new ParcelResource($parcel))->resolve($request),
            'events' => $parcel->events->map(fn ($e) => [
                'event_type' => $e->event_type->value,
                'occurred_at' => $e->occurred_at?->toIso8601String(),
                'from_status' => $e->from_status,
                'to_status' => $e->to_status,
            ]),
        ]);
    }
}
