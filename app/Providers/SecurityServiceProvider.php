<?php

namespace App\Providers;

use App\Support\Security;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Batas percobaan login dari Settings > Keamanan.
 * Didaftarkan setelah semua provider selesai, sehingga menggantikan batas bawaan Fortify.
 */
class SecurityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            RateLimiter::for('login', function (Request $request) {
                $field = class_exists(\Laravel\Fortify\Fortify::class) ? \Laravel\Fortify\Fortify::username() : 'email';
                $key   = Str::transliterate(Str::lower((string) $request->input($field))) . '|' . $request->ip();

                return Limit::perMinutes(Security::lockMinutes(), Security::loginAttempts())->by($key);
            });
        });
    }
}
