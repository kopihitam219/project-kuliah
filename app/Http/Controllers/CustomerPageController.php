<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman tambahan customer: Jadwal Saya & Menu.
 */
class CustomerPageController extends Controller
{
    public const TABS = [
        'mendatang'  => 'Mendatang',
        'selesai'    => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    public function jadwal(Request $request): View
    {
        if (class_exists(\App\Support\BookingExpiry::class)) {
            \App\Support\BookingExpiry::sweep(true);
        }

        $tab   = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'mendatang';
        $today = now()->toDateString();
        $base  = Booking::where('user_id', $request->user()->id);

        $queries = [
            'mendatang'  => (clone $base)->whereIn('status', ['pending', 'booked'])->whereDate('booking_date', '>=', $today),
            'selesai'    => (clone $base)->where('status', 'booked')->whereDate('booking_date', '<', $today),
            'dibatalkan' => (clone $base)->whereIn('status', ['cancelled', 'rejected']),
        ];

        $counts = collect($queries)->map(fn ($q) => (clone $q)->count());

        $bookings = $queries[$tab]
            ->when($tab === 'mendatang', fn ($q) => $q->orderBy('booking_date')->orderBy('start_time'),
                fn ($q) => $q->orderByDesc('booking_date')->orderByDesc('start_time'))
            ->limit(60)
            ->get();

        $payments = $bookings->isEmpty()
            ? collect()
            : Payment::whereIn('booking_id', $bookings->pluck('id'))->get()->keyBy('booking_id');

        return view('jadwal', [
            'tab'      => $tab,
            'tabs'     => self::TABS,
            'counts'   => $counts,
            'bookings' => $bookings,
            'payments' => $payments,
        ]);
    }

    public function menu(Request $request): View
    {
        return view('menu', ['user' => $request->user()]);
    }
}
