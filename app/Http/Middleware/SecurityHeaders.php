<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict browser-side hardening for every page and API response:
 * nothing is cached, no third party can load anything, no referrer is sent, and the site cannot be framed.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = implode('; ', [
            "default-src 'none'",
            "script-src 'self'",
            "style-src 'self'",
            "img-src 'self' data:",
            "media-src 'self'",
            "font-src 'self'",
            "connect-src 'self'",
            "manifest-src 'none'",
            "base-uri 'none'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        $headers = [
            'Content-Security-Policy' => $csp,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, private',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()',
        ];

        // Public marketing / explainer pages are meant to be found. Everything else (chat API, operator console)
        // stays noindex. A controller opts a page in with $request->attributes->set('seo', [...]).
        $seo = $request->attributes->get('seo');
        if ($seo && ! empty($seo['index'])) {
            $headers['X-Robots-Tag'] = 'index, follow, max-image-preview:large, max-snippet:-1';
        }
        if ($seo && ! empty($seo['cache'])) {
            $headers['Cache-Control'] = 'public, max-age=600';
            unset($headers['Pragma']);
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains');
        }

        return $response;
    }
}
