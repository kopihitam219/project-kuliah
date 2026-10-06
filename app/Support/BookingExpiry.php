<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

/**
 * Booking online yang belum dibayar dalam batas waktu (default 30 menit sejak
 * booking dibuat) dinyatakan GAGAL: status dibatalkan, slot jam dibuka lagi,
 * customer & admin mendapat notifikasi.
 *
 * Tidak termasuk: booking offline, pembayaran yang sudah lunas, sedang dicek
 * admin (bukti sudah dikirim), atau memilih bayar cash.
 */
class BookingExpiry
{
    /** Batas waktu bayar untuk sebuah booking */
    public static function deadline(Booking $booking): ?CarbonInterface
    {
        return $booking->created_at?->copy()->addMinutes(BookingRules::paymentDeadlineMinutes());
    }

    /** Apakah booking ini masih menunggu pembayaran (berlaku hitung mundur) */
    public static function awaitingPayment(Booking $booking, ?Payment $payment = null): bool
    {
        if ($booking->status !== 'pending' || $booking->user_id === null) {
            return false;
        }

        if (($booking->getAttributes()['source'] ?? null) === 'offline') {
            return false;
        }

        $payment ??= Payment::where('booking_id', $booking->id)->first();

        return ! $payment || in_array($payment->status, ['unpaid', 'pending'], true);
    }

    /**
     * Gagalkan semua booking yang lewat batas waktu bayar.
     * Dipanggil otomatis paling sering sekali per menit.
     */
    public static function sweep(bool $force = false): int
    {
        if (! $force && ! Cache::add('booking-expiry-sweep', 1, 60)) {
            return 0;
        }

        if (! Schema::hasColumn('bookings', 'expired_at')) {
            return 0;
        }

        $cutoff = now()->subMinutes(BookingRules::paymentDeadlineMinutes());

        $candidates = Booking::query()
            ->where('status', 'pending')
            ->whereNotNull('user_id')
            ->where(fn ($q) => $q->whereNull('source')->orWhere('source', '!=', 'offline'))
            ->where('created_at', '<=', $cutoff)
            ->get();

        $count = 0;

        foreach ($candidates as $booking) {
            if (self::expire($booking)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Gagalkan satu booking jika memang sudah lewat batas waktu.
     */
    public static function expireIfDue(Booking $booking): bool
    {
        $deadline = self::deadline($booking);

        if (! $deadline || $deadline->isFuture()) {
            return false;
        }

        return self::expire($booking);
    }

    private static function expire(Booking $booking): bool
    {
        $payment = Payment::where('booking_id', $booking->id)->first();

        if (! self::awaitingPayment($booking, $payment)) {
            return false;
        }

        DB::transaction(function () use ($booking, $payment) {
            $booking->forceFill([
                'status'     => 'cancelled',
                'expired_at' => now(),
            ])->saveQuietly();

            if ($payment) {
                $payment->forceFill([
                    'status'     => 'cancelled',
                    'expires_at' => null,
                ])->saveQuietly();
            }
        });

        self::notify($booking->fresh());

        return true;
    }

    private static function notify(Booking $booking): void
    {
        if (! class_exists(\App\Notifications\BookingActivity::class)) {
            return;
        }

        try {
            $schedule = $booking->booking_date->locale('id')->translatedFormat('D, d M Y')
                . ' ' . substr($booking->start_time, 0, 5) . '–' . substr($booking->end_time, 0, 5);
            $minutes  = BookingRules::paymentDeadlineMinutes();
            $lesson   = method_exists($booking, 'isCourse') ? $booking->lesson_label : 'Lesson';
            $customer = $booking->user;

            if ($customer) {
                $customer->notify(new \App\Notifications\BookingActivity(
                    'cancelled',
                    'Booking gagal: waktu bayar habis',
                    "Booking {$lesson} {$schedule} dibatalkan otomatis karena belum dibayar dalam {$minutes} menit. Silakan booking ulang jika jadwal masih tersedia.",
                    route('booking', ['date' => $booking->booking_date->format('Y-m-d')], false),
                    $booking->id,
                ));
            }

            $admins = User::where('role', 'admin')->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new \App\Notifications\BookingActivity(
                    'cancelled',
                    'Booking gagal (tidak dibayar)',
                    ($customer?->name ?? 'Customer') . " tidak membayar booking {$lesson} {$schedule} dalam {$minutes} menit. Booking dibatalkan otomatis dan jadwal dibuka kembali.",
                    route('admin.dashboard', [], false) . '#booking-list',
                    $booking->id,
                ));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
