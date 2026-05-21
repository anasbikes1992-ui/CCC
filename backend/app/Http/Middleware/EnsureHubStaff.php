<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHubStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'UNAUTHENTICATED', 'message' => 'Authentication required.'],
            ], 401);
        }

        // Accept either hub_staff or hub_manager roles (and super_admin for testing)
        if (! in_array($user->role, ['hub_staff', 'hub_manager', 'super_admin'], true)) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'FORBIDDEN', 'message' => 'Hub staff access required.'],
            ], 403);
        }

        if ($user->role !== 'super_admin' && ! $user->hub_staff?->hub_id) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'NO_HUB_ASSIGNED', 'message' => 'No hub assignment found for this account.'],
            ], 403);
        }

        return $next($request);
    }
}
