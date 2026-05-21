<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Driver;

use App\Enums\ParcelEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\ScanRequest;
use App\Http\Requests\Driver\DeliveryProofRequest;
use App\Http\Responses\ApiResponse;
use App\Models\DeliveryProof;
use App\Models\Driver;
use App\Models\Parcel;
use App\Services\ParcelNumberService;
use App\Services\QrTokenService;
use App\Services\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ScanController extends Controller
{
    public function scan(
        ScanRequest $request,
        string $idOrNumber,
        QrTokenService $qr,
        ParcelNumberService $numbers,
        ScanService $scans,
    ): JsonResponse {
        $scanMode = (string) $request->validated('scan_mode');

        // Resolve parcel: by JWT (qr/barcode) or by parcel number (manual).
        $parcel = $scanMode === 'manual'
            ? $this->resolveByNumber($idOrNumber, $numbers)
            : $this->resolveByToken($idOrNumber, $request->validated('qr_token'), $qr);

        $parcel->loadMissing('trip');

        /** @var Driver $driver */
        $driver = $request->attributes->get('driverProfile');
        if (! $parcel->trip || $parcel->trip->driver_id !== $driver->id) {
            return ApiResponse::error('FORBIDDEN', 'Parcel is not assigned to this driver', [], 403);
        }

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

    public function deliver(DeliveryProofRequest $request, string $idOrNumber, QrTokenService $qr, ParcelNumberService $numbers, ScanService $scans): JsonResponse
    {
        /** @var Driver $driver */
        $driver = $request->attributes->get('driverProfile');

        $scanMode = strtolower((string) $request->header('X-Scan-Mode', 'qr'));

        $parcel = $scanMode === 'manual'
            ? $this->resolveByNumber($idOrNumber, $numbers)
            : $this->resolveByToken($idOrNumber, $request->input('qr_token'), $qr);

        $parcel->loadMissing('trip');
        if (! $parcel->trip || $parcel->trip->driver_id !== $driver->id) {
            return ApiResponse::error('FORBIDDEN', 'Parcel is not assigned to this driver', [], 403);
        }

        $sigPath = $request->file('signature')->store('delivery_proofs/signatures', 'public');
        $photoPath = $request->hasFile('photo') 
            ? $request->file('photo')->store('delivery_proofs/photos', 'public')
            : null;

        $nic = $request->validated('receiver_nic');
        $nicLast4 = strlen($nic) > 4 ? substr($nic, -4) : $nic;
        
        $geo = null;
        if ($request->has('geo_lat') && $request->has('geo_lng')) {
            $geo = ['lat' => (float) $request->validated('geo_lat'), 'lng' => (float) $request->validated('geo_lng')];
        }

        DB::transaction(function () use ($parcel, $request, $driver, $sigPath, $photoPath, $nic, $nicLast4, $geo, $scans) {
            DeliveryProof::create([
                'parcel_id' => $parcel->id,
                'receiver_name_input' => $request->validated('receiver_name'),
                'receiver_nic_encrypted' => Crypt::encryptString($nic),
                'receiver_nic_last4' => $nicLast4,
                'signature_url' => $sigPath,
                'signature_size_bytes' => $request->file('signature')->getSize(),
                'photo_url' => $photoPath,
                'photo_size_bytes' => $request->hasFile('photo') ? $request->file('photo')->getSize() : null,
                'delivered_at' => $request->validated('occurred_at') ? \Carbon\Carbon::parse($request->validated('occurred_at')) : now(),
                'delivered_by_user_id' => $driver->user_id,
                'device_id' => $request->validated('device_id'),
                'delivery_lat' => $geo['lat'] ?? null,
                'delivery_lng' => $geo['lng'] ?? null,
            ]);

            $scans->record(
                parcel: $parcel,
                eventType: ParcelEventType::DELIVERED,
                actor: $request->user(),
                scanMode: 'manual',
                deviceId: $request->validated('device_id'),
                geo: $geo,
                metadata: [],
                occurredAt: $request->validated('occurred_at')
                    ? \Carbon\Carbon::parse($request->validated('occurred_at'))
                    : null,
            );
        });

        $parcel = $parcel->fresh();

        return ApiResponse::success([
            'parcel' => [
                'id' => $parcel->id,
                'parcel_number' => $parcel->parcel_number,
                'status' => $parcel->status->value,
                'status_changed_at' => $parcel->status_changed_at?->toIso8601String(),
            ]
        ]);
    }
}
