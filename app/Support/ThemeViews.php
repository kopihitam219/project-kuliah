<?php

namespace App\Support;

/**
 * Mencatat view Blade yang dirender pada satu request,
 * supaya CSS tema terang yang sesuai bisa dipasang (public/css/light/*.css).
 */
class ThemeViews
{
    /** @var array<string, true> */
    private static array $views = [];

    public static function add(string $name): void
    {
        self::$views[$name] = true;
    }

    /** @return array<int, string> */
    public static function all(): array
    {
        return array_keys(self::$views);
    }

    public static function reset(): void
    {
        self::$views = [];
    }
}
