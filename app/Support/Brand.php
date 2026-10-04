<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Identitas & tampilan aplikasi yang diatur admin di menu Settings.
 * Semua method punya nilai bawaan, jadi aman dipakai walau Settings belum diisi.
 */
class Brand
{
    public const DEFAULT_NAME       = 'Golf Booking Lesson';
    public const DEFAULT_TAGLINE    = 'Improve your swing. Enjoy your game.';
    public const DEFAULT_BACKGROUND = 'images/background.golf.jpeg';

    public const HERO_DEFAULTS = [
        'hero_label'     => 'GOLF BOOKING LESSON',
        'hero_title_1'   => 'FROM FIRST SWING',
        'hero_title_2'   => 'TO',
        'hero_highlight' => 'CHAMPIONSHIP',
        'hero_text'      => 'Tingkatkan permainan golf Anda bersama instruktur profesional. Program latihan dirancang untuk membantu Anda berkembang dari pemula hingga level kompetitif.',
    ];

    public static function name(): string
    {
        return Setting::get('site_name', self::DEFAULT_NAME);
    }

    public static function tagline(): string
    {
        return Setting::get('tagline', self::DEFAULT_TAGLINE);
    }

    /**
     * Nama dipecah untuk tampilan logo: kata tengah berwarna hijau.
     * "Golf Booking Lesson" -> ['Golf', 'Booking', 'Lesson']
     */
    public static function nameParts(): array
    {
        $words = preg_split('/\s+/', trim(self::name()), -1, PREG_SPLIT_NO_EMPTY);

        return match (true) {
            count($words) >= 3 => [$words[0], implode(' ', array_slice($words, 1, -1)), end($words)],
            count($words) === 2 => [$words[0], $words[1], ''],
            default             => [$words[0] ?? self::DEFAULT_NAME, '', ''],
        };
    }

    public static function logoUrl(): ?string
    {
        return self::fileUrl(Setting::get('logo'));
    }

    public static function faviconUrl(): ?string
    {
        return self::fileUrl(Setting::get('favicon'));
    }

    /**
     * Background per area: public | auth | admin.
     */
    public static function background(string $area = 'public'): string
    {
        return self::fileUrl(Setting::get('bg_' . $area)) ?? asset(self::DEFAULT_BACKGROUND);
    }

    public static function hero(): array
    {
        $hero = [];

        foreach (self::HERO_DEFAULTS as $key => $default) {
            $hero[$key] = Setting::get($key, $default);
        }

        return $hero;
    }

    public static function maintenance(): bool
    {
        return Setting::get('maintenance') === '1';
    }

    public static function maintenanceMessage(): string
    {
        return Setting::get('maintenance_message', 'Kami sedang melakukan perbaikan sistem. Silakan kembali beberapa saat lagi.');
    }

    private static function fileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
