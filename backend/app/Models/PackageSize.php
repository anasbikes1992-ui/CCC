<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PackageSize extends Model
{
    use HasUuid;

    protected $fillable = [
        'code', 'display_name', 'max_weight_kg',
        'max_length_cm', 'max_width_cm', 'max_height_cm',
        'capacity_units', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_weight_kg' => 'decimal:2',
            'max_length_cm' => 'integer',
            'max_width_cm' => 'integer',
            'max_height_cm' => 'integer',
            'capacity_units' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
