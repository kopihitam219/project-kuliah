<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\BookingRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        /*
         * Booking yang lewat batas waktu bayar digagalkan dulu,
         * supaya slotnya langsung tersedia lagi.
         */
        if (class_exists(\App\Support\BookingExpiry::class)) {
            \App\Support\BookingExpiry::sweep(true);
        }

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
                'source',
                'created_at',
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
                'lesson_type',
                'location_id',
                'course_venue',
                'status',
            ]);

        return view('booking', [
            'selectedDate' => $selectedDate,
            'pendingBookings' => $pendingBookings,
            'bookedBookings' => $bookedBookings,
            'yourBookings' => $yourBookings,
            'locations' => BookingRules::activeLocations(),
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
        $lessonType = $request->input('lesson_type', 'driving');

        /*
         * Course Lesson: jam selalu sesuai Settings (default 07:00 - 12:00).
         */
        if ($lessonType === 'course') {
            $request->merge([
                'start_time' => BookingRules::courseStart(),
                'end_time'   => BookingRules::courseEnd(),
            ]);
        }

        $validated = $request->validate([
            'lesson_type' => [
                'required',
                Rule::in(array_keys(BookingRules::lessonTypes())),
            ],

            'location_id' => [
                Rule::requiredIf($lessonType !== 'course'),
                'nullable',
                Rule::exists('contact_locations', 'id')->where('is_active', true),
            ],

            'course_venue' => [
                Rule::requiredIf($lessonType === 'course'),
                'nullable',
                'string',
                'max:150',
            ],

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
        ], [
            'lesson_type.required' => 'Pilih jenis lesson terlebih dahulu.',
            'lesson_type.in'       => 'Jenis lesson tidak tersedia.',
            'location_id.required' => 'Pilih lapangan driving range terlebih dahulu (Rawamangun atau Suvarna).',
            'location_id.exists'   => 'Lapangan yang dipilih tidak tersedia.',
            'course_venue.required' => 'Tulis lapangan golf yang Anda pilih untuk Course Lesson.',
            'course_venue.max'      => 'Nama lapangan golf maksimal 150 karakter.',
        ]);

        if ($lessonType === 'course') {
            $this->validateCourseDate($validated['booking_date']);
        } else {
            $this->validateBookingTime(
                $validated['start_time'],
                $validated['end_time']
            );
        }

        $newBooking = DB::transaction(function () use ($request, $validated, $lessonType) {

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
                    'booking' => $lessonType === 'course'
                        ? 'Course Lesson tidak tersedia di tanggal ini karena sebagian jam ' . BookingRules::courseStart() . '–' . BookingRules::courseEnd() . ' sudah terisi. Silakan pilih tanggal lain.'
                        : 'Waktu yang dipilih sudah digunakan. Silakan pilih waktu lain.',
                ]);
            }

            return Booking::create([
                'user_id' => $request->user()->id,
                'booking_date' => $validated['booking_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'lesson_type' => $lessonType,
                'location_id' => $lessonType === 'course' ? null : ($validated['location_id'] ?? null),
                'course_venue' => $lessonType === 'course' ? trim($validated['course_venue']) : null,
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

        if ($booking->isCourse()) {
            $request->merge([
                'start_time' => BookingRules::courseStart(),
                'end_time'   => BookingRules::courseEnd(),
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

        if ($booking->isCourse()) {
            $this->validateCourseDate($validated['booking_date']);
        } else {
            $this->validateBookingTime(
                $validated['start_time'],
                $validated['end_time']
            );
        }

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

        if ($request->input('from') === 'jadwal' && \Illuminate\Support\Facades\Route::has('jadwal')) {
            return redirect()
                ->route('jadwal')
                ->with('booking_success', 'Jadwal berhasil diubah. Booking kembali berstatus PENDING dan menunggu approval admin.');
        }

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
        $durationMinutes = (int) abs($start->diffInMinutes($end));

        /*
         * Lesson coach dihitung per jam: mulai di jam bulat & durasi kelipatan 1 jam.
         */
        if ($start->minute !== 0 || $end->minute !== 0 || $durationMinutes % 60 !== 0) {
            throw ValidationException::withMessages([
                'booking' => 'Lesson dihitung per jam. Pilih jam mulai dan selesai di jam bulat, misalnya 08:00 – 10:00.',
            ]);
        }

        if ($durationMinutes < \App\Support\BookingRules::minMinutes()) {
            throw ValidationException::withMessages([
                'booking' =>
                    'Durasi lesson minimal ' . \App\Support\BookingRules::minMinutes() . ' menit.',
            ]);
        }
    }

    /**
     * Validasi Course Lesson: fitur aktif & jam mulai belum lewat.
     */
    private function validateCourseDate(string $date): void
    {
        if (! BookingRules::courseEnabled()) {
            throw ValidationException::withMessages([
                'booking' => 'Course Lesson sedang tidak tersedia.',
            ]);
        }

        $start = Carbon::parse($date . ' ' . BookingRules::courseStart());

        if ($start->lte(now())) {
            throw ValidationException::withMessages([
                'booking' => 'Course Lesson hari ini sudah dimulai. Silakan pilih tanggal lain.',
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
