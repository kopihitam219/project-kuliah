<?php

namespace App\Models;

use App\Support\BookingRules;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Payment extends Model
{
    /** Harga bawaan jika belum diatur di Settings */
    public const PRICE_PER_HOUR = 900000;

    /** Rekening contoh untuk mode demo (bukan rekening sungguhan) */
    public const DEMO_ACCOUNTS = [
        'mandiri' => ['account' => '1230004567890', 'holder' => 'GOLF BOOKING LESSON (DEMO)'],
        'bca'     => ['account' => '0001234567',    'holder' => 'GOLF BOOKING LESSON (DEMO)'],
    ];

    /** Definisi dasar metode pembayaran */
    public const METHODS = [
        'qris' => [
            'label' => 'QRIS',
            'desc'  => 'Scan dengan aplikasi e-wallet atau m-banking apa pun',
        ],
        'mandiri' => [
            'label' => 'Bank Mandiri',
            'desc'  => 'Transfer ke rekening Bank Mandiri',
        ],
        'bca' => [
            'label' => 'Bank BCA',
            'desc'  => 'Transfer ke rekening Bank BCA',
        ],
    ];

    public const STATUSES = [
        'unpaid'    => 'Belum dibayar',
        'pending'   => 'Menunggu pembayaran',
        'verifying' => 'Menunggu verifikasi',
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

    /* ---------------------------------------------------------------
     | PENGATURAN DARI SETTINGS
     * --------------------------------------------------------------- */

    public static function pricePerHour(): int
    {
        return class_exists(BookingRules::class) ? BookingRules::pricePerHour() : self::PRICE_PER_HOUR;
    }

    /**
     * Konfigurasi lengkap satu metode (dipakai juga untuk metode yang sudah dinonaktifkan).
     */
    public static function methodConfig(string $key): ?array
    {
        if (! isset(self::METHODS[$key])) {
            return null;
        }

        $config = self::METHODS[$key] + ['key' => $key];

        if ($key === 'qris') {
            $image = Setting::get('qris_image');

            return $config + [
                'type'     => 'qris',
                'image'    => $image ? Storage::disk('public')->url($image) : null,
                'merchant' => Setting::get('qris_merchant', 'GOLF BOOKING LESSON'),
                'nmid'     => Setting::get('qris_nmid'),
                'real'     => ! BookingRules::isDemoPayment(),
            ];
        }

        // Bank: transfer ke nomor rekening
        $demo = BookingRules::isDemoPayment();

        $config['type']    = 'transfer';
        $config['account'] = Setting::get("{$key}_account");
        $config['holder']  = Setting::get("{$key}_holder");
        $config['real']    = ! $demo;

        // Mode demo: pakai rekening contoh jika belum diisi
        if ($demo && (! $config['account'] || ! $config['holder'])) {
            $config['account'] = self::DEMO_ACCOUNTS[$key]['account'];
            $config['holder']  = self::DEMO_ACCOUNTS[$key]['holder'];
        }

        return $config;
    }

    /**
     * Metode yang aktif (ditampilkan ke customer).
     * Bank hanya tampil jika nomor rekening & atas nama sudah diisi di Settings.
     */
    public static function methods(): array
    {
        $methods = [];

        foreach (array_keys(self::METHODS) as $key) {
            if (Setting::get("pay_{$key}_enabled", '1') !== '1') {
                continue;
            }

            $config = self::methodConfig($key);

            if ($config['type'] === 'transfer' && (! $config['account'] || ! $config['holder'])) {
                continue;
            }

            $methods[$key] = $config;
        }

        return $methods;
    }

    /**
     * Mode live: pembayaran QRIS / transfer perlu dicek admin sebelum dianggap lunas.
     * Mode demo: langsung lunas (simulasi).
     */
    public function needsVerification(): bool
    {
        return (bool) (self::methodConfig((string) $this->method)['real'] ?? false);
    }

    /* ---------------------------------------------------------------
     | PEMBAYARAN BOOKING
     * --------------------------------------------------------------- */

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
                'amount'           => (int) round($minutes / 60 * self::pricePerHour()),
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
