<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'ok' => true,
            'version' => config('app.version', '0.1.0'),
            'db' => config('database.default', 'pgsql'),
            'redis' => config('database.redis.client', 'redis'),
            'time' => now()->toIso8601String(),
        ]);
    }
}
