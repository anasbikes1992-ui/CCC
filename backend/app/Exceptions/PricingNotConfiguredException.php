<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class PricingNotConfiguredException extends RuntimeException
{
    public function __construct(string $routeCode, string $sizeCode)
    {
        parent::__construct(
            "Pricing not configured for route={$routeCode} size={$sizeCode}"
        );
    }
}
