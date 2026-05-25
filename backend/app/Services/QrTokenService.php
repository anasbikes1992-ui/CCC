<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\QrTokenInvalidException;
use Carbon\Carbon;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use UnexpectedValueException;

/**
 * Signs and verifies JWTs encoded inside parcel QR codes.
 * Spec: docs/adr/0003-parcel-number-and-qr-token.md.
 */
class QrTokenService
{
    private const ALG = 'HS256';
    private const VERSION = 1;

    public function __construct(
        private readonly ?string $secret = null,
        private readonly int $ttlDays = 30,
    ) {}

    private function key(): string
    {
        $secret = $this->secret ?? config('services.qr.secret') ?? config('app.key');
        if (! $secret) {
            throw new \RuntimeException('QR_TOKEN_SECRET is not configured');
        }

        return $secret;
    }

    private function ttl(): int
    {
        return (int) ($this->ttlDays ?: config('services.qr.ttl_days', 30));
    }

    public function sign(string $parcelUuid, string $parcelNumber, ?Carbon $now = null): string
    {
        $now = $now ?? Carbon::now();
        $payload = [
            'iss' => 'ccc',
            'sub' => $parcelUuid,
            'pno' => $parcelNumber,
            'iat' => $now->timestamp,
            'exp' => $now->copy()->addDays($this->ttl())->timestamp,
            'ver' => self::VERSION,
        ];

        return JWT::encode($payload, $this->key(), self::ALG);
    }

    /**
     * @return array{parcel_uuid: string, parcel_number: string}
     */
    public function verify(string $jwt): array
    {
        try {
            $decoded = (array) JWT::decode($jwt, new Key($this->key(), self::ALG));
        } catch (ExpiredException) {
            throw new QrTokenInvalidException('expired');
        } catch (SignatureInvalidException) {
            throw new QrTokenInvalidException('signature_mismatch');
        } catch (UnexpectedValueException $e) {
            throw new QrTokenInvalidException('malformed', $e->getMessage());
        }

        if (($decoded['iss'] ?? null) !== 'ccc') {
            throw new QrTokenInvalidException('malformed', 'bad issuer');
        }

        return [
            'parcel_uuid' => (string) ($decoded['sub'] ?? ''),
            'parcel_number' => (string) ($decoded['pno'] ?? ''),
        ];
    }
}
