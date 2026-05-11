<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\ParcelEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParcelEvent extends Model
{
    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'parcel_id', 'event_type', 'from_status', 'to_status',
        'actor_user_id', 'actor_role', 'hub_id', 'trip_id',
        'scan_mode', 'device_id', 'metadata', 'occurred_at',
        'geo_lat', 'geo_lng',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => ParcelEventType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'geo_lat' => 'float',
            'geo_lng' => 'float',
        ];
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
