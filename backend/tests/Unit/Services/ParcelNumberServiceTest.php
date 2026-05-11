<?php

declare(strict_types=1);

use App\Services\ParcelNumberService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('produces a parcel number with valid CCC-YYYYMMDD-NNNNNN-X format', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00', 'Asia/Colombo'));
    $svc = app(ParcelNumberService::class);

    $n = $svc->generate();

    expect($n)->toMatch('/^CCC-20260601-\d{6}-\d$/');
});

it('produces sequential numbers within the same day', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00', 'Asia/Colombo'));
    $svc = app(ParcelNumberService::class);

    $first = $svc->generate();
    $second = $svc->generate();

    [, , $a, ] = explode('-', $first);
    [, , $b, ] = explode('-', $second);
    expect((int) $b)->toBe(((int) $a) + 1);
});

it('resets the sequence on a new day', function () {
    $svc = app(ParcelNumberService::class);

    Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00', 'Asia/Colombo'));
    $svc->generate();
    $svc->generate();

    Carbon::setTestNow(Carbon::parse('2026-06-02 10:00:00', 'Asia/Colombo'));
    $next = $svc->generate();

    [, , $seq, ] = explode('-', $next);
    expect((int) $seq)->toBe(1);
});

it('validates a well-formed parcel number', function () {
    $svc = app(ParcelNumberService::class);
    Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00', 'Asia/Colombo'));
    $n = $svc->generate();

    expect($svc->isValid($n))->toBeTrue();
});

it('rejects a parcel number with a tampered digit', function () {
    $svc = app(ParcelNumberService::class);
    Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00', 'Asia/Colombo'));
    $n = $svc->generate();

    $bad = substr($n, 0, -1).((int) substr($n, -1) + 1) % 10;

    expect($svc->isValid($bad))->toBeFalse();
});

it('rejects malformed strings', function () {
    $svc = app(ParcelNumberService::class);
    expect($svc->isValid('not-a-parcel'))->toBeFalse();
    expect($svc->isValid('CCC-20260601-1234-7'))->toBeFalse();
    expect($svc->isValid(''))->toBeFalse();
});
