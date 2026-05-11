<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Event types written to the parcel_events audit table.
 * Mirrors ParcelStatus + adds an audit-only ILLEGAL_TRANSITION_ATTEMPT case.
 */
enum ParcelEventType: string
{
    case BOOKED = 'BOOKED';
    case LABEL_PRINTED = 'LABEL_PRINTED';
    case PICKED_UP = 'PICKED_UP';
    case RECEIVED_AT_ORIGIN_HUB = 'RECEIVED_AT_ORIGIN_HUB';
    case LOADED_ON_LORRY = 'LOADED_ON_LORRY';
    case IN_TRANSIT = 'IN_TRANSIT';
    case ARRIVED_AT_DESTINATION_HUB = 'ARRIVED_AT_DESTINATION_HUB';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case DELIVERY_FAILED = 'DELIVERY_FAILED';
    case CANCELLED = 'CANCELLED';
    case RETURNED_TO_ORIGIN = 'RETURNED_TO_ORIGIN';
    case ILLEGAL_TRANSITION_ATTEMPT = 'ILLEGAL_TRANSITION_ATTEMPT';
}
