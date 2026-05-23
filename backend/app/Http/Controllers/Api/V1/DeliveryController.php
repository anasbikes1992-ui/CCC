<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Services\OTPService;
use App\Services\ScanService;
use App\Enums\ParcelEventType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    public function __construct(
        private OTPService $otpService,
        private ScanService $scanService,
    ) {}

    /**
     * Verify OTP provided by receiver before delivery
     * POST /api/v1/delivery/verify-otp
     */
    public function verifyOTP(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parcel_id' => 'required|uuid|exists:parcels,id',
            'otp' => 'required|string|size:6',
        ]);

        $parcel = Parcel::findOrFail($validated['parcel_id']);

        // Check if parcel is in correct status
        if ($parcel->status !== 'ARRIVED_AT_DESTINATION_HUB' && $parcel->status !== 'OUT_FOR_DELIVERY') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATUS',
                    'message' => 'Parcel is not ready for pickup verification',
                ],
            ], 400);
        }

        // Verify OTP
        $isValid = $this->otpService->verifyDeliveryOTP($parcel, $validated['otp']);

        if (!$isValid) {
            $attemptsLeft = 3 - $parcel->delivery_otp_attempts;
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_OTP',
                    'message' => 'Invalid OTP code',
                    'details' => [
                        'attempts_left' => max(0, $attemptsLeft),
                    ],
                ],
            ], 400);
        }

        Log::info('Delivery OTP verified successfully', [
            'parcel_id' => $parcel->id,
            'parcel_number' => $parcel->parcel_number,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'verified' => true,
                'parcel_id' => $parcel->id,
                'parcel_number' => $parcel->parcel_number,
                'message' => 'OTP verified successfully. Proceed with delivery.',
            ],
        ]);
    }

    /**
     * Regenerate OTP (e.g., if expired)
     * POST /api/v1/delivery/regenerate-otp
     */
    public function regenerateOTP(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parcel_id' => 'required|uuid|exists:parcels,id',
        ]);

        $parcel = Parcel::findOrFail($validated['parcel_id']);

        // Check if parcel is in correct status
        if ($parcel->status !== 'ARRIVED_AT_DESTINATION_HUB' && $parcel->status !== 'OUT_FOR_DELIVERY') {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATUS',
                    'message' => 'Cannot regenerate OTP for this parcel status',
                ],
            ], 400);
        }

        $otp = $this->otpService->regenerateDeliveryOTP($parcel);

        // Send new OTP via WhatsApp
        $trackingUrl = config('app.frontend_url') . '/track/' . $parcel->parcel_number;
        $pickupLocation = $parcel->drop_type === 'hub' 
            ? ($parcel->dropHub->name ?? 'Destination Hub')
            : 'Doorstep delivery';

        $params = [
            $parcel->receiver_name,
            $parcel->customer->name,
            $parcel->parcel_number,
            $parcel->packageSize->name ?? $parcel->size,
            number_format($parcel->weight_kg, 2) . ' kg',
            $parcel->route->origin_hub_name ?? 'Origin',
            $parcel->route->destination_hub_name ?? 'Destination',
            $pickupLocation,
            $otp,
            $trackingUrl,
        ];

        \App\Jobs\SendWhatsAppNotification::dispatch(
            $parcel->receiver_phone,
            'ready_for_pickup',
            'en',
            $params
        );

        Log::info('Delivery OTP regenerated', [
            'parcel_id' => $parcel->id,
            'parcel_number' => $parcel->parcel_number,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'New OTP has been sent to the receiver',
                'expires_in_minutes' => 30,
            ],
        ]);
    }

    /**
     * Complete delivery with all verification
     * POST /api/v1/delivery/complete
     */
    public function completeDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parcel_id' => 'required|uuid|exists:parcels,id',
            'otp' => 'required|string|size:6',
            'receiver_nic' => 'required|string|min:10|max:12',
            'signature_base64' => 'required|string',
            'photo_base64' => 'nullable|string',
            'delivery_notes' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $parcel = Parcel::findOrFail($validated['parcel_id']);

        // 1. Verify OTP first
        if (!$parcel->delivery_otp_verified_at) {
            $isValid = $this->otpService->verifyDeliveryOTP($parcel, $validated['otp']);
            
            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_OTP',
                        'message' => 'Invalid or expired OTP',
                    ],
                ], 400);
            }
        }

        // 2. Create delivery proof
        $deliveryProof = $parcel->deliveryProof()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'receiver_nic_encrypted' => encrypt($validated['receiver_nic']),
            'signature_image_url' => $this->uploadSignature($validated['signature_base64'], $parcel->parcel_number),
            'delivery_photo_url' => isset($validated['photo_base64']) 
                ? $this->uploadPhoto($validated['photo_base64'], $parcel->parcel_number)
                : null,
            'delivery_notes' => $validated['delivery_notes'] ?? null,
            'delivered_at' => now(),
            'delivered_by' => auth()->id(),
        ]);

        // 3. Update parcel status to DELIVERED
        $geo = isset($validated['latitude'], $validated['longitude'])
            ? ['lat' => $validated['latitude'], 'lng' => $validated['longitude']]
            : null;

        $this->scanService->record(
            parcel: $parcel,
            eventType: ParcelEventType::DELIVERED,
            actor: auth()->user(),
            scanMode: 'manual',
            deviceId: $request->header('X-Device-ID'),
            geo: $geo,
            metadata: [
                'delivery_proof_id' => $deliveryProof->id,
                'otp_verified' => true,
            ],
        );

        Log::info('Delivery completed successfully', [
            'parcel_id' => $parcel->id,
            'parcel_number' => $parcel->parcel_number,
            'delivered_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Delivery completed successfully',
                'parcel_number' => $parcel->parcel_number,
                'delivered_at' => $deliveryProof->delivered_at->toIso8601String(),
            ],
        ]);
    }

    private function uploadSignature(string $base64, string $parcelNumber): string
    {
        // Decode base64
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        
        // Generate filename
        $filename = "signatures/{$parcelNumber}_" . now()->timestamp . '.png';
        
        // Upload to Supabase storage
        \Storage::disk('supabase')->put($filename, $imageData);
        
        return \Storage::disk('supabase')->url($filename);
    }

    private function uploadPhoto(string $base64, string $parcelNumber): string
    {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        $filename = "delivery_photos/{$parcelNumber}_" . now()->timestamp . '.jpg';
        \Storage::disk('supabase')->put($filename, $imageData);
        return \Storage::disk('supabase')->url($filename);
    }
}
