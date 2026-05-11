<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\TripStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'trip_code', 'route_id', 'lorry_id', 'driver_id',
        'scheduled_departure', 'scheduled_arrival',
        'actual_departure', 'actual_arrival',
        'status', 'capacity_units_max', 'capacity_units_used',
        'bookings_close_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_departure' => 'datetime',
            'scheduled_arrival' => 'datetime',
            'actual_departure' => 'datetime',
            'actual_arrival' => 'datetime',
            'bookings_close_at' => 'datetime',
            'status' => TripStatus::class,
            'capacity_units_max' => 'integer',
            'capacity_units_used' => 'integer',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function lorry(): BelongsTo
    {
        return $this->belongsTo(Lorry::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class);
    }

    public function capacityRemaining(): int
    {
        return $this->capacity_units_max - $this->capacity_units_used;
    }
}
