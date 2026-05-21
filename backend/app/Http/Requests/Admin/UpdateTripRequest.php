<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tripId = $this->route('trip');

        return [
            'trip_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('trips')->ignore($tripId)],
            'route_id' => ['sometimes', 'required', 'uuid', 'exists:routes,id'],
            'lorry_id' => ['sometimes', 'required', 'uuid', 'exists:lorries,id'],
            'driver_id' => ['sometimes', 'required', 'uuid', 'exists:drivers,id'],
            'scheduled_departure' => ['sometimes', 'required', 'date'],
            'scheduled_arrival' => ['sometimes', 'required', 'date', 'after:scheduled_departure'],
            'capacity_units_max' => ['sometimes', 'required', 'integer', 'min:1'],
            'bookings_close_at' => ['nullable', 'date', 'before:scheduled_departure'],
            'status' => ['sometimes', 'required', 'string', Rule::in(array_column(\App\Enums\TripStatus::cases(), 'value'))],
        ];
    }
}
