<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriverProfile
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $driver = $request->user()?->driver;
        if (! $driver || ! $driver->is_active) {
            return ApiResponse::error('FORBIDDEN', 'Driver profile required', [], 403);
        }

        $request->attributes->set('driverProfile', $driver);

        return $next($request);
    }
}
