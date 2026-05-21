<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    private const ALLOWED_ROLES = [
        'admin_super',
        'ops_admin',
        'finance_admin',
        'support_admin',
        'super_admin',
    ];

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, self::ALLOWED_ROLES, true)) {
            return ApiResponse::error('FORBIDDEN', 'Admin access required.', [], 403);
        }

        return $next($request);
    }
}