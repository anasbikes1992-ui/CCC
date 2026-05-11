<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUuid, Notifiable, SoftDeletes;

    protected $fillable = [
        'phone',
        'email',
        'full_name',
        'password_hash',
        'role',
        'preferred_lang',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [];
    }

    /** Sanctum auth relies on getAuthPassword() returning the hash column. */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class, 'customer_id');
    }
}
