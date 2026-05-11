<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Customer\ParcelController as CustomerParcelController;
use App\Http\Controllers\Api\V1\Driver\ScanController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\PublicTrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Customer
        Route::name('customer.')->prefix('customer')->group(function () {
            Route::get('/parcels', [CustomerParcelController::class, 'index']);
            Route::post('/parcels', [CustomerParcelController::class, 'store'])->name('parcels.store');
            Route::get('/parcels/{id}', [CustomerParcelController::class, 'show']);
        });

        // Driver
        Route::prefix('driver')->group(function () {
            Route::post('/parcels/{idOrNumber}/scan', [ScanController::class, 'scan']);
        });
    });

    // Public tracking — no auth, no rate limit beyond global throttle.
    Route::get('/public/parcels/{parcelNumber}/track', PublicTrackingController::class);
});
