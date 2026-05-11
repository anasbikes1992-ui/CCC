<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use App\Enums\ParcelStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parcel extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'parcel_number', 'qr_token',
        'customer_id', 'trip_id',
        'route_id', 'package_size_id',
        'weight_kg', 'length_cm', 'width_cm', 'height_cm',
        'pickup_type', 'pickup_address', 'pickup_hub_id',
        'drop_type', 'drop_address', 'drop_hub_id',
        'receiver_name', 'receiver_phone',
        'declared_value_lkr', 'cod_amount_lkr',
        'is_express', 'has_insurance',
        'base_price_lkr', 'surcharges_lkr', 'discount_lkr', 'total_price_lkr',
        'capacity_units',
        'status', 'status_changed_at',
        'notes',
        'pickup_lat', 'pickup_lng', 'drop_lat', 'drop_lng',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'length_cm' => 'integer',
            'width_cm' => 'integer',
            'height_cm' => 'integer',
            'declared_value_lkr' => 'decimal:2',
            'cod_amount_lkr' => 'decimal:2',
            'is_express' => 'boolean',
            'has_insurance' => 'boolean',
            'base_price_lkr' => 'decimal:2',
            'surcharges_lkr' => 'decimal:2',
            'discount_lkr' => 'decimal:2',
            'total_price_lkr' => 'decimal:2',
            'capacity_units' => 'integer',
            'status' => ParcelStatus::class,
            'status_changed_at' => 'datetime',
            'pickup_lat' => 'float',
            'pickup_lng' => 'float',
            'drop_lat' => 'float',
            'drop_lng' => 'float',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function packageSize(): BelongsTo
    {
        return $this->belongsTo(PackageSize::class);
    }

    public function pickupHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'pickup_hub_id');
    }

    public function dropHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'drop_hub_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ParcelEvent::class)->orderBy('occurred_at');
    }

    public function deliveryProof(): HasOne
    {
        return $this->hasOne(DeliveryProof::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
