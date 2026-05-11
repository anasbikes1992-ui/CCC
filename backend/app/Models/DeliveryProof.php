<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class DeliveryProof extends Model
{
    use HasUuid;

    protected $fillable = [
        'parcel_id', 'receiver_name_input',
        'receiver_nic_encrypted', 'receiver_nic_last4',
        'signature_url', 'signature_size_bytes',
        'photo_url', 'photo_size_bytes',
        'delivered_at', 'delivered_by_user_id', 'device_id',
        'delivery_lat', 'delivery_lng',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'signature_size_bytes' => 'integer',
            'photo_size_bytes' => 'integer',
            'delivery_lat' => 'float',
            'delivery_lng' => 'float',
        ];
    }

    /**
     * Decrypt NIC on read; never expose unmasked outside this scope.
     * API responses must use receiver_nic_last4 instead.
     */
    public function getReceiverNicAttribute(): ?string
    {
        if (! $this->receiver_nic_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->receiver_nic_encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }
}
