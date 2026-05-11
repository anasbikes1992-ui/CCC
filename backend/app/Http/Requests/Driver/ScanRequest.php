<?php

declare(strict_types=1);

namespace App\Http\Requests\Driver;

use App\Enums\ParcelEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScanRequest extends FormRequest
{
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
            'qr_token' => ['nullable', 'string'],
            'event_type' => ['required', 'string', Rule::in($allowed)],
            'geo.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'device_id' => ['nullable', 'string', 'max:80'],
            'occurred_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
