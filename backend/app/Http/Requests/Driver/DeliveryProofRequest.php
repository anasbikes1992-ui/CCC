<?php

declare(strict_types=1);

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'receiver_name' => ['required', 'string', 'max:150'],
            'receiver_nic' => ['required', 'string', 'max:20'],
            'signature' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg', 'min:5', 'max:5120'], // min 5KB, max 5MB
            'photo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:5120'], // max 5MB
            'geo_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'device_id' => ['nullable', 'string', 'max:80'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
