<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Driver::with('user');

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->whereHas('user', fn ($q) => $q->where('full_name', 'ilike', "%{$s}%")->orWhere('phone', 'ilike', "%{$s}%"));
        }

        $limit   = min((int) $request->input('limit', 50), 100);
        $drivers = $query->orderBy('created_at', 'desc')->paginate($limit);

        return ApiResponse::success([
            'drivers' => $drivers->items(),
            'meta'    => ['total' => $drivers->total(), 'page' => $drivers->currentPage(), 'last_page' => $drivers->lastPage()],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $driver = Driver::with(['user'])->findOrFail($id);
        return ApiResponse::success(['driver' => $driver]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'             => 'required_without:user|uuid|exists:users,id',
            'user'                => 'required_without:user_id|array',
            'user.full_name'      => 'required_with:user|string|max:255',
            'user.phone'          => 'required_with:user|string|unique:users,phone',
            'user.email'          => 'nullable|email|unique:users,email',
            'user.password'       => 'required_with:user|string|min:6',
            'license_number'      => 'required|string|max:50',
            'license_expires_at'  => 'required|date|after:today',
            'is_active'           => 'boolean',
        ]);

        if (isset($data['user'])) {
            $userData = $data['user'];
            $user = User::create([
                'full_name'      => $userData['full_name'],
                'phone'          => $userData['phone'],
                'email'          => $userData['email'] ?? null,
                'password_hash'  => Hash::make($userData['password']),
                'role'           => 'driver',
                'preferred_lang' => 'en',
            ]);
            $userId = $user->id;
        } else {
            $userId = $data['user_id'];
        }

        $driver = Driver::create([
            'user_id'            => $userId,
            'license_number'     => $data['license_number'],
            'license_expires_at' => $data['license_expires_at'],
            'is_active'          => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(['driver' => $driver->load('user')], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $driver = Driver::findOrFail($id);

        $data = $request->validate([
            'license_number'     => 'sometimes|string|max:50',
            'license_expires_at' => 'sometimes|date',
            'is_active'          => 'sometimes|boolean',
        ]);

        $driver->update($data);

        return ApiResponse::success(['driver' => $driver->fresh()->load('user')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();
        return ApiResponse::success(['message' => 'Driver deleted successfully.']);
    }
}
