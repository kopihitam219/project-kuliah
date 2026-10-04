<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin memindahkan jadwal booking customer
 * (misalnya karena jadwal lama terkena penutupan lapangan).
 * Notifikasi ke customer dikirim otomatis oleh BookingObserver.
 */
class AdminBookingRescheduleController extends Controller
{
    private const OPEN_TIME  = '07:00';
    private const CLOSE_TIME = '20:00';

    /**
     * Halaman reschedule.
     */
    public function edit(Request $request, Booking $booking): View|RedirectResponse
    {
        if (! in_array($booking->status, ['pending', 'booked'], true)) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Booking ini sudah tidak aktif sehingga tidak bisa di-reschedule.');
        }

        $booking->load('user');

        $duration = $this->minutesBetween($booking->start_time, $booking->end_time);

        // Tanggal yang sedang dilihat di grid jam
        try {
            $date = Carbon::parse($request->query('date', $booking->booking_date->toDateString()))->startOfDay();
        } catch (\Throwable $e) {
            $date = today();
        }

        if ($date->lt(today())) {
            $date = today();
        }

        $currentBlocks = class_exists(\App\Models\ScheduleBlock::class)
            ? \App\Models\ScheduleBlock::overlapping($booking->booking_date, $booking->start_time, $booking->end_time)->get()
            : collect();

        $payment = class_exists(\App\Models\Payment::class)
            ? \App\Models\Payment::where('booking_id', $booking->id)->first()
            : null;

