<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Parcel;
use App\Models\ParcelEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParcelStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Parcel $parcel,
        public ParcelEvent $event
    ) {}
}
