<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    private const CACHE_KEY = 'app_settings';

    protected $fillable = ['key', 'value'];

    /**
     * Semua pengaturan (disimpan di cache supaya tidak query setiap halaman).
     */
    public static function values(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
        } catch (\Throwable $e) {
            // Tabel belum ada (sebelum migrate) atau database bermasalah: pakai nilai bawaan
            return [];
        }
    }

    /**
     * Ambil satu pengaturan. Nilai kosong dianggap belum diatur.
     */
    public static function get(string $key, $default = null)
    {
        $value = static::values()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * Simpan beberapa pengaturan sekaligus, lalu bersihkan cache.
     */
    public static function put(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
