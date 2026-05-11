<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class QrTokenInvalidException extends RuntimeException
{
    public string $reason;

    public function __construct(string $reason, string $message = '')
    {
        // Reasons: expired | signature_mismatch | malformed | revoked
        $this->reason = $reason;
        parent::__construct($message !== '' ? $message : "QR token invalid: {$reason}");
    }
}
