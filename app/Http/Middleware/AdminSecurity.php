<?php

namespace App\Http\Middleware;

use App\Support\Security;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keamanan akun admin (diatur di Settings > Keamanan):
 * - keluar otomatis jika tidak aktif
 * - wajib verifikasi 2 langkah (2FA)
 */
class AdminSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return $next($request);
        }

        // 1) Keluar otomatis jika tidak aktif
        $idle = Security::adminIdleMinutes();

        if ($idle > 0) {
            $last = (int) $request->session()->get('admin_last_activity', 0);

            if ($last > 0 && (time() - $last) > $idle * 60) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('error', "Anda keluar otomatis karena tidak aktif selama {$idle} menit. Silakan login kembali.");
            }

            $request->session()->put('admin_last_activity', time());
        }

        // 2) Wajib 2FA: arahkan ke halaman pengaturan 2FA sampai aktif
        $twoFactorRoute = Security::twoFactorRoute();

        if (Security::requireAdmin2fa() && $twoFactorRoute && empty($user->two_factor_confirmed_at)) {
            $allowed = $request->routeIs($twoFactorRoute, 'logout', 'password.confirm', 'password.confirm.*', 'two-factor.*')
                || $request->is('livewire*', 'user/*', 'two-factor-challenge');

            if (! $allowed) {
                return redirect()
                    ->route($twoFactorRoute)
                    ->with('status', 'Admin wajib mengaktifkan verifikasi 2 langkah (2FA) sebelum melanjutkan.');
            }
        }

        return $next($request);
    }
}
