<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\PricingMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PricingMatrix::with(['route.originHub', 'route.destinationHub', 'packageSize']);

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->input('route_id'));
        }

        $pricing = $query->orderBy('route_id')->orderBy('package_size_id')->get();

        return ApiResponse::success(['pricing' => $pricing]);
    }

    public function show(string $id): JsonResponse
    {
        $pm = PricingMatrix::with(['route', 'packageSize'])->findOrFail($id);
        return ApiResponse::success(['pricing' => $pm]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'route_id'        => 'required|uuid|exists:routes,id',
            'package_size_id' => 'required|uuid|exists:package_sizes,id',
            'base_price_lkr'  => 'required|numeric|min:0',
            'surcharges'      => 'nullable|array',
            'effective_from'  => 'nullable|date',
            'effective_until' => 'nullable|date|after:effective_from',
        ]);

        $pm = PricingMatrix::create($data);
        $pm->load(['route', 'packageSize']);

        return ApiResponse::success(['pricing' => $pm], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $pm   = PricingMatrix::findOrFail($id);

        $data = $request->validate([
            'base_price_lkr'  => 'sometimes|numeric|min:0',
            'surcharges'      => 'sometimes|nullable|array',
            'effective_from'  => 'sometimes|nullable|date',
            'effective_until' => 'sometimes|nullable|date',
        ]);

        $pm->update($data);

        return ApiResponse::success(['pricing' => $pm->fresh()->load(['route', 'packageSize'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $pm = PricingMatrix::findOrFail($id);
        $pm->delete();
        return ApiResponse::success(['message' => 'Pricing entry deleted successfully.']);
    }
}
