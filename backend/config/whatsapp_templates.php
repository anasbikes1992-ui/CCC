<?php

return [
    'templates' => [
        'booking_confirmed' => [
            'name' => 'booking_confirmed',
            'recipients' => ['sender'],
            'language' => 'en',
            // params: 1: Sender Name, 2: Parcel Number, 3: Origin, 4: Destination, 5: Trip info, 6: ETA, 7: Tracking URL
        ],
        'parcel_picked_up' => [
            'name' => 'parcel_picked_up',
            'recipients' => ['sender', 'receiver'],
            'language' => 'en',
            // params: 1: Parcel Number, 2: Origin, 3: Destination, 4: Pickup time, 5: Tracking URL
        ],
        'arrived_at_origin_hub' => [
            'name' => 'arrived_at_origin_hub',
            'recipients' => ['sender', 'receiver'],
            'language' => 'en',
            // params: 1: Parcel Number, 2: Origin Hub, 3: Tracking URL
        ],
        'in_transit' => [
            'name' => 'in_transit',
            'recipients' => ['receiver'],
            'language' => 'en',
            // params: 1: Parcel Number, 2: Origin Hub, 3: Departure time, 4: Destination Hub, 5: ETA, 6: Tracking URL
        ],
        'arrived_at_destination_hub' => [
            'name' => 'arrived_at_destination_hub',
            'recipients' => ['receiver'],
            'language' => 'en',
            // params: 1: Parcel Number, 2: Destination Hub, 3: Collection Point, 4: Tracking URL
        ],
        'ready_for_pickup' => [
            'name' => 'ready_for_pickup',
            'recipients' => ['receiver'],
            'language' => 'en',
            // params: 1: Receiver Name, 2: Sender Name, 3: Parcel Number, 4: Size, 5: Weight, 6: Origin, 7: Destination, 8: Pickup Location, 9: OTP, 10: Tracking URL
        ],
        'out_for_delivery' => [
            'name' => 'out_for_delivery',
            'recipients' => ['receiver'],
            'language' => 'en',
            // params: 1: Parcel Number, 2: Driver Name, 3: Driver Phone, 4: ETA, 5: Tracking URL
        ],
        'delivered' => [
            'name' => 'delivered',
            'recipients' => ['sender', 'receiver'],
            'language' => 'en',
            // params: 1: Parcel Number, 2: Receiver Name, 3: Receiver NIC, 4: Delivery Time
        ],
        'delivery_failed' => [
            'name' => 'delivery_failed',
            'recipients' => ['sender'],
            'language' => 'en',
            // params: 1: Parcel Number, 2: Reason, 3: Next Attempt Time
        ],
        'cancelled' => [
            'name' => 'cancelled',
            'recipients' => ['sender'],
            'language' => 'en',
            // params: 1: Parcel Number, 2: Refund Amount, 3: Refund Days, 4: Reference
        ],
    ],

    // Map Parcel Event Types to Template Names
    'event_mapping' => [
        \App\Enums\ParcelEventType::BOOKED->value => 'booking_confirmed',
        \App\Enums\ParcelEventType::PICKED_UP->value => 'parcel_picked_up',
        \App\Enums\ParcelEventType::RECEIVED_AT_ORIGIN_HUB->value => 'arrived_at_origin_hub',
        \App\Enums\ParcelEventType::IN_TRANSIT->value => 'in_transit',
        \App\Enums\ParcelEventType::ARRIVED_AT_DESTINATION_HUB->value => 'arrived_at_destination_hub',
        \App\Enums\ParcelEventType::OUT_FOR_DELIVERY->value => 'out_for_delivery',
        \App\Enums\ParcelEventType::DELIVERED->value => 'delivered',
        \App\Enums\ParcelEventType::DELIVERY_FAILED->value => 'delivery_failed',
        \App\Enums\ParcelEventType::CANCELLED->value => 'cancelled',
    ],
];
