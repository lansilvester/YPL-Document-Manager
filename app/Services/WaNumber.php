<?php

namespace App\Services;

/**
 * Normalisasi nomor HP Indonesia ke format +62….
 * Menerima: 0896…, +62896…, 62896…, 896…, (0896) 123-4567, dst.
 */
class WaNumber
{
    public static function normalize(string $input): ?string
    {
        $input = trim($input);
        $hasPlus = str_starts_with($input, '+');
        $digits = preg_replace('/\D/', '', $input) ?? '';
        if ($digits === '') {
            return null;
        }

        if ($hasPlus) {
            $norm = '+'.$digits;
        } elseif (str_starts_with($digits, '62')) {
            $norm = '+'.$digits;
        } elseif (str_starts_with($digits, '0')) {
            $norm = '+62'.substr($digits, 1);
        } else {
            $norm = '+62'.$digits;
        }

        return preg_match('/^\+62\d{8,13}$/', $norm) ? $norm : null;
    }
}
