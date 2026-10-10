<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\ThemeAssets;
use App\Support\ThemeViews;
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

        // Menu bawah ala aplikasi (hanya halaman biasa yang berhasil dimuat).
        // Dirender lebih dulu supaya view-nya ikut tercatat untuk tema.
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

        $inject = '';

        // Tema: terang (bawaan) atau gelap, disimpan di browser pengunjung.
        $inject .= '    <script data-mobile-kit>(function(){var t="light";try{t=localStorage.getItem("gbl-theme")||"light";}catch(e){}document.documentElement.setAttribute("data-theme",t==="dark"?"dark":"light");})();</script>' . "\n";

        if (! preg_match('/<meta[^>]+name=["\']viewport["\']/i', $html)) {
            $inject .= '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
        }

        $inject .= '    <meta name="theme-color" content="#f5f3ec" media="(prefers-color-scheme: light)">' . "\n";
        $inject .= '    <meta name="theme-color" content="#04100b" media="(prefers-color-scheme: dark)">' . "\n";
        $inject .= '    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        $inject .= '    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">' . "\n";
        $inject .= $this->backgroundVars();
        $inject .= $this->css('css/mobile.css');
        $inject .= $this->css('css/theme.css');
        $inject .= $this->css('css/light/_mobile.css');

        foreach (ThemeViews::all() as $view) {
            if (isset(ThemeAssets::VIEWS[$view])) {
                $inject .= $this->css('css/light/' . ThemeAssets::VIEWS[$view]);
            }
        }

        $inject .= '    <script src="' . e(asset('js/mobile.js')) . '?v=' . $this->version('js/mobile.js') . '" defer data-mobile-kit></script>' . "\n";
        $inject .= '    <script src="' . e(asset('js/theme.js')) . '?v=' . $this->version('js/theme.js') . '" defer data-mobile-kit></script>' . "\n";

        $html = substr_replace($html, $inject, $headEnd, 0);

        $response->setContent($html);
        $response->headers->remove('Content-Length');

        return $response;
    }

    /** Foto latar dari Settings dipakai ulang oleh CSS tema terang. */
    private function backgroundVars(): string
    {
        if (! class_exists(\App\Support\Brand::class) || ! method_exists(\App\Support\Brand::class, 'background')) {
            return '';
        }

        $vars = [];

        foreach (['public', 'auth', 'admin'] as $area) {
            try {
                $url    = (string) \App\Support\Brand::background($area);
                $vars[] = '--gbl-photo-' . $area . ":url('" . str_replace(["'", '"', '<', '>'], ['%27', '%22', '', ''], $url) . "')";
            } catch (Throwable $e) {
                // abaikan
            }
        }

        return $vars ? '    <style data-mobile-kit>:root{' . implode(';', $vars) . '}</style>' . "\n" : '';
    }

    private function version(string $file): string
    {
        return ThemeAssets::VERSION;
    }

    private function css(string $file): string
    {
        return '    <link rel="stylesheet" href="' . e(asset($file)) . '?v=' . $this->version($file) . '" data-mobile-kit>' . "\n";
    }
}
