<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificationLog::with('parcel');

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('parcel_id')) {
            $query->where('parcel_id', $request->input('parcel_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $limit = min((int) $request->input('limit', 50), 100);
        $logs  = $query->orderBy('created_at', 'desc')->paginate($limit);

        return ApiResponse::success([
            'logs' => $logs->items(),
            'meta' => [
                'total'     => $logs->total(),
                'page'      => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $log = NotificationLog::with('parcel')->findOrFail($id);
        return ApiResponse::success(['log' => $log]);
    }
}
