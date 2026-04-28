<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        app()->instance('csp_nonce', $nonce);

        $response = $next($request);

        // Mode lebih ketat:
        // script inline diizinkan melalui nonce, sementara inline event handler
        // pada view utama sudah dipindahkan ke addEventListener.
        $csp = implode('; ', [
            "default-src 'self'",

            "script-src 'self' 'nonce-{$nonce}'",

            // Style inline masih dipakai luas pada atribut style dan blok style di Blade
            "style-src 'self' 'unsafe-inline'",

            "font-src 'self'",

            // Gambar: domain sendiri + data URI (untuk preview base64 di JS)
            "img-src 'self' data:",

            // Koneksi fetch/XHR: hanya domain sendiri (untuk AJAX verifikasi)
            "connect-src 'self'",

            // Tidak izinkan embed frame dari luar
            "frame-ancestors 'none'",

            // Tidak izinkan object/embed/applet
            "object-src 'none'",

            // Base URI hanya domain sendiri
            "base-uri 'self'",

            // Form hanya boleh submit ke domain sendiri
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        // Header keamanan tambahan
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
