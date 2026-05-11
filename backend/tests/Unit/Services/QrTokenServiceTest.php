<?php

declare(strict_types=1);

use App\Exceptions\QrTokenInvalidException;
use App\Services\QrTokenService;

it('signs and verifies a token round trip', function () {
    $svc = new QrTokenService(secret: 'test-secret', ttlDays: 30);
    $jwt = $svc->sign('aaaa-bbbb', 'CCC-20260601-000042-7');

    $payload = $svc->verify($jwt);

    expect($payload['parcel_uuid'])->toBe('aaaa-bbbb');
    expect($payload['parcel_number'])->toBe('CCC-20260601-000042-7');
});

it('rejects a tampered signature', function () {
    $svc = new QrTokenService(secret: 'test-secret', ttlDays: 30);
    $jwt = $svc->sign('aaaa-bbbb', 'CCC-20260601-000042-7');

    // Flip a character in the signature segment.
    $parts = explode('.', $jwt);
    $parts[2] = strrev($parts[2]);
    $tampered = implode('.', $parts);

    expect(fn () => $svc->verify($tampered))
        ->toThrow(QrTokenInvalidException::class);
});

it('rejects a token signed with a different secret', function () {
    $a = new QrTokenService(secret: 'secret-a', ttlDays: 30);
    $b = new QrTokenService(secret: 'secret-b', ttlDays: 30);
    $jwt = $a->sign('aaaa', 'CCC-20260601-000001-3');

    expect(fn () => $b->verify($jwt))->toThrow(QrTokenInvalidException::class);
});

it('rejects an expired token', function () {
    $svc = new QrTokenService(secret: 'test-secret', ttlDays: 30);

    $past = \Carbon\Carbon::now()->subDays(31);
    $jwt = $svc->sign('aaaa', 'CCC-20260601-000001-3', $past);

    expect(fn () => $svc->verify($jwt))->toThrow(QrTokenInvalidException::class);
});
