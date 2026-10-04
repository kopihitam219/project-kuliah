<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSetting extends Model
{
    protected $fillable = [
        'description',
        'whatsapp',
        'email',
        'opening_hours',
    ];

    /**
     * Mengambil pengaturan kontak (dibuat otomatis jika belum ada).
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'description'   => 'Ada pertanyaan soal program, jadwal lesson, atau event? Pilih lokasi latihan terdekat dan hubungi kami.',
            'whatsapp'      => '0858 8680 3126',
            'email'         => 'info@golfbookinglesson.com',
            'opening_hours' => 'Senin – Minggu, 08:00 – 18:00',
        ]);
    }

    /**
     * Nomor WhatsApp format internasional untuk link wa.me
     * contoh: "0858 8680 3126" -> "6285886803126"
     */
    public function getWhatsappNumberAttribute(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->whatsapp);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return $digits;
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        return $this->whatsapp_number ? 'https://wa.me/' . $this->whatsapp_number : null;
    }
}
