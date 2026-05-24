<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Customer;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\BookParcelRequest;
use App\Http\Resources\ParcelResource;
use App\Http\Responses\ApiResponse;
use App\Models\PackageSize;
use App\Models\Parcel;
use App\Models\Route;
use App\Services\BookingService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Throwable;

class ParcelController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $base = Parcel::query()->where('customer_id', $request->user()->id);

        $total = (clone $base)->count();
        $inTransit = (clone $base)
            ->whereIn('status', ['IN_TRANSIT', 'OUT_FOR_DELIVERY', 'LOADED_ON_LORRY', 'ARRIVED_AT_DESTINATION_HUB'])
            ->count();
        $delivered = (clone $base)->where('status', 'DELIVERED')->count();

        return ApiResponse::success([
            'total' => $total,
            'in_transit' => $inTransit,
            'delivered' => $delivered,
            'pending' => max($total - $inTransit - $delivered, 0),
        ]);
    }

    public function routes(): JsonResponse
    {
        $routes = Route::query()
            ->where('is_active', true)
            ->with(['originHub:id,name', 'destinationHub:id,name'])
            ->orderBy('display_name')
            ->get()
            ->map(fn (Route $route) => [
                'id' => $route->id,
                'code' => $route->code,
                'display_name' => $route->display_name,
                'name' => $route->display_name,
                'origin_hub' => $route->originHub?->name,
                'destination_hub' => $route->destinationHub?->name,
            ]);

        return ApiResponse::success($routes);
    }

    public function packageSizes(): JsonResponse
    {
        $sizes = PackageSize::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PackageSize $size) => [
                'id' => $size->id,
                'code' => $size->code,
                'display_name' => $size->display_name,
                'name' => $size->display_name,
                'max_weight_kg' => (float) $size->max_weight_kg,
                'capacity_units' => $size->capacity_units,
            ]);

        return ApiResponse::success($sizes);
    }

    public function quote(Request $request, PricingService $pricing): JsonResponse
    {
        $validated = $request->validate([
            'route_code' => ['required', 'string', 'exists:routes,code'],
            'package_size_code' => ['required', 'string', 'exists:package_sizes,code'],
            'pickup_type' => ['nullable', 'in:hub,doorstep'],
            'drop_type' => ['nullable', 'in:hub,doorstep'],
            'is_express' => ['nullable', 'boolean'],
            'has_insurance' => ['nullable', 'boolean'],
            'declared_value_lkr' => ['nullable', 'numeric', 'min:0'],
            'cod_amount_lkr' => ['nullable', 'numeric', 'min:0'],
        ]);

        $quote = $pricing->quote(
            routeCode: $validated['route_code'],
            sizeCode: $validated['package_size_code'],
            pickupType: $validated['pickup_type'] ?? 'hub',
            dropType: $validated['drop_type'] ?? 'hub',
            isExpress: (bool) ($validated['is_express'] ?? false),
            hasInsurance: (bool) ($validated['has_insurance'] ?? false),
            declaredValueLkr: isset($validated['declared_value_lkr']) ? (float) $validated['declared_value_lkr'] : null,
            codAmountLkr: isset($validated['cod_amount_lkr']) ? (float) $validated['cod_amount_lkr'] : null,
        );

        return ApiResponse::success($quote);
    }

    public function index(Request $request): JsonResponse
    {
        $parcels = Parcel::query()
            ->where('customer_id', $request->user()->id)
            ->with(['route', 'trip', 'packageSize'])
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
            ->with(['route', 'trip', 'events', 'packageSize'])
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
