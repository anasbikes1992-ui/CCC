<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_code' => ['required', 'string', 'max:255', 'unique:trips,trip_code'],
            'route_id' => ['required', 'uuid', 'exists:routes,id'],
            'lorry_id' => ['required', 'uuid', 'exists:lorries,id'],
            'driver_id' => ['required', 'uuid', 'exists:drivers,id'],
            'scheduled_departure' => ['required', 'date', 'after:now'],
            'scheduled_arrival' => ['required', 'date', 'after:scheduled_departure'],
            'capacity_units_max' => ['required', 'integer', 'min:1'],
            'bookings_close_at' => ['nullable', 'date', 'before:scheduled_departure'],
        ];
    }
}
