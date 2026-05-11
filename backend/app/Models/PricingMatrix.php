<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingMatrix extends Model
{
    use HasUuid;

    protected $table = 'pricing_matrix';

    protected $fillable = [
        'route_id', 'package_size_id', 'base_price_lkr',
        'surcharges', 'effective_from', 'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'base_price_lkr' => 'decimal:2',
            'surcharges' => 'array',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function packageSize(): BelongsTo
    {
        return $this->belongsTo(PackageSize::class);
    }
}
