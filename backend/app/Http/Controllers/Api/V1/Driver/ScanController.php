<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Driver;

use App\Enums\ParcelEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\ScanRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Parcel;
use App\Services\ParcelNumberService;
use App\Services\QrTokenService;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;

class ScanController extends Controller
{
    public function scan(
        ScanRequest $request,
        string $idOrNumber,
        QrTokenService $qr,
        ParcelNumberService $numbers,
        ScanService $scans,
    ): JsonResponse {
        $scanMode = $request->header('X-Scan-Mode', 'qr');

        // Resolve parcel: by JWT (qr/barcode) or by parcel number (manual).
        $parcel = $scanMode === 'manual'
            ? $this->resolveByNumber($idOrNumber, $numbers)
            : $this->resolveByToken($idOrNumber, $request->validated('qr_token'), $qr);

        $eventType = ParcelEventType::from($request->validated('event_type'));

        $event = $scans->record(
            parcel: $parcel,
            eventType: $eventType,
            actor: $request->user(),
            scanMode: (string) $scanMode,
            deviceId: $request->validated('device_id'),
            geo: $request->validated('geo'),
            metadata: $request->validated('metadata') ?? [],
            occurredAt: $request->validated('occurred_at')
                ? \Carbon\Carbon::parse($request->validated('occurred_at'))
                : null,
        );

        $parcel = $parcel->fresh();

        return ApiResponse::success([
            'parcel' => [
                'id' => $parcel->id,
                'parcel_number' => $parcel->parcel_number,
                'status' => $parcel->status->value,
                'status_changed_at' => $parcel->status_changed_at?->toIso8601String(),
            ],
            'event' => [
                'id' => $event->id,
                'event_type' => $event->event_type->value,
                'occurred_at' => $event->occurred_at?->toIso8601String(),
            ],
        ]);
    }

    private function resolveByToken(string $idOrNumber, ?string $qrToken, QrTokenService $qr): Parcel
    {
        if (! $qrToken) {
            return Parcel::where('id', $idOrNumber)
                ->orWhere('parcel_number', $idOrNumber)
                ->firstOrFail();
        }

        $payload = $qr->verify($qrToken);

        return Parcel::where('id', $payload['parcel_uuid'])->firstOrFail();
    }

    private function resolveByNumber(string $parcelNumber, ParcelNumberService $numbers): Parcel
    {
        if (! $numbers->isValid($parcelNumber)) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Bad parcel number');
        }

        return Parcel::where('parcel_number', $parcelNumber)->firstOrFail();
    }
}
