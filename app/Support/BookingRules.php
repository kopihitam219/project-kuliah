<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * Aturan booking & pembayaran yang diatur admin di menu Settings.
 * Nilai bawaan = aturan lama, jadi perilaku tidak berubah sebelum admin mengubahnya.
 */
class BookingRules
{
    public const DEFAULTS = [
        'open_time'            => '07:00',
        'close_time'           => '20:00',
        'slot_minutes'         => '60',
        'min_minutes'          => '30',
        'cancel_days'          => '1',
        'max_active'           => '0',
        'auto_approve_paid'    => '0',
        'price_per_hour'       => '900000',
        'payment_expiry_hours' => '24',
        'require_paid'         => '0',
        'payment_mode'         => 'demo',
    ];

    private static function value(string $key): string
    {
        return (string) Setting::get($key, self::DEFAULTS[$key]);
    }

    /* ------------------------- Jadwal ------------------------- */

    public static function openTime(): string
    {
        return self::value('open_time');
    }

    public static function closeTime(): string
    {
        return self::value('close_time');
    }

    public static function slotMinutes(): int
    {
        return max(30, (int) self::value('slot_minutes'));
    }

    public static function minMinutes(): int
    {
        return max(30, (int) self::value('min_minutes'));
    }

    /** 0 = sampai hari H, 1 = H-1, 2 = H-2, dst. */
    public static function cancelDays(): int
    {
        return max(0, (int) self::value('cancel_days'));
    }

    /** 0 = tanpa batas */
    public static function maxActive(): int
    {
        return max(0, (int) self::value('max_active'));
    }

    public static function autoApprovePaid(): bool
    {
        return self::value('auto_approve_paid') === '1';
    }

    /**
     * Apakah booking pada tanggal ini masih boleh dibatalkan / dijadwal ulang.
     */
    public static function canModifyDate($date): bool
    {
        return Carbon::parse($date)->startOfDay()->gte(today()->addDays(self::cancelDays()));
    }

    /** Tanggal paling awal untuk jadwal baru saat customer reschedule. */
    public static function minRescheduleDate(): \Carbon\CarbonInterface
    {
        return today()->addDays(max(1, self::cancelDays()));
    }

    public static function cancelRuleText(): string
    {
        $days = self::cancelDays();

        return $days === 0
            ? 'Pembatalan dan perubahan jadwal bisa dilakukan sampai hari lesson.'
            : "Pembatalan dan perubahan jadwal hanya bisa dilakukan paling lambat H-{$days}.";
    }

    /* ------------------------ Pembayaran ------------------------ */

    public static function pricePerHour(): int
    {
        return max(0, (int) self::value('price_per_hour'));
    }

    public static function paymentExpiryHours(): int
    {
        return max(1, (int) self::value('payment_expiry_hours'));
    }

    public static function requirePaidBeforeApprove(): bool
    {
        return self::value('require_paid') === '1';
    }

    /**
     * demo = simulasi, pembayaran langsung lunas tanpa uang sungguhan.
     * live = QRIS & rekening asli, setiap pembayaran diverifikasi admin.
     */
    public static function paymentMode(): string
    {
        return self::value('payment_mode') === 'live' ? 'live' : 'demo';
    }

    public static function isDemoPayment(): bool
    {
        return self::paymentMode() === 'demo';
    }
}
