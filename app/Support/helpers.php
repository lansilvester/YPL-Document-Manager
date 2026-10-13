<?php

use App\Support\Csp;

if (! function_exists('csp_nonce')) {
    /**
     * Nonce CSP request saat ini, untuk atribut nonce pada script/style inline.
     */
    function csp_nonce(): string
    {
        return Csp::nonce();
    }
}
