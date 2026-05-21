<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('forbids non-admin users from admin endpoints', function () {
    $customer = User::create([
        'full_name' => 'Customer User',
        'phone' => '+94770001111',
        'password_hash' => Hash::make('secret'),
        'role' => 'customer',
    ]);

    $token = $customer->createToken('api')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/admin/dashboard/stats')
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'FORBIDDEN');
});

it('allows admin users to access admin endpoints', function () {
    $admin = User::create([
        'full_name' => 'Admin User',
        'phone' => '+94770002222',
        'password_hash' => Hash::make('secret'),
        'role' => 'admin_super',
    ]);

    $token = $admin->createToken('api')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/admin/dashboard/stats')
        ->assertOk()
        ->assertJsonPath('success', true);
});