<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Sumber nonce Content-Security-Policy.
 *
 * Satu nonce dibuat per request dan dipakai ulang oleh header CSP
 * (SecurityHeaders) maupun atribut `nonce=` pada setiap tag <script>/<style>
 * inline. Memungkinkan CSP tanpa 'unsafe-inline' untuk script.
 */
class Csp
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        return self::$nonce ??= Str::random(24);
    }
}
