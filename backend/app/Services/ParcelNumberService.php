<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates parcel numbers in the format CCC-YYYYMMDD-NNNNNN-X.
 * Spec: docs/adr/0003-parcel-number-and-qr-token.md.
 */
class ParcelNumberService
{
    /** Generate the next parcel number for today (Asia/Colombo). */
    public function generate(?Carbon $now = null): string
    {
        $now = $now ?? Carbon::now('Asia/Colombo');
        $date = $now->format('Y-m-d');
        $datePart = $now->format('Ymd');

        $seq = DB::transaction(function () use ($date) {
            // Atomic upsert + increment. ON CONFLICT lets us avoid an explicit SELECT FOR UPDATE
            // because the unique key is the date column.
            $row = DB::selectOne(
                'INSERT INTO parcel_number_counters (date, last_seq)
                 VALUES (?, 1)
                 ON CONFLICT (date) DO UPDATE SET last_seq = parcel_number_counters.last_seq + 1
                 RETURNING last_seq',
                [$date]
            );

            return (int) $row->last_seq;
        });

        $seqPart = str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        $check = $this->luhnCheckDigit($datePart.$seqPart);

        return sprintf('CCC-%s-%s-%d', $datePart, $seqPart, $check);
    }

    /** Verify the check digit on a candidate parcel number (used by manual entry). */
    public function isValid(string $parcelNumber): bool
    {
        if (! preg_match('/^CCC-(\d{8})-(\d{6})-(\d)$/', $parcelNumber, $m)) {
            return false;
        }

        return $this->luhnCheckDigit($m[1].$m[2]) === (int) $m[3];
    }

    /** Luhn mod 10 check digit. */
    public function luhnCheckDigit(string $digits): int
    {
        $sum = 0;
        $double = true; // start doubling from rightmost digit
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $d = (int) $digits[$i];
            if ($double) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
            $double = ! $double;
        }

        return (10 - ($sum % 10)) % 10;
    }
}
