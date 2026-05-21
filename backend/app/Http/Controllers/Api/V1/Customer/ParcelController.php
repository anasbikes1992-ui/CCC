<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\BookParcelRequest;
use App\Http\Resources\ParcelResource;
use App\Http\Responses\ApiResponse;
use App\Models\Parcel;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Throwable;

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

    public function label(Request $request, string $id): Response|JsonResponse
    {
        $parcel = Parcel::query()
            ->where('id', $id)
            ->where('customer_id', $request->user()->id)
            ->with(['route', 'trip', 'packageSize', 'pickupHub', 'dropHub'])
            ->firstOrFail();

        $pickup = $parcel->pickup_type === 'hub'
            ? ($parcel->pickupHub?->name ?? 'Hub')
            : ($parcel->pickup_address ?? 'Doorstep Pickup');
        $drop = $parcel->drop_type === 'hub'
            ? ($parcel->dropHub?->name ?? 'Hub')
            : ($parcel->drop_address ?? 'Doorstep Delivery');

        $html = '<html><body style="font-family: sans-serif; font-size: 12px;">'
            .'<h2 style="margin:0 0 6px 0;">'.$parcel->parcel_number.'</h2>'
            .'<div style="margin-bottom:6px;"><strong>Route:</strong> '.e($parcel->route?->display_name ?? $parcel->route?->code ?? 'N/A').'</div>'
            .'<div style="margin-bottom:6px;"><strong>From:</strong> '.e($pickup).'</div>'
            .'<div style="margin-bottom:6px;"><strong>To:</strong> '.e($drop).'</div>'
            .'<div style="margin-bottom:6px;"><strong>Receiver:</strong> '.e($parcel->receiver_name).' ('.e($parcel->receiver_phone).')</div>'
            .'<div style="margin-bottom:6px;"><strong>Size:</strong> '.e($parcel->packageSize?->code ?? 'N/A').'</div>'
            .'<div><strong>Trip:</strong> '.e($parcel->trip?->trip_code ?? 'Unassigned').'</div>'
            .'</body></html>';

        try {
            $pdf = Pdf::loadHTML($html)->setPaper([0, 0, 288, 432]);

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="label-'.$parcel->parcel_number.'.pdf"',
            ]);
        } catch (Throwable) {
            return ApiResponse::error('SERVER_ERROR', 'Failed to generate label PDF', [], 500);
        }
    }
}
