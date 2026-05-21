<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SmsService
{
    /**
     * Send an SMS using Notify.lk API.
     */
    public function send(string $phone, string $message): array
    {
        $userId = config('services.notify_lk.user_id');
        $apiKey = config('services.notify_lk.api_key');
        $senderId = config('services.notify_lk.sender_id');

        if (!$userId || !$apiKey) {
            Log::warning('SmsService: Notify.lk credentials are not configured.');
            return ['status' => 'skipped', 'reason' => 'missing_credentials'];
        }

        // Format phone number to Sri Lankan format, e.g., 9477...
        $phone = $this->formatPhoneNumber($phone);

        try {
            $response = Http::post('https://app.notify.lk/api/v1/send', [
                'user_id' => $userId,
                'api_key' => $apiKey,
                'sender_id' => $senderId,
                'to' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('SmsService error response: ' . $response->body());
            throw new Exception("Notify.lk API Error: " . $response->status());
        } catch (Exception $e) {
            Log::error('SmsService exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Basic phone number formatter to ensure it starts with 94.
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If it starts with 07, replace 0 with 94
        if (str_starts_with($phone, '07') && strlen($phone) === 10) {
            return '94' . substr($phone, 1);
        }
        
        // If it doesn't have 94 and is 9 digits (e.g. 771234567), prepend 94
        if (strlen($phone) === 9) {
            return '94' . $phone;
        }

        return $phone;
    }
}
