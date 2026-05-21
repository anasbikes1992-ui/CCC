<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Hub;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Hub::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'ilike', "%{$s}%")->orWhere('city', 'ilike', "%{$s}%");
            });
        }

        $hubs = $query->withCount(['routes as origin_routes_count' => fn ($q) => $q->where('origin_hub_id', \DB::raw('hubs.id'))])
            ->orderBy('city')
            ->get();

        return ApiResponse::success(['hubs' => $hubs]);
    }

    public function show(string $id): JsonResponse
    {
        $hub = Hub::findOrFail($id);
        return ApiResponse::success(['hub' => $hub]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'code'      => 'required|string|max:10|unique:hubs,code',
            'city'      => 'required|string|max:100',
            'district'  => 'nullable|string|max:100',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'hub_lat'   => 'nullable|numeric',
            'hub_lng'   => 'nullable|numeric',
            'is_active' => 'boolean',
        ]);

        $hub = Hub::create($data);
        return ApiResponse::success(['hub' => $hub], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $hub  = Hub::findOrFail($id);

        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'code'      => "sometimes|string|max:10|unique:hubs,code,{$id}",
            'city'      => 'sometimes|string|max:100',
            'district'  => 'sometimes|nullable|string|max:100',
            'address'   => 'sometimes|nullable|string',
            'phone'     => 'sometimes|nullable|string|max:20',
            'hub_lat'   => 'sometimes|nullable|numeric',
            'hub_lng'   => 'sometimes|nullable|numeric',
            'is_active' => 'sometimes|boolean',
        ]);

        $hub->update($data);
        return ApiResponse::success(['hub' => $hub->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $hub = Hub::findOrFail($id);
        $hub->delete();
        return ApiResponse::success(['message' => 'Hub deleted successfully.']);
    }
}
