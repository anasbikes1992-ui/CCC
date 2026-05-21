<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Customer\DisputeController as CustomerDisputeController;
use App\Http\Controllers\Api\V1\Customer\ParcelController as CustomerParcelController;
use App\Http\Controllers\Api\V1\Customer\SupportTicketController as CustomerTicketController;
use App\Http\Controllers\Api\V1\Driver\TripController;
use App\Http\Controllers\Api\V1\Driver\ScanController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Hub\DashboardController as HubDashboardController;
use App\Http\Controllers\Api\V1\Hub\InventoryController as HubInventoryController;
use App\Http\Controllers\Api\V1\Hub\ScanController as HubScanController;
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
            Route::get('/parcels/{id}/label.pdf', [CustomerParcelController::class, 'label']);

            // Disputes
            Route::get('/disputes', [CustomerDisputeController::class, 'index']);
            Route::get('/disputes/{id}', [CustomerDisputeController::class, 'show']);
            Route::post('/parcels/{parcelId}/dispute', [CustomerDisputeController::class, 'store']);

            // Support tickets
            Route::get('/tickets', [CustomerTicketController::class, 'index']);
            Route::post('/tickets', [CustomerTicketController::class, 'store']);
            Route::get('/tickets/{id}', [CustomerTicketController::class, 'show']);
            Route::post('/tickets/{id}/reply', [CustomerTicketController::class, 'reply']);
        });

        // Driver
        Route::prefix('driver')->middleware(['throttle:60,1', 'ensure.driver.profile'])->group(function () {
            Route::get('/trips', [TripController::class, 'index']);
            Route::get('/trips/{id}/parcels', [TripController::class, 'parcels']);
            Route::post('/parcels/{idOrNumber}/scan', [ScanController::class, 'scan']);
            Route::post('/parcels/{idOrNumber}/deliver', [ScanController::class, 'deliver']);
        });

        // Admin
        Route::prefix('admin')->name('admin.')->middleware(['throttle:60,1', 'ensure.admin'])->group(function () {
            // Dashboard (God's View)
            Route::get('dashboard/stats', [\App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'index']);

            // Trips
            Route::apiResource('trips', \App\Http\Controllers\Api\V1\Admin\TripController::class);

            // Parcels
            Route::apiResource('parcels', \App\Http\Controllers\Api\V1\Admin\ParcelController::class)->except(['store']);

            // Users
            Route::apiResource('users', \App\Http\Controllers\Api\V1\Admin\UserController::class);

            // Drivers
            Route::apiResource('drivers', \App\Http\Controllers\Api\V1\Admin\DriverController::class);

            // Hubs
            Route::apiResource('hubs', \App\Http\Controllers\Api\V1\Admin\HubController::class);

            // Routes
            Route::apiResource('routes', \App\Http\Controllers\Api\V1\Admin\RouteController::class);

            // Lorries
            Route::apiResource('lorries', \App\Http\Controllers\Api\V1\Admin\LorryController::class);

            // Pricing Matrix
            Route::apiResource('pricing', \App\Http\Controllers\Api\V1\Admin\PricingController::class);

            // Notification Logs (read-only)
            Route::get('notifications-log', [\App\Http\Controllers\Api\V1\Admin\NotificationLogController::class, 'index']);
            Route::get('notifications-log/{id}', [\App\Http\Controllers\Api\V1\Admin\NotificationLogController::class, 'show']);

            // Disputes
            Route::get('disputes', [\App\Http\Controllers\Api\V1\Admin\DisputeController::class, 'index']);
            Route::get('disputes/{id}', [\App\Http\Controllers\Api\V1\Admin\DisputeController::class, 'show']);
            Route::patch('disputes/{id}', [\App\Http\Controllers\Api\V1\Admin\DisputeController::class, 'update']);

            // Support Tickets
            Route::get('tickets', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'index']);
            Route::get('tickets/{id}', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'show']);
            Route::patch('tickets/{id}', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'update']);
            Route::post('tickets/{id}/reply', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'reply']);
        });

        // Hub Staff Console
        Route::prefix('hub')->name('hub.')->middleware(['throttle:120,1', 'ensure.hub.staff'])->group(function () {
            Route::get('/dashboard', [HubDashboardController::class, 'index']);
            Route::get('/inventory', [HubInventoryController::class, 'index']);
            Route::get('/inbound', [HubInventoryController::class, 'inbound']);
            Route::get('/outbound', [HubInventoryController::class, 'outbound']);
            Route::get('/trips/{tripId}/manifest', [HubInventoryController::class, 'manifest']);
            Route::get('/parcels/{idOrNumber}', [HubScanController::class, 'lookup']);
            Route::post('/parcels/{idOrNumber}/scan', [HubScanController::class, 'scan']);
        });
    });

    // Webhooks
    Route::get('/webhooks/whatsapp', [\App\Http\Controllers\Api\V1\WebhookController::class, 'verifyWhatsapp']);
    Route::post('/webhooks/whatsapp', [\App\Http\Controllers\Api\V1\WebhookController::class, 'handleWhatsapp']);

    // Public tracking stays unauthenticated but uses a stricter endpoint-level throttle.
    Route::get('/public/parcels/{parcelNumber}/track', PublicTrackingController::class)
        ->middleware('throttle:10,1');
});
