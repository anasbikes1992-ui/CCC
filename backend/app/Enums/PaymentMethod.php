<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case COD = 'cod';
    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card'; // Reserved for v1.1; not used in MVP.
}
