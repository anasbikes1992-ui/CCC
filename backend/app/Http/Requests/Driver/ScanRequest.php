<?php

declare(strict_types=1);

namespace App\Http\Requests\Driver;

use App\Enums\ParcelEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'scan_mode' => strtolower((string) $this->header('X-Scan-Mode', 'qr')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $allowed = collect(ParcelEventType::cases())
            ->map(fn (ParcelEventType $c) => $c->value)
            ->reject(fn (string $v) => $v === 'ILLEGAL_TRANSITION_ATTEMPT' || $v === 'BOOKED')
            ->values()
            ->all();

        return [
            'scan_mode' => ['required', Rule::in(['qr', 'manual'])],
            'qr_token' => ['nullable', 'string', 'required_if:scan_mode,qr'],
            'event_type' => ['required', 'string', Rule::in($allowed)],
            'geo.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'device_id' => ['nullable', 'string', 'max:80'],
            'occurred_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array', 'max:20'],
            'metadata.*' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
