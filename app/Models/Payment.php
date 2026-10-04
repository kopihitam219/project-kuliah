<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** Harga lesson per jam (Rupiah) */
    public const PRICE_PER_HOUR = 900000;

    /** Batas waktu pembayaran setelah memilih metode (jam) */
    public const EXPIRES_IN_HOURS = 24;

    /** Metode pembayaran (DUMMY) */
    public const METHODS = [
        'qris' => [
            'label'  => 'QRIS',
            'desc'   => 'Scan dengan aplikasi e-wallet atau m-banking apa pun',
            'prefix' => null,
        ],
        'mandiri' => [
            'label'  => 'Bank Mandiri',
            'desc'   => 'Transfer via Mandiri Virtual Account',
            'prefix' => '89508',
        ],
        'bca' => [
            'label'  => 'Bank BCA',
            'desc'   => 'Transfer via BCA Virtual Account',
            'prefix' => '39358',
        ],
    ];

    public const STATUSES = [
        'unpaid'    => 'Belum dibayar',
        'pending'   => 'Menunggu pembayaran',
        'paid'      => 'Lunas',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = [
        'booking_id',
        'user_id',
        'reference',
        'duration_minutes',
        'amount',
        'method',
        'va_number',
        'status',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'amount'           => 'integer',
        'duration_minutes' => 'integer',
        'expires_at'       => 'datetime',
        'paid_at'          => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Ambil pembayaran booking, atau buat baru jika belum ada.
     * Harga dikunci saat pembayaran pertama kali dibuat.
     */
    public static function forBooking(Booking $booking): self
    {
        $minutes = Carbon::parse($booking->start_time)->diffInMinutes(Carbon::parse($booking->end_time));
        $minutes = max(30, (int) abs($minutes));

        return static::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'user_id'          => $booking->user_id,
                'reference'        => 'GBL-' . now()->format('Ymd') . '-' . str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
                'duration_minutes' => $minutes,
                'amount'           => (int) round($minutes / 60 * self::PRICE_PER_HOUR),
                'status'           => 'unpaid',
            ]
        );
    }

    /**
     * Jika batas waktu habis, kembalikan ke "belum dibayar" agar customer bisa memilih metode lagi.
     */
    public function releaseIfExpired(): void
    {
        if ($this->status === 'pending' && $this->expires_at && $this->expires_at->isPast()) {
            $this->update([
                'status'     => 'unpaid',
                'method'     => null,
                'va_number'  => null,
                'expires_at' => null,
            ]);
        }
    }

    public static function formatRupiah(int $amount): string
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }

    public function getAmountLabelAttribute(): string
    {
        return self::formatRupiah($this->amount);
    }

    public function getMethodLabelAttribute(): ?string
    {
        return self::METHODS[$this->method]['label'] ?? null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getDurationLabelAttribute(): string
    {
        $hours   = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        return trim(($hours ? "{$hours} jam " : '') . ($minutes ? "{$minutes} menit" : ''));
    }
}
