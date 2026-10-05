<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminOfflineBookingController extends Controller
{
    /**
     * Form Create Booking Admin.
     *
     * Admin dapat membuat booking untuk:
     * - Member yang sudah memiliki akun
     * - Customer offline yang belum memiliki akun
     */
    public function create(): View
    {
        view()->share('offlineLocations', \App\Support\BookingRules::activeLocations());

        $customers = DB::table('users')
            ->where('role', 'customer')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
            ]);

        return view('admin.offline-booking', [
            'customers' => $customers,
        ]);
    }

    /**
     * Simpan booking yang dibuat Admin.
     *
     * Semua booking dari Admin langsung berstatus BOOKED.
     */
    public function store(Request $request): RedirectResponse
    {
        /*
         * Course Lesson: jam mengikuti Settings (default 07:00 - 12:00).
         */
        $lessonType = $request->input('lesson_type', 'driving') === 'course' ? 'course' : 'driving';

        if ($lessonType === 'course') {
            $request->merge([
                'start_time' => \App\Support\BookingRules::courseStart(),
                'end_time'   => \App\Support\BookingRules::courseEnd(),
            ]);
        }

        $request->validate([
            'lesson_type' => ['nullable', 'in:driving,course'],
            'location_id' => ['nullable', 'integer', 'exists:contact_locations,id'],
            'course_venue' => [$lessonType === 'course' ? 'required' : 'nullable', 'string', 'max:150'],
        ], [
            'course_venue.required' => 'Isi lapangan golf untuk Course Lesson.',
        ]);

        $validated = $request->validate([
            /*
            |--------------------------------------------------------------------------
            | Customer Type
            |--------------------------------------------------------------------------
            */

            'customer_type' => [
                'required',
                'in:member,offline',
            ],

            /*
            |--------------------------------------------------------------------------
            | Existing Member
            |--------------------------------------------------------------------------
            */

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            /*
            |--------------------------------------------------------------------------
            | Offline Customer
            |--------------------------------------------------------------------------
            */

            'offline_customer_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'offline_customer_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'offline_customer_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Booking Date & Time
            |--------------------------------------------------------------------------
            */

            'booking_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
                'after_or_equal:' . \App\Support\BookingRules::openTime(),
                'before:' . \App\Support\BookingRules::closeTime(),
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'after:' . \App\Support\BookingRules::openTime(),
                'before_or_equal:' . \App\Support\BookingRules::closeTime(),
            ],

            /*
            |--------------------------------------------------------------------------
            | Additional Data
            |--------------------------------------------------------------------------
            */

            'admin_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'customer_type.required' => 'Jenis customer wajib dipilih.',
            'customer_type.in' => 'Jenis customer tidak valid.',

            'user_id.exists' => 'Member yang dipilih tidak ditemukan.',

            'offline_customer_name.max' =>
                'Nama customer maksimal 255 karakter.',

            'offline_customer_phone.max' =>
                'Nomor HP maksimal 30 karakter.',

            'offline_customer_email.email' =>
                'Format email customer tidak valid.',

            'offline_customer_email.max' =>
                'Email customer maksimal 255 karakter.',

            'booking_date.required' =>
                'Tanggal booking wajib diisi.',

            'booking_date.after_or_equal' =>
                'Tanggal booking tidak boleh sebelum hari ini.',

            'start_time.required' =>
                'Jam mulai wajib diisi.',

            'start_time.date_format' =>
                'Format jam mulai tidak valid.',

            'start_time.after_or_equal' =>
                'Jam mulai minimal ' . \App\Support\BookingRules::openTime() . '.',

            'start_time.before' =>
                'Jam mulai harus sebelum ' . \App\Support\BookingRules::closeTime() . '.',

            'end_time.required' =>
                'Jam selesai wajib diisi.',

            'end_time.date_format' =>
                'Format jam selesai tidak valid.',

            'end_time.after' =>
                'Jam selesai harus setelah jam mulai.',

            'end_time.before_or_equal' =>
                'Jam selesai maksimal ' . \App\Support\BookingRules::closeTime() . '.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validasi Customer
        |--------------------------------------------------------------------------
        */

        if ($validated['customer_type'] === 'member') {
            if (empty($validated['user_id'])) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'user_id' =>
                            'Silakan pilih member yang sudah memiliki akun.',
                    ]);
            }

            $memberExists = DB::table('users')
                ->where('id', $validated['user_id'])
                ->where('role', 'customer')
                ->exists();

            if (!$memberExists) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'user_id' =>
                            'Member yang dipilih tidak valid.',
                    ]);
            }
        }

        if ($validated['customer_type'] === 'offline') {
            if (empty(trim($validated['offline_customer_name'] ?? ''))) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'offline_customer_name' =>
                            'Nama customer wajib diisi.',
                    ]);
            }

            if (empty(trim($validated['offline_customer_phone'] ?? ''))) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'offline_customer_phone' =>
                            'Nomor HP customer wajib diisi.',
                    ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi Jam
        |--------------------------------------------------------------------------
        */

        $startMinutes = $this->timeToMinutes(
            $validated['start_time']
        );

        $endMinutes = $this->timeToMinutes(
            $validated['end_time']
        );

        if ($endMinutes <= $startMinutes) {
            return back()
                ->withInput()
                ->withErrors([
                    'end_time' =>
                        'Jam selesai harus lebih besar dari jam mulai.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Minimal Durasi 30 Menit
        |--------------------------------------------------------------------------
        */

        if (($endMinutes - $startMinutes) < \App\Support\BookingRules::minMinutes()) {
            return back()
                ->withInput()
                ->withErrors([
                    'end_time' =>
                        'Durasi booking minimal ' . \App\Support\BookingRules::minMinutes() . ' menit.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan Booking
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Jadwal Ditutup Admin
        |--------------------------------------------------------------------------
        */

        if (class_exists(\App\Models\ScheduleBlock::class)) {
            $closed = \App\Models\ScheduleBlock::overlapping(
                $validated['booking_date'],
                $validated['start_time'],
                $validated['end_time']
            )->first();

            if ($closed) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'booking_date' => "Jadwal tersebut ditutup ({$closed->time_label}): {$closed->reason}. Buka kembali di menu Kelola Jadwal jika ingin tetap membuat booking.",
                    ]);
            }
        }

        $bookingId = DB::transaction(function () use ($validated, $lessonType, $request) {

            /*
            |--------------------------------------------------------------------------
            | Cek Bentrok Jadwal
            |--------------------------------------------------------------------------
            |
            | Booking aktif:
            | - pending
            | - booked
            |
            | Booking rejected tidak dianggap mengunci jadwal.
            |
            */

            $overlapExists = DB::table('bookings')
                ->where(
                    'booking_date',
                    $validated['booking_date']
                )
                ->whereIn('status', [
                    'pending',
                    'booked',
                ])
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

            if ($overlapExists) {
                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | Data Booking
            |--------------------------------------------------------------------------
            */

            $data = [
                /*
                 * Member:
                 * user_id terisi.
                 *
                 * Offline:
                 * user_id NULL.
                 */
                'user_id' => $validated['customer_type'] === 'member'
                    ? $validated['user_id']
                    : null,

                /*
                 * Customer offline.
                 */
                'offline_customer_name' =>
                    $validated['customer_type'] === 'offline'
                        ? trim($validated['offline_customer_name'])
                        : null,

                'offline_customer_phone' =>
                    $validated['customer_type'] === 'offline'
                        ? trim($validated['offline_customer_phone'])
                        : null,

                'offline_customer_email' =>
                    $validated['customer_type'] === 'offline'
                        ? (
                            !empty($validated['offline_customer_email'])
                                ? trim($validated['offline_customer_email'])
                                : null
                        )
                        : null,

                /*
                 * Booking.
                 */
                'booking_date' => $validated['booking_date'],

                'start_time' => $validated['start_time'],

                'end_time' => $validated['end_time'],

                /*
                 * Jenis lesson & lapangan.
                 */
                'lesson_type' => $lessonType,

                'location_id' => $lessonType === 'course'
                    ? null
                    : ($request->input('location_id') ?: null),

                'course_venue' => $lessonType === 'course'
                    ? trim((string) $request->input('course_venue'))
                    : null,

                /*
                 * Admin booking langsung BOOKED.
                 */
                'status' => 'booked',

                /*
                 * Booking dibuat langsung oleh Admin.
                 */
                'source' => 'offline',

                /*
                 * Catatan Admin.
                 */
                'admin_notes' =>
                    !empty($validated['admin_notes'])
                        ? trim($validated['admin_notes'])
                        : null,

                'created_at' => now(),

                'updated_at' => now(),
            ];

            return DB::table('bookings')
                ->insertGetId($data);
        });

        /*
        |--------------------------------------------------------------------------
        | Jadwal Bentrok
        |--------------------------------------------------------------------------
        */

        if ($bookingId === null) {
            return back()
                ->withInput()
                ->withErrors([
                    'booking_date' =>
                        'Jadwal tersebut sudah memiliki booking aktif. Silakan pilih tanggal atau jam lain.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Berhasil
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('admin.offline-booking.create')
            ->with(
                'success',
                'Booking berhasil dibuat dan langsung berstatus BOOKED.'
            );
    }

    /**
     * Convert HH:MM menjadi jumlah menit.
     */
    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map(
            'intval',
            explode(':', $time)
        );

        return ($hours * 60) + $minutes;
    }
}
