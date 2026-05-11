<?php

declare(strict_types=1);

it('returns ok at /api/health', function () {
    $r = $this->getJson('/api/health');

    $r->assertOk();
    $r->assertJsonPath('success', true);
    $r->assertJsonPath('data.ok', true);
});
