<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ParcelStatusChanged;
use App\Jobs\SendWhatsAppNotification;
use App\Enums\ParcelEventType;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendParcelStatusNotification implements ShouldQueue
{
    public function handle(ParcelStatusChanged $event): void
    {
        // Don't send notifications for illegal transitions
        if ($event->event->event_type === ParcelEventType::ILLEGAL_TRANSITION_ATTEMPT) {
            return;
        }

        // Map the event type to the whatsapp template name.
        $templateMap = config('whatsapp_templates.event_mapping');
        $templateName = $templateMap[$event->event->event_type->value] ?? null;

        if ($templateName) {
            // Dispatch WhatsApp Notification job
            SendWhatsAppNotification::dispatch($event->parcel->id, $templateName);
        }
    }
}
