<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\ScheduleBlock;
use App\Support\BookingNotifier;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * - Menolak booking / reschedule yang bentrok dengan jadwal yang ditutup admin.
 * - Mengirim notifikasi setiap ada perubahan booking.
 * Tidak perlu mengubah controller: semua create/update Booking tertangkap di sini.
 */
class BookingObserver
{
    /**
     * Cek jadwal ditutup sebelum booking baru disimpan / dijadwal ulang.
     */
    public function saving(Booking $booking): void
    {
        $this->checkMaxActive($booking);

        if (! class_exists(ScheduleBlock::class)) {
            return;
        }

        $scheduleChanged = ! $booking->exists
            || $booking->isDirty(['booking_date', 'start_time', 'end_time']);

        if (! $scheduleChanged || ! in_array($booking->status, ['pending', 'booked'], true)) {
            return;
        }

        $block = ScheduleBlock::overlapping($booking->booking_date, $booking->start_time, $booking->end_time)->first();

        if ($block) {
            $date = Carbon::parse($booking->booking_date)->locale('id')->translatedFormat('d M Y');

            throw ValidationException::withMessages([
                'booking' => "Jadwal {$date} ({$block->time_label}) ditutup oleh admin: {$block->reason}. Silakan pilih jam lain.",
            ]);
        }
    }

    public function created(Booking $booking): void
    {
        $actor = auth()->user();

        if (! $actor || $actor->role !== 'customer' || ! class_exists(BookingNotifier::class)) {
            return;
        }

        $this->safely(fn () => BookingNotifier::toAdmins($booking, 'created', $actor));
    }

    public function updated(Booking $booking): void
    {
        $actor = auth()->user();

        if (! $actor || ! class_exists(BookingNotifier::class)) {
            return;
        }

        $rescheduled    = $booking->wasChanged(['booking_date', 'start_time', 'end_time']);
        $newStatus      = $booking->wasChanged('status') ? $booking->status : null;
        $previousStatus = $booking->getOriginal('status');

        $event = match (true) {
            $rescheduled               => 'rescheduled',
            $newStatus === 'booked'    => 'approved',
            $newStatus === 'rejected'  => 'rejected',
            $newStatus === 'cancelled' => ($actor->role === 'admin' && $previousStatus === 'pending') ? 'rejected' : 'cancelled',
            default                    => null,
        };

        if (! $event) {
            return;
        }

        $from = $rescheduled
            ? BookingNotifier::schedule(
                $booking->getOriginal('booking_date'),
                $booking->getOriginal('start_time'),
                $booking->getOriginal('end_time'),
            )
            : null;

        $this->safely(function () use ($booking, $event, $actor, $from) {
            if ($actor->role === 'customer') {
                BookingNotifier::toAdmins($booking, $event, $actor, $from);
            } elseif ($actor->role === 'admin') {
                BookingNotifier::toCustomer($booking, $event, $actor, $from);
            }
        });
    }

    /**
     * Batas jumlah booking aktif per customer (diatur di Settings, 0 = tanpa batas).
     * Hanya berlaku untuk booking baru yang dibuat customer sendiri.
     */
    private function checkMaxActive(Booking $booking): void
    {
        if ($booking->exists || ! $booking->user_id || ! class_exists(\App\Support\BookingRules::class)) {
            return;
        }

        $max = \App\Support\BookingRules::maxActive();

        if ($max === 0 || auth()->user()?->role !== 'customer') {
            return;
        }

        $active = Booking::where('user_id', $booking->user_id)
            ->whereIn('status', ['pending', 'booked'])
            ->whereDate('booking_date', '>=', today())
            ->count();

        if ($active >= $max) {
            throw ValidationException::withMessages([
                'booking' => "Anda sudah memiliki {$active} booking aktif. Maksimal {$max} booking aktif per customer, "
                    . 'selesaikan atau batalkan salah satunya terlebih dahulu.',
            ]);
        }
    }

    /**
     * Notifikasi gagal tidak boleh menggagalkan proses booking.
     */
    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
