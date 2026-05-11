<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lorry extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'lorries';

    protected $fillable = [
        'registration_number', 'type', 'max_weight_kg',
        'max_capacity_units', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_weight_kg' => 'decimal:2',
            'max_capacity_units' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
