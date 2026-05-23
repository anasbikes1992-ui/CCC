<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withEvents(discover: [
        __DIR__.'/../app/Listeners',
    ], listen: [
        \App\Events\ParcelStatusChanged::class => [
            \App\Listeners\SendParcelNotifications::class,
        ],
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // API auth is bearer-token based across web/mobile clients.
        // Keep middleware stack stateless (no Sanctum cookie/CSRF stateful pipeline).
        $middleware->throttleApi();

        $middleware->alias([
            'ensure.admin' => \App\Http\Middleware\EnsureAdmin::class,
            'ensure.driver.profile' => \App\Http\Middleware\EnsureDriverProfile::class,
            'ensure.hub.staff'      => \App\Http\Middleware\EnsureHubStaff::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Force JSON envelope on all API errors so frontends never get HTML pages.
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return app(\App\Http\Responses\ApiExceptionRenderer::class)->render($e);
        });
    })->create();
