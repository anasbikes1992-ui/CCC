<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Exceptions\IllegalStatusTransitionException;
use App\Exceptions\PricingNotConfiguredException;
use App\Exceptions\QrTokenInvalidException;
use App\Exceptions\TripFullException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Translates every framework / app exception into the standard API envelope.
 * Wired up in bootstrap/app.php → withExceptions().
 */
class ApiExceptionRenderer
{
    public function render(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => ApiResponse::error(
                'VALIDATION_ERROR',
                $e->getMessage(),
                $e->errors(),
                422,
            ),

            $e instanceof AuthenticationException => ApiResponse::error(
                'UNAUTHENTICATED',
                'Authentication required',
                [],
                401,
            ),

            $e instanceof AuthorizationException => ApiResponse::error(
                'FORBIDDEN',
                $e->getMessage() ?: 'Forbidden',
                [],
                403,
            ),

            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => ApiResponse::error(
                'NOT_FOUND',
                'Resource not found',
                [],
                404,
            ),

            $e instanceof ThrottleRequestsException => ApiResponse::error(
                'RATE_LIMITED',
                'Too many requests',
                [],
                429,
            ),

            $e instanceof QrTokenInvalidException => ApiResponse::error(
                'INVALID_QR_TOKEN',
                $e->getMessage(),
                ['reason' => $e->reason],
                401,
            ),

            $e instanceof IllegalStatusTransitionException => ApiResponse::error(
                'ILLEGAL_STATUS_TRANSITION',
                $e->getMessage(),
                [
                    'parcel_id' => $e->parcelId,
                    'from' => $e->from->value,
                    'to' => $e->to->value,
                    'allowed' => $e->allowedNext(),
                ],
                422,
            ),

            $e instanceof TripFullException => ApiResponse::error(
                'TRIP_FULL',
                $e->getMessage(),
                ['route_code' => $e->routeCode],
                409,
            ),

            $e instanceof PricingNotConfiguredException => ApiResponse::error(
                'SERVER_ERROR',
                'Pricing matrix incomplete — contact support',
                [],
                500,
            ),

            default => ApiResponse::error(
                'SERVER_ERROR',
                config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
                config('app.debug') ? ['trace' => collect($e->getTrace())->take(5)->toArray()] : [],
                500,
            ),
        };
    }
}
