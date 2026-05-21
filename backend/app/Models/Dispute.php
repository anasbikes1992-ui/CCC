<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    use HasUuids;

    protected $fillable = [
        'parcel_id',
        'raised_by_user_id',
        'type',
        'description',
        'status',
        'resolved_by',
        'resolution',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'under_review']);
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'rejected', 'closed']);
    }
}
