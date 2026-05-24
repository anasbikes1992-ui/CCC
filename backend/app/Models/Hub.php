<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hub extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'address', 'city', 'district', 'phone', 'is_active',
        'hub_lat', 'hub_lng',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hub_lat' => 'float',
            'hub_lng' => 'float',
        ];
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class, 'origin_hub_id');
    }
}
