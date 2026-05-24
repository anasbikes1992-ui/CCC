<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Lorry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LorryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $lorries = Lorry::orderBy('registration_number')->get();
        return ApiResponse::success(['lorries' => $lorries]);
    }

    public function show(string $id): JsonResponse
    {
        $lorry = Lorry::findOrFail($id);
        return ApiResponse::success(['lorry' => $lorry]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registration_number' => 'required|string|max:20|unique:lorries,registration_number',
            'type'                => 'required|in:small,medium,large',
            'max_capacity_units'  => 'required|integer|min:1',
            'max_weight_kg'       => 'required|numeric|min:1',
            'is_active'           => 'boolean',
        ]);

        $lorry = Lorry::create($data);
        return ApiResponse::success(['lorry' => $lorry], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $lorry = Lorry::findOrFail($id);

        $data = $request->validate([
            'registration_number' => "sometimes|string|max:20|unique:lorries,registration_number,{$id}",
            'type'                => 'sometimes|in:small,medium,large',
            'max_capacity_units'  => 'sometimes|integer|min:1',
            'max_weight_kg'       => 'sometimes|numeric|min:1',
            'is_active'           => 'sometimes|boolean',
        ]);

        $lorry->update($data);
        return ApiResponse::success(['lorry' => $lorry->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $lorry = Lorry::findOrFail($id);
        $lorry->delete();
        return ApiResponse::success(['message' => 'Lorry deleted successfully.']);
    }
}
