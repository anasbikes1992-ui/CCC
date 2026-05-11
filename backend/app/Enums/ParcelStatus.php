<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Parcel lifecycle states + the legal-transition matrix.
 *
 * Single source of truth — see docs/adr/0002-parcel-state-machine.md.
 * Every status change in the system MUST go through ScanService::record(),
 * which calls canTransitionTo() before persisting.
 */
enum ParcelStatus: string
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

    /**
     * Authoritative legal-transition matrix.
     * Anything not listed here is illegal and must be rejected by ScanService.
     *
     * @return array<int, self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::BOOKED => [self::LABEL_PRINTED, self::PICKED_UP, self::CANCELLED],
            self::LABEL_PRINTED => [self::PICKED_UP, self::CANCELLED],
            self::PICKED_UP => [self::RECEIVED_AT_ORIGIN_HUB, self::CANCELLED],
            self::RECEIVED_AT_ORIGIN_HUB => [self::LOADED_ON_LORRY, self::CANCELLED],
            self::LOADED_ON_LORRY => [self::IN_TRANSIT, self::RECEIVED_AT_ORIGIN_HUB],
            self::IN_TRANSIT => [self::ARRIVED_AT_DESTINATION_HUB],
            self::ARRIVED_AT_DESTINATION_HUB => [self::OUT_FOR_DELIVERY],
            self::OUT_FOR_DELIVERY => [self::DELIVERED, self::DELIVERY_FAILED],
            self::DELIVERY_FAILED => [self::OUT_FOR_DELIVERY, self::RETURNED_TO_ORIGIN],
            self::DELIVERED, self::CANCELLED, self::RETURNED_TO_ORIGIN => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), strict: true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::DELIVERED, self::CANCELLED, self::RETURNED_TO_ORIGIN], strict: true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
