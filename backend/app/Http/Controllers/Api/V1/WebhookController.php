<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsappService
    ) {}

    /**
     * Webhook verification for Meta.
     */
    public function verifyWhatsapp(Request $request): Response
    {
        $mode = $request->query('hub_mode', '');
        $token = $request->query('hub_verify_token', '');
        $challenge = $request->query('hub_challenge', '');

        $verifiedChallenge = $this->whatsappService->verifyWebhook($mode, $token, $challenge);

        if ($verifiedChallenge !== null) {
            return response($verifiedChallenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Webhook payload handler for Meta.
     */
    public function handleWhatsapp(Request $request): Response
    {
        $payload = $request->all();

        $this->whatsappService->handleWebhook(
            $payload,
            (string) $request->getContent(),
            $request->header('X-Hub-Signature-256')
        );

        return response('EVENT_RECEIVED', 200);
    }
}
