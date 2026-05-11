<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class BookParcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'route_code' => ['required', 'string', 'exists:routes,code'],
            'package_size_code' => ['required', 'string', 'exists:package_sizes,code'],

            'weight_kg' => ['required', 'numeric', 'min:0.01'],
            'length_cm' => ['nullable', 'integer', 'min:1'],
            'width_cm' => ['nullable', 'integer', 'min:1'],
            'height_cm' => ['nullable', 'integer', 'min:1'],

            'pickup_type' => ['required', 'in:hub,doorstep'],
            'pickup_address' => ['required_if:pickup_type,doorstep', 'nullable', 'string'],
            'pickup_hub_code' => ['required_if:pickup_type,hub', 'nullable', 'string', 'exists:hubs,code'],
            'pickup_geo.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_geo.lng' => ['nullable', 'numeric', 'between:-180,180'],

            'drop_type' => ['required', 'in:hub,doorstep'],
            'drop_address' => ['required_if:drop_type,doorstep', 'nullable', 'string'],
            'drop_hub_code' => ['required_if:drop_type,hub', 'nullable', 'string', 'exists:hubs,code'],
            'drop_geo.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'drop_geo.lng' => ['nullable', 'numeric', 'between:-180,180'],

            'receiver_name' => ['required', 'string', 'max:150'],
            'receiver_phone' => ['required', 'string', 'regex:/^\+\d{10,15}$/'],

            'declared_value_lkr' => ['nullable', 'numeric', 'min:0'],
            'cod_amount_lkr' => ['nullable', 'numeric', 'min:0'],

            'is_express' => ['nullable', 'boolean'],
            'has_insurance' => ['nullable', 'boolean'],

            'payment_method' => ['required', 'in:cod,bank_transfer'],
        ];
    }
}
