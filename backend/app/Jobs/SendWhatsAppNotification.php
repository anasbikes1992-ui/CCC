<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Parcel;
use App\Models\NotificationLog;
use App\Services\WhatsAppService;
use App\Helpers\TrackingUrlBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 60, 300]; // exponential backoff

    public function __construct(
        private string $parcelId,
        private string $templateName,
        private ?string $recipientType = null
    ) {}

    public function handle(WhatsAppService $whatsappService): void
    {
        $parcel = Parcel::with(['route.originHub', 'route.destinationHub', 'trip.driver'])->find($this->parcelId);

        if (!$parcel) {
            Log::warning("SendWhatsAppNotification: Parcel {$this->parcelId} not found");
            return;
        }

        $templateConfig = config("whatsapp_templates.templates.{$this->templateName}");

        if (!$templateConfig) {
            Log::warning("SendWhatsAppNotification: Template config for {$this->templateName} not found");
            return;
        }

        $recipients = $this->recipientType ? [$this->recipientType] : $templateConfig['recipients'];

        foreach ($recipients as $type) {
            $phone = null;
            $name = null;
            
            if ($type === 'sender') {
                $phone = $parcel->sender_phone;
                $name = $parcel->sender_name;
            } elseif ($type === 'receiver') {
                $phone = $parcel->receiver_phone;
                $name = $parcel->receiver_name;
            }

            if (!$phone) {
                continue;
            }

            $params = $this->buildParams($this->templateName, $parcel, $name);

            // Log attempt
            $log = NotificationLog::create([
                'parcel_id' => $parcel->id,
                'channel' => 'whatsapp',
                'template' => $this->templateName,
                'recipient' => $phone,
                'status' => 'queued',
                'payload' => $params,
            ]);

            try {
                $response = $whatsappService->sendTemplate($phone, $this->templateName, $templateConfig['language'], $params);
                
                $log->update([
                    'status' => 'sent',
                    'provider_msg_id' => $response['messages'][0]['id'] ?? ($response['fake_success'] ? 'fake_id' : null),
                    'sent_at' => now(),
                ]);
            } catch (Exception $e) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    private function buildParams(string $templateName, Parcel $parcel, string $recipientName): array
    {
        $trackingUrl = TrackingUrlBuilder::for($parcel);

        return match ($templateName) {
            'booking_confirmed' => [
                $recipientName,
                $parcel->parcel_number,
                $parcel->route?->originHub?->name ?? 'Origin Hub',
                $parcel->route?->destinationHub?->name ?? 'Destination Hub',
                $parcel->trip?->scheduled_departure_at?->format('D M j, g:i A') ?? 'TBA',
                $parcel->trip?->scheduled_arrival_at?->format('D M j, g:i A') ?? 'TBA',
                $trackingUrl,
            ],
            'parcel_picked_up' => [
                $parcel->parcel_number,
                $parcel->route?->originHub?->name ?? 'Origin',
                $parcel->route?->destinationHub?->name ?? 'Destination',
                $parcel->picked_up_at?->format('g:i A') ?? 'just now',
                $trackingUrl,
            ],
            'arrived_at_origin_hub' => [
                $parcel->parcel_number,
                $parcel->route?->originHub?->name ?? 'Origin',
                $trackingUrl,
            ],
            'in_transit' => [
                $parcel->parcel_number,
                $parcel->route?->originHub?->name ?? 'Origin',
                $parcel->trip?->actual_departure_at?->format('g:i A') ?? 'recently',
                $parcel->route?->destinationHub?->name ?? 'Destination',
                $parcel->trip?->scheduled_arrival_at?->format('g:i A') ?? 'soon',
                $trackingUrl,
            ],
            'arrived_at_destination_hub' => [
                $parcel->parcel_number,
                $parcel->route?->destinationHub?->name ?? 'Destination',
                $parcel->route?->destinationHub?->name ?? 'Destination Hub',
                $trackingUrl,
            ],
            'out_for_delivery' => [
                $parcel->parcel_number,
                $parcel->trip?->driver?->user?->name ?? 'Driver',
                $parcel->trip?->driver?->user?->phone ?? 'Driver Phone',
                'soon', // could calculate based on ETA
                $trackingUrl,
            ],
            'delivered' => [
                $parcel->parcel_number,
                $parcel->receiver_name,
                '******' . substr($parcel->deliveryProof?->receiver_nic_encrypted ?? 'xxxxxx', -4),
                $parcel->delivered_at?->format('g:i A') ?? 'just now',
            ],
            'delivery_failed' => [
                $parcel->parcel_number,
                $this->getFailureReason($parcel),
                'Tomorrow',
            ],
            'cancelled' => [
                $parcel->parcel_number,
                $parcel->total_price,
                '3-5',
                $parcel->id,
            ],
            default => [],
        };
    }

    private function getFailureReason(Parcel $parcel): string
    {
        $event = \App\Models\ParcelEvent::where('parcel_id', $parcel->id)
            ->where('event_type', \App\Enums\ParcelEventType::DELIVERY_FAILED)
            ->latest('occurred_at')
            ->first();

        return $event?->metadata['reason'] ?? 'Receiver unavailable';
    }

    public function failed(Throwable $exception): void
    {
        Log::error("SendWhatsAppNotification final failure for parcel {$this->parcelId}: " . $exception->getMessage());
        
        // SMS Fallback
        $parcel = Parcel::find($this->parcelId);
        if (!$parcel) return;

        $templateConfig = config("whatsapp_templates.templates.{$this->templateName}");
        if (!$templateConfig) return;

        $recipients = $this->recipientType ? [$this->recipientType] : $templateConfig['recipients'];
        $trackingUrl = TrackingUrlBuilder::for($parcel);

        foreach ($recipients as $type) {
            $phone = $type === 'sender' ? $parcel->sender_phone : $parcel->receiver_phone;
            if (!$phone) continue;

            $smsMessage = match ($this->templateName) {
                'booking_confirmed' => "CCC: Your parcel {$parcel->parcel_number} is booked. Track: {$trackingUrl}",
                'in_transit' => "CCC: Parcel {$parcel->parcel_number} is on the way. Track: {$trackingUrl}",
                'out_for_delivery' => "CCC: Parcel {$parcel->parcel_number} is out for delivery. Track: {$trackingUrl}",
                'delivered' => "CCC: Parcel {$parcel->parcel_number} was delivered successfully.",
                'delivery_failed' => "CCC: Delivery failed for parcel {$parcel->parcel_number} (Reason: {$this->getFailureReason($parcel)}). We will retry.",
                default => null,
            };

            if ($smsMessage) {
                \App\Jobs\SendSmsNotification::dispatch(
                    $parcel->id, 
                    $smsMessage, 
                    $phone, 
                    $this->templateName
                );
            }
        }
    }
}
