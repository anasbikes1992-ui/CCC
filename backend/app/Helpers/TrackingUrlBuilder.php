<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Parcel;

class TrackingUrlBuilder
{
    public static function for(Parcel $parcel): string
    {
        $baseUrl = config('app.tracking_url', 'https://track.cargo.lk');
        
        return rtrim($baseUrl, '/') . '/' . $parcel->parcel_number;
    }
}
