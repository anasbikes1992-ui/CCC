<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class TripFullException extends RuntimeException
{
    public function __construct(public readonly string $routeCode)
    {
        parent::__construct("No trip on route {$this->routeCode} has capacity in the next 7 days");
    }
}
