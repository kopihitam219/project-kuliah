<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Tampilan HP untuk semua halaman HTML:
 *  - meta viewport (bila belum ada)
 *  - public/css/mobile.css & public/js/mobile.js
 *  - menu bawah ala aplikasi (partials/mobile-tabbar), kecuali halaman login/daftar/cetak
 */
class MobileResponsive
{
    /** Halaman yang tidak diberi menu bawah. */
    private const NO_TABBAR = [
        'login', 'register', 'password.*', 'verification.*', 'two-factor.*',
        'admin.payments.receipt', 'admin.payments.proof', 'payment.booking.proof',
    ];

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
            $inject .= '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
        }

        $cssVersion = @filemtime(public_path('css/mobile.css')) ?: 1;
        $jsVersion  = @filemtime(public_path('js/mobile.js')) ?: 1;
        $version    = substr(md5($cssVersion . '|' . @filesize(public_path('css/mobile.css'))), 0, 8);

        $inject .= '    <meta name="theme-color" content="#04100b">' . "\n";
        $inject .= '    <link rel="stylesheet" href="' . e(asset('css/mobile.css')) . '?v=' . $version . '" data-mobile-kit>' . "\n";
        $inject .= '    <script src="' . e(asset('js/mobile.js')) . '?v=' . $jsVersion . '" defer data-mobile-kit></script>' . "\n";

        $html = substr_replace($html, $inject, $headEnd, 0);

        // Menu bawah ala aplikasi (hanya halaman biasa yang berhasil dimuat).
        if ($response->getStatusCode() === 200 && ! $request->routeIs(...self::NO_TABBAR)) {
            $bodyEnd = strripos($html, '</body>');

            if ($bodyEnd !== false) {
                try {
                    $tabbar = view('partials.mobile-tabbar')->render();
                    $html   = substr_replace($html, $tabbar . "\n", $bodyEnd, 0);
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }

        $response->setContent($html);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
