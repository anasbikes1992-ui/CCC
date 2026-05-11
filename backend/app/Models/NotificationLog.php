<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasUuid;

    protected $table = 'notifications_log';

    public $timestamps = false;

    protected $fillable = [
        'parcel_id', 'user_id', 'channel', 'template', 'recipient',
        'status', 'provider_msg_id', 'error_code', 'error_message',
        'payload', 'sent_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
