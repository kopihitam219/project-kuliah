<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Halaman booking customer.
     */
    public function index(Request $request): View
    {
        $selectedDate = $request->input(
            'date',
            now()->toDateString()
        );

        try {
            $selectedDate = Carbon::parse($selectedDate)->toDateString();
        } catch (\Throwable $e) {
            $selectedDate = now()->toDateString();
        }

        /*
         * Customer tidak boleh melihat tanggal yang sudah lewat.
         */
        if ($selectedDate < now()->toDateString()) {
            $selectedDate = now()->toDateString();
        }

        /*
         * Ambil booking aktif pada tanggal yang dipilih.
         *
         * PENDING  = slot sedang menunggu approval Admin
         * BOOKED   = sudah disetujui Admin
         *
         * CANCELLED tidak dimasukkan karena slot tersebut
         * sudah kembali tersedia.
         */
        $bookings = Booking::query()
            ->whereDate('booking_date', $selectedDate)
            ->whereIn('status', ['pending', 'booked'])
            ->orderBy('start_time')
            ->get([
                'id',
                'user_id',
                'booking_date',
                'start_time',
                'end_time',
                'status',
            ]);

        $pendingBookings = $bookings
            ->where('status', 'pending')
            ->values();

        $bookedBookings = $bookings
            ->where('status', 'booked')
            ->values();

        /*
         * Booking milik customer yang sedang login.
         *
         * Hanya booking aktif yang ditampilkan.
         */
        $yourBookings = Booking::query()
            ->where('user_id', $request->user()->id)
            ->whereDate(
                'booking_date',
                '>=',
                now()->toDateString()
            )
            ->whereIn('status', ['pending', 'booked'])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get([
                'id',
                'booking_date',
                'start_time',
                'end_time',
                'status',
            ]);

        return view('booking', [
            'selectedDate' => $selectedDate,
            'pendingBookings' => $pendingBookings,
            'bookedBookings' => $bookedBookings,
            'yourBookings' => $yourBookings,
        ]);
    }

    /**
     * Simpan booking baru.
     *
     * Booking baru selalu masuk PENDING.
     * Admin harus melakukan approval agar menjadi BOOKED.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
            ],
        ]);

        $this->validateBookingTime(
            $validated['start_time'],
            $validated['end_time']
        );

        $newBooking = DB::transaction(function () use ($request, $validated) {

            /*
             * Lock booking pada tanggal tersebut selama proses
             * pengecekan dan penyimpanan.
             *
             * Tujuannya agar dua request bersamaan tidak mengambil
             * slot yang sama.
             */
            $overlap = Booking::query()
                ->whereDate(
                    'booking_date',
                    $validated['booking_date']
                )
                ->whereIn(
                    'status',
                    ['pending', 'booked']
                )
                ->where(function ($query) use ($validated) {
                    $query
                        ->where(
                            'start_time',
                            '<',
                            $validated['end_time']
                        )
                        ->where(
                            'end_time',
                            '>',
                            $validated['start_time']
                        );
                })
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'booking' =>
                        'Waktu yang dipilih sudah digunakan. Silakan pilih waktu lain.',
                ]);
            }

            return Booking::create([
                'user_id' => $request->user()->id,
                'booking_date' => $validated['booking_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'status' => 'pending',
            ]);
        });

        return redirect()
            ->route('payment.booking', $newBooking)
            ->with(
                'booking_success',
                'Booking berhasil dibuat. Silakan selesaikan pembayaran untuk mengamankan jadwal Anda.'
            );
    }

    /**
     * Batalkan booking.
     *
     * Member:
     * - hanya boleh membatalkan booking miliknya sendiri.
     *
     * Admin:
     * - boleh membatalkan booking apa pun.
     *
     * Aturan:
     * - hanya boleh dilakukan H-1.
     * - hari-H sudah tidak boleh cancel.
     */
    public function cancel(
        Request $request,
        Booking $booking
    ): RedirectResponse {
        $this->authorizeBookingAction(
            $request,
            $booking
        );

        $this->ensureHMinusOne(
            $booking,
            'dibatalkan'
        );

        if (!in_array(
            $booking->status,
            ['pending', 'booked'],
            true
        )) {
            return back()->withErrors([
                'booking' =>
                    'Booking ini sudah tidak aktif.',
            ]);
        }

        $booking->update([
            'status' => 'cancelled',
        ]);

        return back()->with(
            'booking_success',
            'Booking berhasil dibatalkan. Jam tersebut sekarang tersedia kembali.'
        );
    }

    /**
     * Reschedule booking.
     *
     * Member:
     * - hanya boleh reschedule booking miliknya sendiri.
     *
     * Admin:
     * - boleh reschedule booking apa pun.
     *
     * Aturan:
     * - reschedule hanya boleh H-1.
     * - jadwal baru harus minimal H-1.
     * - jadwal lama langsung dilepas.
     * - jadwal baru kembali menjadi PENDING.
     * - Admin harus approve ulang.
     */
    public function reschedule(
        Request $request,
        Booking $booking
    ): RedirectResponse {
        $this->authorizeBookingAction(
            $request,
            $booking
        );

        $this->ensureHMinusOne(
            $booking,
            'diubah jadwalnya'
        );

        if (!in_array(
            $booking->status,
            ['pending', 'booked'],
            true
        )) {
            return back()->withErrors([
                'booking' =>
                    'Booking ini sudah tidak aktif.',
            ]);
        }

        $validated = $request->validate([
            'booking_date' => [
                'required',
                'date',
                'after_or_equal:' . \App\Support\BookingRules::minRescheduleDate()->toDateString(),
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
            ],
        ]);

        $this->validateBookingTime(
            $validated['start_time'],
            $validated['end_time']
        );

        DB::transaction(function () use (
            $booking,
            $validated
        ) {

            /*
             * Lock booking yang sedang di-reschedule.
             */
            $currentBooking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($booking->id);

            /*
             * Pastikan booking belum berubah status
             * selama proses berjalan.
             */
            if (!in_array(
                $currentBooking->status,
                ['pending', 'booked'],
                true
            )) {
                throw ValidationException::withMessages([
                    'booking' =>
                        'Booking ini sudah tidak aktif.',
                ]);
            }

            /*
             * Jika user memilih tanggal dan jam yang sama,
             * tidak perlu membuat perubahan.
             */
            if (
                $currentBooking->booking_date->toDateString()
                    === $validated['booking_date']
                &&
                $this->normalizeTime(
                    $currentBooking->start_time
                ) === $validated['start_time']
                &&
                $this->normalizeTime(
                    $currentBooking->end_time
                ) === $validated['end_time']
            ) {
                throw ValidationException::withMessages([
                    'booking' =>
                        'Jadwal baru sama dengan jadwal sebelumnya.',
                ]);
            }

            /*
             * Cek bentrok dengan booking lain.
             *
             * Booking yang sedang di-reschedule dikecualikan
             * dari pengecekan.
             */
            $overlap = Booking::query()
                ->whereDate(
                    'booking_date',
                    $validated['booking_date']
                )
                ->whereIn(
                    'status',
                    ['pending', 'booked']
                )
                ->where('id', '!=', $currentBooking->id)
                ->where(function ($query) use ($validated) {
                    $query
                        ->where(
                            'start_time',
                            '<',
                            $validated['end_time']
                        )
                        ->where(
                            'end_time',
                            '>',
                            $validated['start_time']
                        );
                })
                ->lockForUpdate()
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'booking' =>
                        'Jadwal baru sudah digunakan. Silakan pilih jam lain.',
                ]);
            }

            /*
             * Jadwal lama langsung dilepas karena record
             * dipindahkan ke tanggal/jam baru.
             *
             * Status kembali PENDING karena jadwal baru
             * harus disetujui Admin kembali.
             */
            $currentBooking->update([
                'booking_date' => $validated['booking_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'status' => 'pending',
            ]);
        });

        return redirect()
            ->route('booking', [
                'date' => $validated['booking_date'],
            ])
            ->with(
                'booking_success',
                'Jadwal berhasil diubah. Booking kembali berstatus PENDING dan menunggu approval admin.'
            );
    }

    /**
     * Validasi waktu lesson.
     */
    private function validateBookingTime(
        string $startTime,
        string $endTime
    ): void {
        $start = Carbon::createFromFormat(
            'H:i',
            $startTime
        );

        $end = Carbon::createFromFormat(
            'H:i',
            $endTime
        );

        $minimumStart = Carbon::createFromFormat(
            'H:i', \App\Support\BookingRules::openTime()
        );

        $maximumEnd = Carbon::createFromFormat(
            'H:i', \App\Support\BookingRules::closeTime()
        );

        /*
         * Lesson hanya 07:00 - 20:00.
         */
        if (
            $start->lt($minimumStart) ||
            $end->gt($maximumEnd)
        ) {
            throw ValidationException::withMessages([
                'booking' =>
                    'Jam lesson hanya tersedia dari ' . \App\Support\BookingRules::openTime() . ' sampai ' . \App\Support\BookingRules::closeTime() . '.',
            ]);
        }

        /*
         * End harus lebih besar dari Start.
         */
        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'booking' =>
                    'End Time harus lebih besar dari Start Time.',
            ]);
        }

        /*
         * Minimal 30 menit.
         */
        $durationMinutes = $start->diffInMinutes($end);

        if ($durationMinutes < \App\Support\BookingRules::minMinutes()) {
            throw ValidationException::withMessages([
                'booking' =>
                    'Durasi lesson minimal ' . \App\Support\BookingRules::minMinutes() . ' menit.',
            ]);
        }
    }

    /**
     * Memastikan hanya owner atau Admin yang dapat
     * melakukan cancel/reschedule.
     */
    private function authorizeBookingAction(
        Request $request,
        Booking $booking
    ): void {
        $user = $request->user();

        /*
         * Admin boleh mengelola semua booking.
         */
        if ($user->role === 'admin') {
            return;
        }

        /*
         * Member hanya boleh mengelola booking miliknya sendiri.
         */
        if ((int) $booking->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    /**
     * Aturan H-1.
     *
     * Jika lesson tanggal 2 Oktober:
     * cancel/reschedule masih diperbolehkan tanggal 1 Oktober.
     *
     * Saat sudah tanggal 2 Oktober:
     * cancel/reschedule ditolak.
     */
    private function ensureHMinusOne(
        Booking $booking,
        string $action
    ): void {
        $bookingDate = Carbon::parse(
            $booking->booking_date
        )->startOfDay();

        $today = now()->startOfDay();

        /*
         * Harus masih minimal besok.
         */
        if (! \App\Support\BookingRules::canModifyDate($bookingDate)) {
            throw ValidationException::withMessages([
                'booking' =>
                    "Booking ini tidak dapat {$action} lagi. " . \App\Support\BookingRules::cancelRuleText(),
            ]);
        }
    }

    /**
     * Normalisasi waktu untuk perbandingan.
     */
    private function normalizeTime($time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i');
        }

        return Carbon::parse($time)->format('H:i');
    }
}