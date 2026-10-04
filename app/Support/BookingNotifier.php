<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class BookingNotifier
{
    /**
     * Notifikasi ke semua admin (aksi dilakukan customer).
     */
    public static function toAdmins(Booking $booking, string $event, User $actor, ?string $from = null): void
    {
        $admins = User::where('role', 'admin')
            ->where('id', '!=', $actor->id)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        $schedule = self::schedule($booking->booking_date, $booking->start_time, $booking->end_time);

        [$title, $message] = match ($event) {
            'created'     => ['Booking baru', "{$actor->name} membuat booking {$schedule}. Menunggu approval."],
            'cancelled'   => ['Booking dibatalkan', "{$actor->name} membatalkan booking {$schedule}."],
            'rescheduled' => ['Reschedule booking', "{$actor->name} mengubah jadwal" . ($from ? " dari {$from}" : '') . " ke {$schedule}. Menunggu approval ulang."],
            default       => ['Aktivitas booking', "{$actor->name} memperbarui booking {$schedule}."],
        };

        Notification::send($admins, new BookingActivity(
            $event,
            $title,
            $message,
            route('admin.dashboard', [], false) . '#booking-list',
            $booking->id,
        ));
    }

    /**
     * Notifikasi ke customer pemilik booking (aksi dilakukan admin).
     */
    public static function toCustomer(Booking $booking, string $event, User $actor, ?string $from = null): void
    {
        if (! $booking->user_id) {
            return; // booking offline tidak punya akun
        }

        $customer = User::find($booking->user_id);

        if (! $customer || $customer->id === $actor->id) {
            return;
        }

        $schedule = self::schedule($booking->booking_date, $booking->start_time, $booking->end_time);

        [$title, $message] = match ($event) {
            'approved'    => ['Booking disetujui', "Booking {$schedule} sudah disetujui admin. Sampai jumpa di lapangan!"],
            'rejected'    => ['Booking ditolak', "Maaf, booking {$schedule} tidak dapat disetujui. Silakan pilih jadwal lain."],
            'cancelled'   => ['Booking dibatalkan admin', "Booking {$schedule} dibatalkan oleh admin."],
            'rescheduled' => ['Jadwal diubah admin', 'Jadwal booking Anda' . ($from ? " {$from}" : '') . " diubah menjadi {$schedule}."],
            default       => ['Info booking', "Booking {$schedule} diperbarui oleh admin."],
        };

        $date = $booking->booking_date ? Carbon::parse($booking->booking_date)->format('Y-m-d') : null;

        $customer->notify(new BookingActivity(
            $event,
            $title,
            $message,
            route('booking', $date ? ['date' => $date] : [], false),
            $booking->id,
        ));
    }

    /**
     * Contoh hasil: "Sab, 17 Okt 2026 09:00–10:00"
     */
    public static function schedule($date, $start, $end): string
    {
        $dateLabel = $date ? Carbon::parse($date)->locale('id')->translatedFormat('D, d M Y') : '-';
        $startTime = $start ? substr((string) $start, 0, 5) : '';
        $endTime   = $end ? substr((string) $end, 0, 5) : '';

        return trim($dateLabel . ' ' . $startTime . ($endTime ? '–' . $endTime : ''));
    }
}
