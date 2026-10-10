<?php

namespace App\Providers;

use App\Support\ThemeViews;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Tema terang (bawaan) + pilihan tema gelap.
 * Setiap view yang dirender dicatat agar CSS tema terangnya ikut dimuat.
 */
class ThemeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            ThemeViews::add($view->getName());
        });
    }
}
