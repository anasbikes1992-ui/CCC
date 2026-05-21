<?php

declare(strict_types=1);

namespace App\Helpers;

class WaMeBuilder
{
    public static function contact(string $phone, string $prefill = ''): string
    {
        $url = 'https://wa.me/' . ltrim($phone, '+');
        
        if (!empty($prefill)) {
            $url .= '?text=' . urlencode($prefill);
        }
        
        return $url;
    }
}
