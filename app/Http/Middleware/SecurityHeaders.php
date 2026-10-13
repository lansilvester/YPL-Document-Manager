<?php

namespace App\Http\Middleware;

use App\Support\Csp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Host CDN yang boleh dimuat (script/style/font). Sumber lain ditolak.
     */
    private const CDN = 'https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com';

    public function handle(Request $request, Closure $next): Response
    {
        // Satu nonce per request, dipakai header CSP dan atribut nonce di view.
        $nonce = Csp::nonce();

        $response = $next($request);

        // Sembunyikan jejak teknologi (PHP menambahkan X-Powered-By lagi — matikan lewat
        // expose_php=Off di produksi agar tak muncul; di sini dihapus bila ada).
        $response->headers->remove('X-Powered-By');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // CSP ketat: script hanya dari self + CDN, dengan nonce untuk inline.
        // Tanpa 'unsafe-inline' pada script. Style tetap mengizinkan inline
        // karena view memakai banyak atribut style="" (hardening lanjutan: pindah ke kelas).
        // HSTS hanya saat HTTPS agar tidak mengunci dev lokal.
        $csp = "default-src 'self'; "
            ."base-uri 'self'; object-src 'none'; form-action 'self'; frame-ancestors 'self'; "
            ."script-src 'self' 'nonce-{$nonce}' ".self::CDN.'; '
            ."style-src 'self' 'unsafe-inline' ".self::CDN.'; '
            ."font-src 'self' data: ".self::CDN.'; '
            ."img-src 'self' data: blob:; "
            ."connect-src 'self' ".self::CDN;
        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
