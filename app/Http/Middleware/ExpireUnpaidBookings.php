<?php

namespace App\Http\Middleware;

use App\Support\BookingExpiry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menggagalkan booking yang tidak dibayar dalam batas waktu.
 * Berjalan otomatis saat website dibuka (paling sering sekali per menit),
 * jadi tidak perlu menjalankan scheduler / cron.
 */
class ExpireUnpaidBookings
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            BookingExpiry::sweep();
        } catch (\Throwable $e) {
            report($e);
        }

        return $next($request);
    }
}