        return view('admin.bookings.reschedule', [
            'booking'       => $booking,
            'duration'      => $duration,
            'date'          => $date,
            'slots'         => $this->slots($date, $booking),
            'currentBlocks' => $currentBlocks,
            'payment'       => $payment,
            'from'          => $request->query('from') === 'schedule' ? 'schedule' : 'dashboard',
        ]);
    }

    /**
     * Simpan jadwal baru.
     */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        if (! in_array($booking->status, ['pending', 'booked'], true)) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'Booking ini sudah tidak aktif.');
        }

        $validated = $request->validate([
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i'],
            'from'         => ['nullable', 'in:schedule,dashboard'],
        ], [
            'booking_date.required'       => 'Tanggal baru wajib diisi.',
            'booking_date.after_or_equal' => 'Tanggal baru tidak boleh sebelum hari ini.',
            'start_time.required'         => 'Pilih jam mulai.',
            'end_time.required'           => 'Pilih jam selesai.',
        ]);

        $start    = $validated['start_time'];
        $end      = $validated['end_time'];
        $newDate  = Carbon::parse($validated['booking_date']);
        $minutes  = $this->minutesBetween($start, $end);

        // Aturan jam lesson
        if ($start < self::OPEN_TIME || $end > self::CLOSE_TIME) {
            return back()->withInput()->withErrors(['booking' => 'Jam lesson hanya tersedia 07:00 sampai 20:00.']);
        }

        if ($minutes < 30) {
            return back()->withInput()->withErrors(['booking' => 'Jam selesai harus setelah jam mulai, minimal 30 menit.']);
        }

        if ($newDate->isToday() && $start <= now()->format('H:i')) {
            return back()->withInput()->withErrors(['booking' => 'Jam mulai sudah lewat untuk hari ini.']);
        }

        // Tidak berubah
        if (
            $booking->booking_date->toDateString() === $newDate->toDateString()
            && substr($booking->start_time, 0, 5) === $start
            && substr($booking->end_time, 0, 5) === $end
        ) {
            return back()->withInput()->withErrors(['booking' => 'Jadwal baru sama dengan jadwal sebelumnya.']);
        }

        // Booking yang sudah lunas tidak boleh berubah durasi
        $payment = class_exists(\App\Models\Payment::class)
            ? \App\Models\Payment::where('booking_id', $booking->id)->first()
            : null;

        $oldMinutes = $this->minutesBetween($booking->start_time, $booking->end_time);

        if ($payment && $payment->status === 'paid' && $minutes !== $oldMinutes) {
            return back()->withInput()->withErrors([
                'booking' => "Booking ini sudah lunas untuk durasi {$payment->duration_label}. Pilih jam baru dengan durasi yang sama.",
            ]);
        }

        // Bentrok dengan booking lain
        $overlap = Booking::query()
            ->whereDate('booking_date', $newDate->toDateString())
            ->whereIn('status', ['pending', 'booked'])
            ->where('id', '!=', $booking->id)
            ->where('start_time', '<', $end . ':00')
            ->where('end_time', '>', $start . ':00')
            ->exists();

        if ($overlap) {
            return back()->withInput()->withErrors(['booking' => 'Jadwal baru bentrok dengan booking lain. Pilih jam yang masih tersedia.']);
        }

        // Simpan (jadwal yang ditutup dicek otomatis oleh BookingObserver)
        DB::transaction(function () use ($booking, $newDate, $start, $end, $payment, $minutes, $oldMinutes) {
            $booking->update([
                'booking_date' => $newDate->toDateString(),
                'start_time'   => $start,
                'end_time'     => $end,
            ]);

            // Pembayaran belum lunas: sesuaikan nominal dengan durasi baru
            if ($payment && $payment->status !== 'paid' && $minutes !== $oldMinutes) {
                $payment->update([
                    'duration_minutes' => $minutes,
                    'amount'           => (int) round($minutes / 60 * \App\Models\Payment::PRICE_PER_HOUR),
                ]);
            }
        });

        $booking->refresh()->load('user');

        $name     = $booking->user->name ?? $booking->offline_customer_name ?? 'customer';
        $schedule = $booking->booking_date->locale('id')->translatedFormat('d M Y') . " {$start}–{$end}";

        $message = "Jadwal {$name} dipindah ke {$schedule}.";
        $message .= $booking->user_id
            ? ' Customer sudah diberi notifikasi.'
            : ' Booking offline: hubungi customer secara manual.';

        $route = ($validated['from'] ?? null) === 'schedule' ? 'admin.schedule-blocks.index' : 'admin.dashboard';

        return redirect()
            ->route($route)
            ->with($booking->user_id ? 'success' : 'error', $message);
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */

    /**
     * Status setiap jam (07:00–20:00) pada tanggal tertentu.
     * available | booked | pending | blocked | current | past
     */
    private function slots(Carbon $date, Booking $booking): array
    {
        $others = Booking::query()
            ->whereDate('booking_date', $date->toDateString())
            ->whereIn('status', ['pending', 'booked'])
            ->where('id', '!=', $booking->id)
            ->get(['start_time', 'end_time', 'status']);

        $blocks = class_exists(\App\Models\ScheduleBlock::class)
            ? \App\Models\ScheduleBlock::whereDate('date', $date->toDateString())->get()
            : collect();

        $isBookingDate = $booking->booking_date->isSameDay($date);
        $slots         = [];

        for ($hour = 7; $hour < 20; $hour++) {
            $start = sprintf('%02d:00', $hour);
            $end   = sprintf('%02d:00', $hour + 1);

            $overlaps = fn ($s, $e) => substr((string) $s, 0, 5) < $end && substr((string) $e, 0, 5) > $start;

            $status = 'available';

            if ($blocks->contains(fn ($b) => ! $b->start_time || $overlaps($b->start_time, $b->end_time))) {
                $status = 'blocked';
            } elseif ($isBookingDate && $overlaps($booking->start_time, $booking->end_time)) {
                $status = 'current';
            } elseif ($other = $others->first(fn ($o) => $overlaps($o->start_time, $o->end_time))) {
                $status = $other->status;
            } elseif ($date->isToday() && $start <= now()->format('H:i')) {
                $status = 'past';
            }

            $slots[] = ['start' => $start, 'end' => $end, 'status' => $status];
        }

        return $slots;
    }

    private function minutesBetween($start, $end): int
    {
        return (int) abs(Carbon::parse((string) $start)->diffInMinutes(Carbon::parse((string) $end)));
    }
}
