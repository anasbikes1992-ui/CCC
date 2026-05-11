<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'routes';

    protected $fillable = [
        'code', 'origin_hub_id', 'destination_hub_id', 'display_name',
        'estimated_duration_minutes', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'estimated_duration_minutes' => 'integer'];
    }

    public function originHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'origin_hub_id');
    }

    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'destination_hub_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function pricingMatrix(): HasMany
    {
        return $this->hasMany(PricingMatrix::class);
    }
}
