<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Xavfsizlik sarlavhalari: MIME sniffing, clickjacking, CSP (asosiy), referrer va brauzer ruxsatlari.
 * HSTS faqat production + HTTPS'da (lokalda brauzer "yopishib" qolmasligi uchun).
 */
class SecurityHeaders
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()', false);
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups', false);
        // Yengil CSP: plaginlar (<object>), <base> almashtirish va begona saytga iframe qilish taqiqlanadi.
        // script-src qo'yilmagan — Vite dev server, JSON-LD va to'lov sahifalari buzilmasligi uchun.
        $headers->set('Content-Security-Policy', implode('; ', array_filter([
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            $request->isSecure() && app()->isProduction() ? 'upgrade-insecure-requests' : null,
        ])), false);

        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        if ($request->is('admin', 'admin/*', 'cabinet', 'cabinet/*', 'settings/*')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow', false);
        }

        return $response;
    }
}
