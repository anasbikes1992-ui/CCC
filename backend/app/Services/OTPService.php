<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Parcel;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class OTPService
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 30;
    private const MAX_ATTEMPTS = 3;

    /**
     * Generate a 6-digit OTP for parcel pickup/delivery
     */
    public function generateDeliveryOTP(Parcel $parcel): string
    {
        $otp = str_pad((string) random_int(100000, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        
        $parcel->update([
            'delivery_otp' => $otp,
            'delivery_otp_generated_at' => Carbon::now(),
            'delivery_otp_verified_at' => null,
            'delivery_otp_attempts' => 0,
        ]);

        return $otp;
    }

    /**
     * Verify OTP provided by receiver to driver
     */
    public function verifyDeliveryOTP(Parcel $parcel, string $otp): bool
    {
        // Check if OTP exists
        if (empty($parcel->delivery_otp)) {
            return false;
        }

        // Check if OTP has expired
        if ($parcel->delivery_otp_generated_at && 
            $parcel->delivery_otp_generated_at->diffInMinutes(Carbon::now()) > self::OTP_EXPIRY_MINUTES) {
            return false;
        }

        // Check max attempts
        if ($parcel->delivery_otp_attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        // Increment attempts
        $parcel->increment('delivery_otp_attempts');

        // Verify OTP
        if ($parcel->delivery_otp === $otp) {
            $parcel->update([
                'delivery_otp_verified_at' => Carbon::now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if OTP is still valid
     */
    public function isOTPValid(Parcel $parcel): bool
    {
        if (empty($parcel->delivery_otp) || !$parcel->delivery_otp_generated_at) {
            return false;
        }

        if ($parcel->delivery_otp_verified_at) {
            return false; // Already used
        }

        if ($parcel->delivery_otp_attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        return $parcel->delivery_otp_generated_at->diffInMinutes(Carbon::now()) <= self::OTP_EXPIRY_MINUTES;
    }

    /**
     * Regenerate OTP (e.g., if expired or too many failed attempts)
     */
    public function regenerateDeliveryOTP(Parcel $parcel): string
    {
        return $this->generateDeliveryOTP($parcel);
    }
}
