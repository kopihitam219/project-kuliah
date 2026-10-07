<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Memasang penyesuaian tampilan HP (public/css/mobile.css & public/js/mobile.js)
 * ke semua halaman HTML, plus meta viewport bila halaman belum punya.
 */
class MobileResponsive
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        $type = (string) $response->headers->get('Content-Type', '');

        if ($type !== '' && stripos($type, 'text/html') === false) {
            return $response;
        }

        $html = $response->getContent();

        if (! is_string($html) || $html === '' || str_contains($html, 'data-mobile-kit')) {
            return $response;
        }

        $headEnd = stripos($html, '</head>');

        if ($headEnd === false) {
            return $response;
        }

        $inject = '';

        if (! preg_match('/<meta[^>]+name=["\']viewport["\']/i', $html)) {
            $inject .= '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
        }

        $cssVersion = @filemtime(public_path('css/mobile.css')) ?: 1;
        $jsVersion  = @filemtime(public_path('js/mobile.js')) ?: 1;

        $inject .= '    <link rel="stylesheet" href="' . e(asset('css/mobile.css')) . '?v=' . $cssVersion . '" data-mobile-kit>' . "\n";
        $inject .= '    <script src="' . e(asset('js/mobile.js')) . '?v=' . $jsVersion . '" defer data-mobile-kit></script>' . "\n";

        $response->setContent(substr_replace($html, $inject, $headEnd, 0));
        $response->headers->remove('Content-Length');

        return $response;
    }
}
