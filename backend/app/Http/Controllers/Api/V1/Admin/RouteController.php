<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $routes = Route::with(['originHub', 'destinationHub'])
            ->withCount('trips')
            ->orderBy('code')
            ->get();

        return ApiResponse::success(['routes' => $routes]);
    }

    public function show(string $id): JsonResponse
    {
        $route = Route::with(['originHub', 'destinationHub', 'pricingMatrix.packageSize'])
            ->withCount('trips')
            ->findOrFail($id);

        return ApiResponse::success(['route' => $route]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'                        => 'required|string|max:20|unique:routes,code',
            'origin_hub_id'               => 'required|uuid|exists:hubs,id',
            'destination_hub_id'          => 'required|uuid|exists:hubs,id|different:origin_hub_id',
            'display_name'                => 'required|string|max:255',
            'estimated_duration_minutes'  => 'required|integer|min:1',
            'is_active'                   => 'boolean',
        ]);

        $route = Route::create($data);
        $route->load(['originHub', 'destinationHub']);

        return ApiResponse::success(['route' => $route], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $route = Route::findOrFail($id);

        $data = $request->validate([
            'code'                        => "sometimes|string|max:20|unique:routes,code,{$id}",
            'origin_hub_id'               => 'sometimes|uuid|exists:hubs,id',
            'destination_hub_id'          => 'sometimes|uuid|exists:hubs,id',
            'display_name'                => 'sometimes|string|max:255',
            'estimated_duration_minutes'  => 'sometimes|integer|min:1',
            'is_active'                   => 'sometimes|boolean',
        ]);

        $route->update($data);
        $route->fresh()->load(['originHub', 'destinationHub']);

        return ApiResponse::success(['route' => $route]);
    }

    public function destroy(string $id): JsonResponse
    {
        $route = Route::findOrFail($id);

        if ($route->trips()->exists()) {
            return ApiResponse::error('CANNOT_DELETE_ROUTE', 'Route has associated trips. Deactivate instead.', [], 422);
        }

        $route->delete();
        return ApiResponse::success(['message' => 'Route deleted successfully.']);
    }
}
