<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrackingResource;
use App\Http\Responses\ApiResponse;
use App\Models\Parcel;
use App\Services\ParcelNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PublicTrackingController extends Controller
{
    public function __invoke(string $parcelNumber, ParcelNumberService $numbers): JsonResponse
    {
        if (! $numbers->isValid($parcelNumber)) {
            throw new NotFoundHttpException('Parcel not found');
        }

        $payload = Cache::remember(
            "track:{$parcelNumber}",
            30,
            function () use ($parcelNumber) {
                $parcel = Parcel::query()
                    ->where('parcel_number', $parcelNumber)
                    ->with(['route', 'trip', 'events'])
                    ->first();

                if (! $parcel) {
                    return null;
                }

                return (new TrackingResource($parcel))->resolve(request());
            }
        );

        if ($payload === null) {
            throw new NotFoundHttpException('Parcel not found');
        }

        return ApiResponse::success($payload)
            ->header('Cache-Control', 'public, max-age=30');
    }
}
