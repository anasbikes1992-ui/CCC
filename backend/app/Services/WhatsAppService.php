<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationLog;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppService
{
    private string $phoneNumberId;
    private string $accessToken;
    private string $appSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id') ?? '';
        $this->accessToken = config('services.whatsapp.access_token') ?? '';
        $this->appSecret = config('services.whatsapp.app_secret') ?? '';
        $apiVersion = config('services.whatsapp.api_version', 'v21.0');
        $this->baseUrl = "https://graph.facebook.com/{$apiVersion}/{$this->phoneNumberId}";
    }

    public function verifyWebhook(string $mode, string $token, string $challenge): ?string
    {
        $verifyToken = (string) config('services.whatsapp.webhook_verify_token', '');

        if ($mode !== 'subscribe' || $token === '' || $challenge === '') {
            return null;
        }

        return hash_equals($verifyToken, $token) ? $challenge : null;
    }

    public function handleWebhook(array $payload, string $rawBody = '', ?string $signature = null): void
    {
        if (! $this->isValidWebhookSignature($rawBody, $signature)) {
            Log::warning('WhatsApp webhook rejected due to invalid signature.', [
                'has_signature' => ! empty($signature),
            ]);

            throw new RuntimeException('Invalid WhatsApp webhook signature.');
        }

        foreach ($this->parseWebhook($payload) as $event) {
            $messageId = $event['message_id'] ?? null;

            if (! is_string($messageId) || $messageId === '') {
                continue;
            }

            $log = NotificationLog::query()
                ->where('provider_msg_id', $messageId)
                ->latest('created_at')
                ->first();

            if (! $log) {
                Log::info('WhatsApp webhook received for unknown message id.', [
                    'message_id' => $messageId,
                    'status' => $event['status'] ?? null,
                ]);

                continue;
            }

            $existingPayload = is_array($log->payload) ? $log->payload : [];
            $error = is_array($event['error'] ?? null) ? $event['error'] : [];

            $log->update([
                'status' => (string) ($event['status'] ?? $log->status),
                'error_code' => isset($error['code']) ? (string) $error['code'] : $log->error_code,
                'error_message' => isset($error['message']) ? (string) $error['message'] : $log->error_message,
                'payload' => array_merge($existingPayload, ['webhook' => $event]),
            ]);
        }
    }

    /**
     * Send a template message via WhatsApp Cloud API.
     *
     * @param string $toPhone Phone number in E.164 format
     * @param string $templateName Name of the template to send
     * @param string $languageCode Language code (e.g. 'en')
     * @param array $params Ordered list of text parameters to replace placeholders like {{1}}
     * @return array
     * @throws Exception
     */
    public function sendTemplate(string $toPhone, string $templateName, string $languageCode = 'en', array $params = []): array
    {
        // Remove leading '+' from phone number if present as Meta API expects it without '+'
        $toPhone = ltrim($toPhone, '+');

        $components = [];
        if (!empty($params)) {
            $parameters = array_map(function ($param) {
                return [
                    'type' => 'text',
                    'text' => (string) $param,
                ];
            }, $params);

            $components[] = [
                'type' => 'body',
                'parameters' => $parameters,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
                'components' => $components,
            ],
        ];

        return $this->post('/messages', $payload);
    }

    /**
     * Send a free-form text message (only allowed within 24hr session window).
     */
    public function sendText(string $toPhone, string $body): array
    {
        $toPhone = ltrim($toPhone, '+');

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $toPhone,
            'type' => 'text',
            'text' => [
                'body' => $body,
            ],
        ];

        return $this->post('/messages', $payload);
    }

    /**
     * Parses a webhook payload from Meta to extract delivery status updates.
     */
    public function parseWebhook(array $payload): array
    {
        $events = [];

        if (!isset($payload['entry'])) {
            return $events;
        }

        foreach ($payload['entry'] as $entry) {
            if (!isset($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'messages') {
                    continue;
                }

                if (isset($change['value']['statuses'])) {
                    foreach ($change['value']['statuses'] as $status) {
                        $events[] = [
                            'message_id' => $status['id'] ?? null,
                            'status' => $status['status'] ?? null,
                            'recipient_id' => $status['recipient_id'] ?? null,
                            'timestamp' => $status['timestamp'] ?? null,
                            'pricing' => $status['pricing'] ?? null,
                            'error' => $status['errors'][0] ?? null,
                        ];
                    }
                }
            }
        }

        return $events;
    }

    private function isValidWebhookSignature(string $rawBody, ?string $signature): bool
    {
        if ($this->appSecret === '') {
            return app()->environment(['local', 'testing']);
        }

        if ($rawBody === '' || ! is_string($signature) || $signature === '') {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $rawBody, $this->appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    private function post(string $endpoint, array $payload): array
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning("WhatsAppService: Missing access token or phone number ID. Skipping send.");
            return ['fake_success' => true];
        }

        $response = Http::withToken($this->accessToken)
            ->post($this->baseUrl . $endpoint, $payload);

        if ($response->failed()) {
            $error = $response->json('error');
            $msg = $error['message'] ?? 'Unknown WhatsApp API Error';
            
            Log::error("WhatsApp API Error: {$msg}", [
                'payload' => $payload,
                'response' => $response->json()
            ]);
            
            throw new Exception("WhatsApp API Error: {$msg}");
        }

        return $response->json();
    }
}
