<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('driver');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'ilike', "%{$s}%")
                  ->orWhere('phone', 'ilike', "%{$s}%")
                  ->orWhere('email', 'ilike', "%{$s}%");
            });
        }

        $limit = min((int) $request->input('limit', 50), 100);
        $users = $query->withCount('parcels')->orderBy('created_at', 'desc')->paginate($limit);

        return ApiResponse::success([
            'users' => $users->items(),
            'meta'  => [
                'total'     => $users->total(),
                'page'      => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::with(['driver', 'parcels' => fn ($q) => $q->latest()->limit(20)])
            ->withCount('parcels')
            ->findOrFail($id);

        return ApiResponse::success(['user' => $user]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name'      => 'required|string|max:255',
            'phone'          => 'required|string|unique:users,phone',
            'email'          => 'nullable|email|unique:users,email',
            'password'       => 'required|string|min:6',
            'role'           => 'required|in:customer,driver,hub_staff,hub_manager,ops_admin,finance_admin,support_admin,admin_super',
            'preferred_lang' => 'nullable|in:en,si,ta',
        ]);

        $user = User::create([
            'full_name'      => $data['full_name'],
            'phone'          => $data['phone'],
            'email'          => $data['email'] ?? null,
            'password_hash'  => Hash::make($data['password']),
            'role'           => $data['role'],
            'preferred_lang' => $data['preferred_lang'] ?? 'en',
        ]);

        return ApiResponse::success(['user' => $user], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'full_name'      => 'sometimes|string|max:255',
            'phone'          => "sometimes|string|unique:users,phone,{$id}",
            'email'          => "sometimes|nullable|email|unique:users,email,{$id}",
            'password'       => 'sometimes|string|min:6',
            'role'           => 'sometimes|in:customer,driver,hub_staff,hub_manager,ops_admin,finance_admin,support_admin,admin_super',
            'preferred_lang' => 'sometimes|in:en,si,ta',
        ]);

        if (isset($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        $user->update($data);

        return ApiResponse::success(['user' => $user->fresh()]);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return ApiResponse::success(['message' => 'User deleted successfully.']);
    }
}
