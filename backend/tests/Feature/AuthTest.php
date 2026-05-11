<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a new customer and returns a token', function () {
    $r = $this->postJson('/api/v1/auth/register', [
        'full_name' => 'Test Customer',
        'phone' => '+94770000999',
        'email' => 'tc@example.com',
        'password' => 'secret-pass',
        'preferred_lang' => 'en',
    ]);

    $r->assertCreated();
    $r->assertJsonPath('data.user.phone', '+94770000999');
    expect($r->json('data.token'))->toBeString()->not->toBeEmpty();
});

it('rejects login with wrong password', function () {
    $this->postJson('/api/v1/auth/register', [
        'full_name' => 'X',
        'phone' => '+94770000888',
        'password' => 'rightpass',
    ])->assertCreated();

    $this->postJson('/api/v1/auth/login', [
        'phone' => '+94770000888',
        'password' => 'wrongpass',
    ])->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHENTICATED');
});

it('returns 422 with VALIDATION_ERROR on bad phone format', function () {
    $r = $this->postJson('/api/v1/auth/register', [
        'full_name' => 'X',
        'phone' => 'not-a-phone',
        'password' => 'secret-pass',
    ]);
    $r->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
});
