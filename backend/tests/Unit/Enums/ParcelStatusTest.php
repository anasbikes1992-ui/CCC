<?php

declare(strict_types=1);

use App\Enums\ParcelStatus;

/**
 * Exhaustive transition-matrix test for ADR 0002.
 * Asserts every legal cell allows transition AND every illegal cell rejects it.
 */

dataset('legal_transitions', [
    [ParcelStatus::BOOKED, ParcelStatus::LABEL_PRINTED],
    [ParcelStatus::BOOKED, ParcelStatus::PICKED_UP],
    [ParcelStatus::BOOKED, ParcelStatus::CANCELLED],
    [ParcelStatus::LABEL_PRINTED, ParcelStatus::PICKED_UP],
    [ParcelStatus::LABEL_PRINTED, ParcelStatus::CANCELLED],
    [ParcelStatus::PICKED_UP, ParcelStatus::RECEIVED_AT_ORIGIN_HUB],
    [ParcelStatus::PICKED_UP, ParcelStatus::CANCELLED],
    [ParcelStatus::RECEIVED_AT_ORIGIN_HUB, ParcelStatus::LOADED_ON_LORRY],
    [ParcelStatus::RECEIVED_AT_ORIGIN_HUB, ParcelStatus::CANCELLED],
    [ParcelStatus::LOADED_ON_LORRY, ParcelStatus::IN_TRANSIT],
    [ParcelStatus::LOADED_ON_LORRY, ParcelStatus::RECEIVED_AT_ORIGIN_HUB],
    [ParcelStatus::IN_TRANSIT, ParcelStatus::ARRIVED_AT_DESTINATION_HUB],
    [ParcelStatus::ARRIVED_AT_DESTINATION_HUB, ParcelStatus::OUT_FOR_DELIVERY],
    [ParcelStatus::OUT_FOR_DELIVERY, ParcelStatus::DELIVERED],
    [ParcelStatus::OUT_FOR_DELIVERY, ParcelStatus::DELIVERY_FAILED],
    [ParcelStatus::DELIVERY_FAILED, ParcelStatus::OUT_FOR_DELIVERY],
    [ParcelStatus::DELIVERY_FAILED, ParcelStatus::RETURNED_TO_ORIGIN],
]);

it('allows every legal transition', function (ParcelStatus $from, ParcelStatus $to) {
    expect($from->canTransitionTo($to))->toBeTrue("{$from->value} → {$to->value} should be legal");
})->with('legal_transitions');

it('rejects every illegal transition (full cross product)', function () {
    $legal = collect([
        [ParcelStatus::BOOKED, ParcelStatus::LABEL_PRINTED],
        [ParcelStatus::BOOKED, ParcelStatus::PICKED_UP],
        [ParcelStatus::BOOKED, ParcelStatus::CANCELLED],
        [ParcelStatus::LABEL_PRINTED, ParcelStatus::PICKED_UP],
        [ParcelStatus::LABEL_PRINTED, ParcelStatus::CANCELLED],
        [ParcelStatus::PICKED_UP, ParcelStatus::RECEIVED_AT_ORIGIN_HUB],
        [ParcelStatus::PICKED_UP, ParcelStatus::CANCELLED],
        [ParcelStatus::RECEIVED_AT_ORIGIN_HUB, ParcelStatus::LOADED_ON_LORRY],
        [ParcelStatus::RECEIVED_AT_ORIGIN_HUB, ParcelStatus::CANCELLED],
        [ParcelStatus::LOADED_ON_LORRY, ParcelStatus::IN_TRANSIT],
        [ParcelStatus::LOADED_ON_LORRY, ParcelStatus::RECEIVED_AT_ORIGIN_HUB],
        [ParcelStatus::IN_TRANSIT, ParcelStatus::ARRIVED_AT_DESTINATION_HUB],
        [ParcelStatus::ARRIVED_AT_DESTINATION_HUB, ParcelStatus::OUT_FOR_DELIVERY],
        [ParcelStatus::OUT_FOR_DELIVERY, ParcelStatus::DELIVERED],
        [ParcelStatus::OUT_FOR_DELIVERY, ParcelStatus::DELIVERY_FAILED],
        [ParcelStatus::DELIVERY_FAILED, ParcelStatus::OUT_FOR_DELIVERY],
        [ParcelStatus::DELIVERY_FAILED, ParcelStatus::RETURNED_TO_ORIGIN],
    ])->map(fn ($p) => "{$p[0]->value}>{$p[1]->value}")->all();

    foreach (ParcelStatus::cases() as $from) {
        foreach (ParcelStatus::cases() as $to) {
            $key = "{$from->value}>{$to->value}";
            if (in_array($key, $legal, true)) {
                continue;
            }
            expect($from->canTransitionTo($to))->toBeFalse("{$key} must be illegal");
        }
    }
});

it('marks DELIVERED, CANCELLED, RETURNED_TO_ORIGIN as terminal', function () {
    expect(ParcelStatus::DELIVERED->isTerminal())->toBeTrue();
    expect(ParcelStatus::CANCELLED->isTerminal())->toBeTrue();
    expect(ParcelStatus::RETURNED_TO_ORIGIN->isTerminal())->toBeTrue();
    expect(ParcelStatus::BOOKED->isTerminal())->toBeFalse();
    expect(ParcelStatus::IN_TRANSIT->isTerminal())->toBeFalse();
});

it('terminal states allow no transitions', function () {
    foreach ([ParcelStatus::DELIVERED, ParcelStatus::CANCELLED, ParcelStatus::RETURNED_TO_ORIGIN] as $t) {
        expect($t->allowedNext())->toBe([]);
    }
});
