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
        'course_enabled'       => '1',
        'course_start'         => '07:00',
        'course_end'           => '12:00',
        'course_price'         => '3300000',
        'course_location'      => 'Lapangan golf (dikonfirmasi admin)',
        'course_note'          => 'Harga belum termasuk caddy fee, green fee, dan tip.',
    ];

    /** Jenis lesson yang bisa dipilih customer */
    public const LESSON_TYPES = [
        'driving' => 'Lesson Driving Range',
        'course'  => 'Course Lesson',
    ];

    /** Cache nama lapangan (id => nama) untuk satu request */
    private static ?array $locationNames = null;

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

    /* ---------------------- Jenis lesson ---------------------- */

    public static function courseEnabled(): bool
    {
        return self::value('course_enabled') === '1';
    }

    public static function courseStart(): string
    {
        return self::value('course_start');
    }

    public static function courseEnd(): string
    {
        return self::value('course_end');
    }

    public static function coursePrice(): int
    {
        return max(0, (int) self::value('course_price'));
    }

    public static function courseLocation(): string
    {
        return self::value('course_location');
    }

    /** Catatan harga Course Lesson (caddy fee, green fee, tip) */
    public static function courseNote(): string
    {
        return trim(self::value('course_note'));
    }

    public static function lessonTypes(): array
    {
        return self::courseEnabled() ? self::LESSON_TYPES : ['driving' => self::LESSON_TYPES['driving']];
    }

    public static function lessonLabel(?string $type): string
    {
        return self::LESSON_TYPES[$type ?: 'driving'] ?? self::LESSON_TYPES['driving'];
    }

    /** Nama lapangan driving range dari menu Contact > Lokasi */
    public static function locationName($id): ?string
    {
        if (! $id) {
            return null;
        }

        if (self::$locationNames === null) {
            try {
                self::$locationNames = \App\Models\ContactLocation::query()->pluck('name', 'id')->all();
            } catch (\Throwable $e) {
                self::$locationNames = [];
            }
        }

        return self::$locationNames[$id] ?? null;
    }

    /** Lapangan yang ditampilkan untuk sebuah booking */
    public static function placeFor(?string $type, $locationId, ?string $venue = null): ?string
    {
        if ($type === 'course') {
            return $venue ? trim($venue) : 'Lapangan golf pilihan customer';
        }

        return self::locationName($locationId);
    }

    /** Lapangan aktif untuk dipilih customer */
    public static function activeLocations()
    {
        try {
            return \App\Models\ContactLocation::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'area']);
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
