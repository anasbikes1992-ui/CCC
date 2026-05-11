<?php

declare(strict_types=1);

namespace App\Enums;

enum TripStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case LOADING = 'LOADING';
    case IN_TRANSIT = 'IN_TRANSIT';
    case ARRIVED = 'ARRIVED';
    case UNLOADING = 'UNLOADING';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}
