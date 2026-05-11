<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ParcelStatus;
use RuntimeException;

class IllegalStatusTransitionException extends RuntimeException
{
    public function __construct(
        public readonly string $parcelId,
        public readonly ParcelStatus $from,
        public readonly ParcelStatus $to,
    ) {
        parent::__construct(
            "Cannot transition parcel from {$from->value} to {$to->value}"
        );
    }

    /** @return array<int, string> */
    public function allowedNext(): array
    {
        return array_map(fn ($s) => $s->value, $this->from->allowedNext());
    }
}
