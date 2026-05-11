<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $db = 'down';
        try {
            DB::connection()->select('SELECT 1');
            $db = 'up';
        } catch (\Throwable) {
            // intentionally swallow — surfaced via 'down'
        }

        $redis = 'down';
        try {
            if (Redis::connection()->ping()) {
                $redis = 'up';
            }
        } catch (\Throwable) {
            // ditto
        }

        return ApiResponse::success([
            'ok' => true,
            'version' => config('app.version', '0.1.0'),
            'db' => $db,
            'redis' => $redis,
            'time' => now()->toIso8601String(),
        ]);
    }
}
