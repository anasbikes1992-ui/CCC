<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\Parcel;
use App\Services\SmsService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 60, 300];

    public function __construct(
        private string $parcelId,
        private string $message,
        private string $phone,
        private string $templateName
    ) {}

    public function handle(SmsService $smsService): void
    {
        $parcel = Parcel::find($this->parcelId);

        if (!$parcel) {
            Log::warning("SendSmsNotification: Parcel {$this->parcelId} not found");
            return;
        }

        $log = NotificationLog::create([
            'parcel_id' => $parcel->id,
            'channel' => 'sms',
            'template' => $this->templateName . '_fallback',
            'recipient' => $this->phone,
            'status' => 'queued',
            'payload' => ['message' => $this->message],
        ]);

        try {
            $response = $smsService->send($this->phone, $this->message);

            $log->update([
                'status' => 'sent',
                'provider_msg_id' => $response['data']['message_id'] ?? null,
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
