<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Pengaturan & pemeriksaan keamanan dari menu Settings > Keamanan.
 */
class Security
{
    public static function loginAttempts(): int
    {
        return max(3, (int) Setting::get('login_max_attempts', '5'));
    }

    public static function lockMinutes(): int
    {
        return max(1, (int) Setting::get('login_lock_minutes', '1'));
    }

    /** 0 = tidak keluar otomatis */
    public static function adminIdleMinutes(): int
    {
        return max(0, (int) Setting::get('admin_idle_minutes', '0'));
    }

    public static function requireAdmin2fa(): bool
    {
        return Setting::get('require_admin_2fa') === '1';
    }

    /** Nama route halaman pengaturan 2FA bawaan (Laravel Fortify / starter kit) */
    public static function twoFactorRoute(): ?string
    {
        foreach (['security.edit', 'two-factor.show', 'settings.security', 'security'] as $name) {
            if (Route::has($name)) {
                return $name;
            }
        }

        return null;
    }

    public static function usesDatabaseSessions(): bool
    {
        return config('session.driver') === 'database';
    }

    /** Backup otomatis lewat aplikasi hanya untuk database SQLite */
    public static function backupSupported(): bool
    {
        return config('database.connections.' . config('database.default') . '.driver') === 'sqlite';
    }

    public static function backupPath(): string
    {
        return storage_path('app/backups');
    }

    public static function backups(): array
    {
        if (! File::isDirectory(self::backupPath())) {
            return [];
        }

        return collect(File::files(self::backupPath()))
            ->filter(fn ($file) => str_starts_with($file->getFilename(), 'backup-'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'time' => $file->getMTime(),
            ])
            ->values()
            ->all();
    }

    /**
     * Pemeriksaan sebelum publish (hanya dibaca, diubah lewat .env di server).
     */
    public static function checks(): array
    {
        $appUrl = (string) config('app.url');

        return [
            [
                'ok'     => ! config('app.debug'),
                'title'  => 'Mode debug',
                'detail' => config('app.debug')
                    ? 'APP_DEBUG masih true. Ubah menjadi false di .env agar pesan error tidak memperlihatkan isi kode.'
                    : 'APP_DEBUG sudah false.',
            ],
            [
                'ok'     => app()->environment('production'),
                'title'  => 'Lingkungan aplikasi',
                'detail' => 'APP_ENV = ' . app()->environment() . (app()->environment('production') ? '.' : '. Ubah menjadi production saat website online.'),
            ],
            [
                'ok'     => str_starts_with($appUrl, 'https://') || request()->secure(),
                'title'  => 'HTTPS',
                'detail' => str_starts_with($appUrl, 'https://') || request()->secure()
                    ? 'Website memakai HTTPS.'
                    : 'Website masih memakai http (' . $appUrl . '). Pasang sertifikat SSL dan ubah APP_URL menjadi https://.',
            ],
            [
                'ok'     => in_array(MustVerifyEmail::class, class_implements(User::class), true),
                'title'  => 'Verifikasi email customer',
                'detail' => 'Akun baru wajib verifikasi email sebelum bisa booking.',
            ],
            [
                'ok'     => true,
                'title'  => 'Proteksi form (CSRF)',
                'detail' => 'Semua form dilindungi token CSRF bawaan Laravel.',
            ],
        ];
    }
}
