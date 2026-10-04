<?php
/**
 * ======================================================================
 *  SETTINGS TAHAP 1 - GOLF BOOKING LESSON
 * ======================================================================
 *  Menu Settings di admin dengan tab:
 *   - Umum & Branding : nama usaha, slogan, logo, favicon, mode maintenance
 *   - Tampilan        : background (publik / login / admin), teks halaman Home
 *   - Akun Admin      : ubah nama & email, ganti password
 *  Tab Booking, Pembayaran, WhatsApp, Keamanan menyusul (Tahap 2 & 3).
 *
 *  Cara pakai (dari folder root proyek):
 *      php install-settings.php
 *
 *  File lama di-backup jadi namafile.bak-TANGGAL
 * ======================================================================
 */

if (PHP_SAPI !== 'cli') {
    exit("Jalankan lewat terminal: php install-settings.php\n");
}

$root = getcwd();

if (! file_exists($root . '/artisan')) {
    fwrite(STDERR, "X File 'artisan' tidak ditemukan.\n  Jalankan dari folder root proyek Laravel (golf-booking-lesson).\n");
    exit(1);
}

$stamp    = date('Ymd_His');
$warnings = [];
$report   = [];

function out(string $msg = ''): void
{
    echo $msg . PHP_EOL;
}

function backup(string $path): void
{
    global $stamp;
    if (file_exists($path)) {
        copy($path, $path . '.bak-' . $stamp);
    }
}

function eolOf(string $content): string
{
    return strpos($content, "\r\n") !== false ? "\r\n" : "\n";
}

/* ----------------------------------------------------------------------
 | ISI FILE
 * ---------------------------------------------------------------------- */
$files = [];

$files['database/migrations/2026_10_04_030000_create_settings_table.php'] = <<<'SET_EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan aplikasi dalam bentuk key -> value.
     */
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            return;
        }

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
SET_EOF;

$files['app/Models/Setting.php'] = <<<'SET_EOF'
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
SET_EOF;

$files['app/Support/Brand.php'] = <<<'SET_EOF'
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
SET_EOF;

$files['app/Http/Middleware/SiteMaintenance.php'] = <<<'SET_EOF'
<?php

namespace App\Http\Middleware;

use App\Support\Brand;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mode maintenance dari menu Settings.
 * Visitor & customer melihat halaman "sedang perbaikan", admin tetap bisa masuk.
 */
class SiteMaintenance
{
    /** Alamat yang tetap bisa dibuka saat maintenance (login admin, dsb.) */
    private const ALLOWED = [
        'login', 'logout', 'forgot-password', 'reset-password*', 'two-factor-challenge',
        'email/*', 'user/*', 'livewire*', 'up', 'storage/*', 'admin*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Brand::maintenance()) {
            return $next($request);
        }

        if ($request->user()?->role === 'admin' || $request->is(...self::ALLOWED)) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }
}
SET_EOF;

$files['app/Http/Controllers/AdminSettingController.php'] = <<<'SET_EOF'
<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    /** Tab yang sudah aktif (tahap 1). Tab lain tampil sebagai "segera hadir". */
    public const TABS = [
        'general'    => ['label' => 'Umum & Branding',       'ready' => true],
        'appearance' => ['label' => 'Tampilan',              'ready' => true],
        'booking'    => ['label' => 'Booking & Jadwal',      'ready' => false, 'stage' => 'Tahap 2'],
        'payment'    => ['label' => 'Pembayaran',            'ready' => false, 'stage' => 'Tahap 2'],
        'whatsapp'   => ['label' => 'Notifikasi & WhatsApp', 'ready' => false, 'stage' => 'Tahap 3'],
        'security'   => ['label' => 'Keamanan',              'ready' => false, 'stage' => 'Tahap 3'],
        'account'    => ['label' => 'Akun Admin',            'ready' => true],
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? $request->query('tab') : 'general';

        return view('admin.settings.index', [
            'tab'  => $tab,
            'tabs' => self::TABS,
            'user' => $request->user(),
        ]);
    }

    /* ---------------------------------------------------------------
     | UMUM & BRANDING
     * --------------------------------------------------------------- */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $request->validate([
            'site_name'           => ['required', 'string', 'max:60'],
            'tagline'             => ['nullable', 'string', 'max:120'],
            'logo'                => ['nullable', 'file', 'mimes:png,webp', 'max:1024'],
            'favicon'             => ['nullable', 'file', 'mimes:png', 'max:512'],
            'maintenance'         => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:300'],
        ], [
            'site_name.required' => 'Nama usaha wajib diisi.',
            'logo.mimes'         => 'Logo harus berupa PNG atau WEBP.',
            'logo.max'           => 'Ukuran logo maksimal 1 MB.',
            'favicon.mimes'      => 'Favicon harus berupa PNG.',
            'favicon.max'        => 'Ukuran favicon maksimal 512 KB.',
        ]);

        Setting::put([
            'site_name'           => trim($request->input('site_name')),
            'tagline'             => trim((string) $request->input('tagline')),
            'maintenance'         => $request->boolean('maintenance') ? '1' : '0',
            'maintenance_message' => trim((string) $request->input('maintenance_message')),
        ]);

        $this->handleImage($request, 'logo', 'logo', 'settings/brand');
        $this->handleImage($request, 'favicon', 'favicon', 'settings/brand');

        $message = 'Pengaturan umum disimpan.';

        if ($request->boolean('maintenance')) {
            $message .= ' Mode maintenance AKTIF: customer & visitor tidak bisa membuka website.';
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'general'])
            ->with($request->boolean('maintenance') ? 'error' : 'success', $message);
    }

    /* ---------------------------------------------------------------
     | TAMPILAN
     * --------------------------------------------------------------- */
    public function updateAppearance(Request $request): RedirectResponse
    {
        $image = ['nullable', 'file', 'mimes:jpg,jpeg,webp', 'max:3072'];

        $request->validate([
            'bg_public'      => $image,
            'bg_auth'        => $image,
            'bg_admin'       => $image,
            'hero_label'     => ['nullable', 'string', 'max:60'],
            'hero_title_1'   => ['nullable', 'string', 'max:60'],
            'hero_title_2'   => ['nullable', 'string', 'max:60'],
            'hero_highlight' => ['nullable', 'string', 'max:40'],
            'hero_text'      => ['nullable', 'string', 'max:300'],
        ], [
            '*.mimes' => 'Background harus berupa JPG atau WEBP.',
            '*.max'   => 'Ukuran background maksimal 3 MB.',
        ]);

        Setting::put($request->only(array_keys(Brand::HERO_DEFAULTS)));

        foreach (['bg_public', 'bg_auth', 'bg_admin'] as $key) {
            $this->handleImage($request, $key, $key, 'settings/backgrounds');
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => 'appearance'])
            ->with('success', 'Pengaturan tampilan disimpan.');
    }

    /* ---------------------------------------------------------------
     | AKUN ADMIN
     * --------------------------------------------------------------- */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ], [
            'name.required'                     => 'Nama wajib diisi.',
            'email.unique'                      => 'Email sudah dipakai akun lain.',
            'current_password.required'         => 'Masukkan password saat ini untuk menyimpan perubahan.',
            'current_password.current_password' => 'Password saat ini salah.',
        ]);

        $user->forceFill([
            'name'  => $request->input('name'),
            'email' => $request->input('email'),
        ])->save();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'account'])
            ->with('success', 'Profil admin diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required'         => 'Masukkan password saat ini.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.confirmed'                => 'Ulangi password baru tidak sama.',
            'password.min'                      => 'Password baru minimal 8 karakter.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $request->input('password')])->save();

        // Keluarkan sesi lain, tetap login di perangkat ini
        Auth::logoutOtherDevices($request->input('password'));
        $request->session()->regenerate();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'account'])
            ->with('success', 'Password berhasil diganti.');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */

    /**
     * Upload gambar baru atau hapus (kembali ke bawaan) jika "hapus_<field>" dicentang.
     */
    private function handleImage(Request $request, string $field, string $settingKey, string $folder): void
    {
        $oldPath = Setting::get($settingKey);

        if ($request->hasFile($field)) {
            $path = $request->file($field)->store($folder, 'public');
            Setting::put([$settingKey => $path]);
            $this->deleteFile($oldPath);

            return;
        }

        if ($request->boolean('remove_' . $field) && $oldPath) {
            Setting::put([$settingKey => null]);
            $this->deleteFile($oldPath);
        }
    }

    private function deleteFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'settings/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
SET_EOF;

$files['resources/views/partials/brand-head.blade.php'] = <<<'SET_EOF'
{{-- Favicon & deskripsi dari menu Settings. Taruh di dalam <head>. --}}
@if ($brandFavicon = \App\Support\Brand::faviconUrl())
    <link rel="icon" type="image/png" href="{{ $brandFavicon }}">
@endif
<meta name="description" content="{{ \App\Support\Brand::name() }} - {{ \App\Support\Brand::tagline() }}">
SET_EOF;

$files['resources/views/partials/brand-logo.blade.php'] = <<<'SET_EOF'
{{--
    Logo + nama usaha dari menu Settings.
    Variabel opsional: $iconClass, $textClass, $accentClass
--}}
@php
    [$brandFirst, $brandMiddle, $brandLast] = \App\Support\Brand::nameParts();
    $brandLogo = \App\Support\Brand::logoUrl();
@endphp

@if ($brandLogo)
    <img src="{{ $brandLogo }}" alt="" class="{{ $iconClass ?? '' }}" style="height: 34px; width: auto; max-width: 120px; object-fit: contain">
@else
    <span class="{{ $iconClass ?? '' }}" aria-hidden="true">⛳</span>
@endif
<span class="{{ $textClass ?? '' }}">{{ $brandFirst }}@if ($brandMiddle) <span class="{{ $accentClass ?? '' }}">{{ $brandMiddle }}</span>@endif{{ $brandLast ? ' ' . $brandLast : '' }}</span>
SET_EOF;

$files['resources/views/partials/site-navbar.blade.php'] = <<<'SET_EOF'
{{--
    Navbar bersama untuk halaman visitor & customer.
    Pakai: @include('partials.site-navbar')
--}}
@php
    $snUser       = auth()->user();
    $snIsCustomer = $snUser && $snUser->role === 'customer';
    $snIsAdmin    = $snUser && $snUser->role === 'admin';

    $snHomeUrl    = $snIsCustomer ? route('dashboard') : route('home');
    $snBookingUrl = $snIsCustomer ? route('booking') : route('login');

    $snLinks = [
        ['label' => 'Home',    'url' => $snHomeUrl,        'active' => request()->routeIs('home', 'dashboard')],
        ['label' => 'Program', 'url' => route('program'),  'active' => request()->routeIs('program')],
        ['label' => 'Galeri',  'url' => route('galeri'),   'active' => request()->routeIs('galeri')],
        ['label' => 'Event',   'url' => route('event'),    'active' => request()->routeIs('event')],
        ['label' => 'Contact', 'url' => route('contact'),  'active' => request()->routeIs('contact')],
    ];

    $snBookingActive = request()->routeIs('booking', 'payment', 'payment.*');
    $snNow           = now();
@endphp

@once
    <style>
        .sn-nav {
            position: sticky;
            top: 0;
            z-index: 200;
            flex: 0 0 auto;
            width: 100%;
            min-height: 68px;
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 0 32px;
            background: rgba(3, 13, 9, .96);
            border-bottom: 1px solid rgba(156, 255, 0, .14);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            font-family: Arial, Helvetica, sans-serif;
            color: #ffffff;
        }

        .sn-nav *, .sn-nav *::before, .sn-nav *::after { box-sizing: border-box; }
        .sn-nav a { text-decoration: none; }

        /* Logo */
        .sn-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
            font-size: 21px;
            font-weight: 900;
            letter-spacing: -.6px;
            white-space: nowrap;
        }

        .sn-brand-icon { font-size: 28px; line-height: 1; }
        .sn-brand-text span { color: #9cff38; }

        /* Menu */
        .sn-links {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .sn-link {
            position: relative;
            padding: 9px 12px;
            border-radius: 8px;
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            font-weight: 700;
            transition: color .2s ease, background .2s ease;
        }

        .sn-link:hover { color: #9cff38; background: rgba(156, 255, 0, .06); }
        .sn-link.active { color: #9cff38; }

        .sn-link.active::after {
            content: "";
            position: absolute;
            left: 12px;
            right: 12px;
            bottom: 2px;
            height: 2px;
            border-radius: 2px;
            background: #9cff38;
        }

        .sn-booking {
            margin-left: 8px;
            padding: 9px 18px;
            border: 1px solid #9cff38;
            border-radius: 8px;
            color: #9cff38;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .6px;
            transition: background .2s ease, color .2s ease;
        }

        .sn-booking:hover,
        .sn-booking.active { background: #9cff38; color: #07120c; }

        /* Kanan */
        .sn-right { display: flex; align-items: center; gap: 12px; }

        .sn-clock {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            padding: 6px 12px;
            border: 1px solid rgba(156, 255, 0, .14);
            border-radius: 9px;
            background: rgba(255, 255, 255, .03);
            line-height: 1.25;
            white-space: nowrap;
        }

        .sn-clock-time { color: #9cff38; font-size: 14px; font-weight: 900; font-variant-numeric: tabular-nums; }
        .sn-clock-date { color: rgba(255, 255, 255, .6); font-size: 10px; font-weight: 700; }

        .sn-user {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            max-width: 160px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 9px;
            background: rgba(255, 255, 255, .04);
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sn-user-dot { width: 9px; height: 9px; flex: 0 0 9px; border-radius: 50%; background: #9cff38; }

        .sn-btn {
            height: 36px;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(156, 255, 0, .55);
            border-radius: 9px;
            background: transparent;
            color: #ffffff;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: background .2s ease, color .2s ease;
        }

        .sn-btn:hover { background: #9cff38; color: #07120c; }
        .sn-btn.lime { color: #9cff38; }
        .sn-btn.lime:hover { color: #07120c; }
        .sn-logout-form { margin: 0; }

        .sn-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 9px;
            background: transparent;
            color: #ffffff;
            font-size: 20px;
            cursor: pointer;
        }

        /* Responsif */
        @media (max-width: 1360px) {
            .sn-clock-date { display: none; }
        }

        @media (max-width: 1180px) {
            .sn-nav { padding: 0 20px; gap: 12px; }
            .sn-link { padding: 9px 9px; }
            .sn-user { max-width: 120px; }
        }

        @media (max-width: 1024px) {
            .sn-nav { flex-wrap: wrap; padding: 10px 16px; }
            .sn-toggle { display: inline-flex; align-items: center; justify-content: center; order: 3; }
            .sn-right { margin-left: auto; order: 2; }

            .sn-links {
                order: 4;
                flex-basis: 100%;
                display: none;
                flex-direction: column;
                align-items: stretch;
                gap: 2px;
                padding: 8px 0 6px;
                border-top: 1px solid rgba(255, 255, 255, .08);
            }

            .sn-nav.open .sn-links { display: flex; }
            .sn-link.active::after { display: none; }
            .sn-link.active { background: rgba(156, 255, 0, .08); }
            .sn-booking { margin: 6px 0 0; text-align: center; }
        }

        @media (max-width: 640px) {
            .sn-brand { font-size: 17px; }
            .sn-brand-icon { font-size: 22px; }
            .sn-user, .sn-clock { display: none; }
            .sn-btn { padding: 0 12px; }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /* Jam & tanggal real-time */
            const pad = (n) => String(n).padStart(2, '0');
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            function tick() {
                const now = new Date();
                document.querySelectorAll('[data-sn-time]').forEach(function (el) {
                    el.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds()) + ' WIB';
                });
                document.querySelectorAll('[data-sn-date]').forEach(function (el) {
                    el.textContent = days[now.getDay()] + ', ' + pad(now.getDate()) + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
                });
            }

            tick();
            setInterval(tick, 1000);

            /* Menu HP */
            document.querySelectorAll('.sn-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    const nav = button.closest('.sn-nav');
                    const open = nav.classList.toggle('open');
                    button.setAttribute('aria-expanded', String(open));
                    button.textContent = open ? '✕' : '☰';
                });
            });
        });
    </script>
@endonce

<nav class="sn-nav">

    <a href="{{ $snHomeUrl }}" class="sn-brand" aria-label="{{ \App\Support\Brand::name() }}">
        @include('partials.brand-logo', ['iconClass' => 'sn-brand-icon', 'textClass' => 'sn-brand-text'])
    </a>

    <div class="sn-links">
        @foreach ($snLinks as $link)
            <a href="{{ $link['url'] }}" class="sn-link {{ $link['active'] ? 'active' : '' }}">{{ $link['label'] }}</a>
        @endforeach

        <a href="{{ $snBookingUrl }}" class="sn-booking {{ $snBookingActive ? 'active' : '' }}">BOOKING</a>
    </div>

    <div class="sn-right">
        <div class="sn-clock" title="Waktu sekarang">
            <span class="sn-clock-time" data-sn-time>{{ $snNow->format('H:i:s') }} WIB</span>
            <span class="sn-clock-date" data-sn-date>{{ $snNow->locale('id')->translatedFormat('l, d M Y') }}</span>
        </div>

        @auth
            @includeIf('partials.notification-bell')

            @if ($snIsAdmin)
                <a href="{{ route('admin.dashboard') }}" class="sn-btn lime">ADMIN</a>
            @else
                <span class="sn-user"><span class="sn-user-dot"></span>Hi, {{ $snUser->name }}</span>

                <form method="POST" action="{{ route('logout') }}" class="sn-logout-form">
                    @csrf
                    <button type="submit" class="sn-btn">Logout</button>
                </form>
            @endif
        @else
            <a href="{{ route('login') }}" class="sn-btn lime">LOGIN</a>
        @endauth
    </div>

    <button type="button" class="sn-toggle" aria-label="Buka menu" aria-expanded="false">☰</button>

</nav>
SET_EOF;

$files['resources/views/maintenance.blade.php'] = <<<'SET_EOF'
@php
    use App\Support\Brand;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedang perbaikan | {{ Brand::name() }}</title>
    @include('partials.brand-head')

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
            background:
                linear-gradient(rgba(2, 13, 9, .88), rgba(2, 13, 9, .94)),
                url('{{ Brand::background('public') }}') center / cover no-repeat;
        }

        .card {
            width: 100%;
            max-width: 520px;
            padding: 36px 30px;
            text-align: center;
            border: 1px solid rgba(156, 255, 0, .25);
            border-radius: 16px;
            background: rgba(3, 15, 11, .78);
        }

        .brand { display: inline-flex; align-items: center; gap: 10px; font-size: 22px; font-weight: 900; }
        .brand-icon { font-size: 30px; }
        .brand .accent { color: #9cff38; }

        .icon {
            width: 64px;
            height: 64px;
            margin: 28px auto 18px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(245, 196, 0, .14);
            color: #ffc62d;
            font-size: 28px;
        }

        h1 { font-size: 26px; font-weight: 900; }
        p { margin-top: 10px; color: rgba(255, 255, 255, .75); font-size: 14px; line-height: 1.6; }
        small { display: block; margin-top: 22px; color: rgba(255, 255, 255, .5); font-size: 12px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">
            @include('partials.brand-logo', ['iconClass' => 'brand-icon', 'accentClass' => 'accent'])
        </div>

        <div class="icon" aria-hidden="true">⚙</div>

        <h1>Sedang dalam perbaikan</h1>
        <p>{{ Brand::maintenanceMessage() }}</p>

        <small>{{ Brand::tagline() }}</small>
    </main>
</body>
</html>
SET_EOF;

$files['resources/views/admin/layouts/panel.blade.php'] = <<<'SET_EOF'
@php
    $pendingCount = \App\Models\Booking::where('status', 'pending')->count();
    $adminName    = auth()->user()->name ?? 'Admin';

    $flashType    = session('error') ? 'error' : (session('success') ? 'success' : null);
    $flashMessage = session('error') ?? session('success');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Admin {{ \App\Support\Brand::name() }}</title>
    @include('partials.brand-head')

    <style>
        :root {
            --bg:          #020d09;
            --bg-deep:     #03150f;
            --panel:       rgba(1, 20, 13, .82);
            --lime:        #9cff38;
            --lime-strong: #8cf12c;
            --ink-dark:    #07120c;
            --text:        #ffffff;
            --text-soft:   rgba(255, 255, 255, .78);
            --text-muted:  rgba(255, 255, 255, .52);
            --line:        rgba(255, 255, 255, .08);
            --border:      rgba(156, 255, 0, .14);
            --radius:      11px;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; }
        body { overflow: hidden; background: var(--bg); color: var(--text); font-family: Arial, Helvetica, sans-serif; }
        button, input, select, textarea { font-family: inherit; }
        button { cursor: pointer; }
        [hidden] { display: none !important; }

        .thin-scroll::-webkit-scrollbar { width: 6px; }
        .thin-scroll::-webkit-scrollbar-thumb { background: rgba(156, 255, 0, .25); border-radius: 10px; }

        /* ---------- Layout ---------- */
        .admin-page {
            width: 100%; height: 100vh;
            display: grid;
            grid-template-columns: 250px 1fr;
            grid-template-rows: 64px 1fr;
            overflow: hidden;
            background: linear-gradient(135deg, #020d09 0%, #03150f 45%, #020b08 100%);
        }
        .admin-page.sidebar-toggled { grid-template-columns: 0 1fr; }
        .admin-page.sidebar-toggled .sidebar { display: none; }

        /* ---------- Topbar ---------- */
        .topbar {
            grid-column: 1 / -1;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 25px;
            background: rgba(1, 13, 9, .97);
            border-bottom: 1px solid rgba(156, 255, 0, .12);
        }
        .top-left, .top-right { display: flex; align-items: center; gap: 22px; }
        .brand { display: flex; align-items: center; gap: 8px; color: var(--text); text-decoration: none; font-size: 22px; font-weight: 900; letter-spacing: -.8px; }
        .brand-icon { font-size: 31px; line-height: 1; margin-right: 4px; }
        .brand .accent { color: var(--lime); }
        .menu-toggle { width: 34px; height: 34px; border: 0; background: transparent; color: #d8e2dd; font-size: 27px; }
        .admin-profile { display: flex; align-items: center; gap: 10px; }
        .admin-avatar {
            width: 42px; height: 42px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: var(--lime-strong); color: var(--ink-dark); font-size: 20px; font-weight: 900;
        }
        .admin-name strong { display: block; font-size: 14px; font-weight: 800; }
        .admin-name small  { display: block; margin-top: 4px; color: rgba(255, 255, 255, .48); font-size: 10px; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            grid-column: 1; grid-row: 2;
            height: calc(100vh - 64px); padding: 18px 13px; overflow: hidden;
            background: rgba(1, 12, 9, .98);
            border-right: 1px solid rgba(156, 255, 0, .08);
        }
        .sidebar-scroll { height: 100%; overflow-y: auto; }
        .sidebar-section { margin-bottom: 22px; }
        .sidebar-title { margin: 16px 10px 9px; color: var(--lime); font-size: 11px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }
        .sidebar-link {
            width: 100%; min-height: 42px;
            display: flex; align-items: center; gap: 13px;
            margin-bottom: 5px; padding: 0 13px;
            border: 0; border-radius: 9px; background: transparent;
            color: var(--text-soft); text-align: left; text-decoration: none;
            font-size: 13px; font-weight: 700; transition: .2s ease;
        }
        .sidebar-link:hover { background: rgba(156, 255, 0, .07); color: var(--lime); }
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(57, 139, 47, .46), rgba(44, 105, 39, .28));
            border: 1px solid rgba(156, 255, 0, .23);
            color: var(--text);
        }
        .sidebar-icon { width: 24px; text-align: center; color: #cbd8d1; font-size: 17px; }
        .sidebar-link.active .sidebar-icon { color: var(--lime); }
        .sidebar-badge {
            margin-left: auto; min-width: 22px; height: 22px; padding: 0 6px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; background: var(--lime); color: var(--ink-dark);
            font-size: 10px; font-weight: 900;
        }
        .sidebar-divider { height: 1px; margin: 20px 9px; background: var(--line); }

        /* ---------- Main ---------- */
        .main { grid-column: 2; grid-row: 2; min-width: 0; height: calc(100vh - 64px); padding: 22px 24px 20px; overflow: hidden; }
        .main-scroll { height: 100%; overflow: auto; padding-right: 2px; }

        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 18px; flex-wrap: wrap; }
        .page-head h1 { font-size: 29px; font-weight: 900; letter-spacing: -1px; }
        .page-head p  { margin-top: 7px; color: rgba(255, 255, 255, .64); font-size: 12px; max-width: 540px; line-height: 1.5; }
        .head-actions { display: flex; gap: 9px; flex-wrap: wrap; }

        .btn {
            height: 39px; padding: 0 16px;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            border-radius: 7px; border: 1px solid transparent;
            font-size: 11px; font-weight: 900; text-decoration: none; white-space: nowrap;
        }
        .btn-primary { background: var(--lime); color: var(--ink-dark); }
        .btn-primary:hover { background: var(--lime-strong); }
        .btn-primary:disabled { opacity: .6; cursor: wait; }
        .btn-outline { border-color: rgba(156, 255, 0, .55); background: rgba(156, 255, 0, .04); color: var(--lime); }
        .btn-outline:hover { background: rgba(156, 255, 0, .12); }
        .btn-ghost { border-color: var(--line); background: transparent; color: var(--text-soft); }
        .btn-ghost:hover { border-color: rgba(255, 255, 255, .25); }

        /* ---------- Tabs ---------- */
        .tabs { display: flex; gap: 8px; margin-bottom: 15px; flex-wrap: wrap; }
        .tab {
            padding: 9px 14px; border-radius: 999px;
            border: 1px solid var(--border); background: rgba(2, 20, 14, .75);
            color: var(--text-soft); text-decoration: none; font-size: 11px; font-weight: 800;
        }
        .tab span { margin-left: 6px; color: var(--text-muted); }
        .tab.active { background: var(--lime); border-color: var(--lime); color: var(--ink-dark); }
        .tab.active span { color: rgba(7, 18, 12, .6); }

        /* ---------- Grid kartu ---------- */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
        .item-card { display: flex; flex-direction: column; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; }
        .item-card.inactive .item-thumb > img,
        .item-card.inactive .item-thumb > video { opacity: .35; filter: grayscale(.6); }
        .item-thumb { position: relative; aspect-ratio: 16 / 10; background: #000; }
        .item-thumb img, .item-thumb video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .no-thumb { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 34px; }
        .play-icon {
            position: absolute; inset: 0; margin: auto;
            width: 46px; height: 46px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background: rgba(0, 0, 0, .6); color: var(--text); font-size: 16px; pointer-events: none;
        }
        .badges { position: absolute; top: 9px; left: 9px; right: 9px; display: flex; justify-content: space-between; gap: 6px; }
        .badge { padding: 4px 8px; border-radius: 999px; font-size: 8px; font-weight: 900; letter-spacing: .4px; text-transform: uppercase; }
        .badge.type     { background: rgba(0, 0, 0, .65); color: var(--text); }
        .badge.active   { background: rgba(67, 190, 77, .9); color: var(--ink-dark); }
        .badge.inactive { background: rgba(255, 255, 255, .85); color: var(--ink-dark); }
        .item-body { padding: 12px 13px 4px; flex: 1; }
        .item-body strong { display: block; font-size: 13px; font-weight: 900; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .item-body small  { display: block; margin-top: 4px; color: #79df48; font-size: 10px; font-weight: 700; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .item-meta { margin-top: 6px; color: rgba(255, 255, 255, .35); font-size: 9px; }
        .item-actions { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; padding: 10px 13px 13px; }
        .item-actions form { display: contents; }
        .item-actions a,
        .item-actions button {
            height: 31px; display: flex; align-items: center; justify-content: center;
            border-radius: 6px; border: 1px solid var(--line); background: transparent;
            color: var(--text-soft); font-size: 10px; font-weight: 800; text-decoration: none;
        }
        .item-actions a:hover, .item-actions button:hover { border-color: rgba(156, 255, 0, .4); color: var(--lime); }
        .item-actions .danger:hover { border-color: rgba(216, 35, 61, .7); color: #ff7a8c; }

        .empty-state { padding: 60px 20px; text-align: center; border: 1px dashed var(--border); border-radius: var(--radius); color: var(--text-muted); font-size: 13px; line-height: 1.8; }
        .empty-state .btn { margin-top: 12px; }

        /* ---------- Form ---------- */
        .form-card { max-width: 680px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); padding: 22px; }
        .field { margin-bottom: 16px; }
        .field > label, .field-label { display: block; margin-bottom: 6px; color: var(--text-soft); font-size: 11px; font-weight: 800; }
        .field-hint { margin-top: 5px; color: var(--text-muted); font-size: 10px; line-height: 1.4; }
        .field-error { margin-top: 5px; color: #ff8a8a; font-size: 10px; font-weight: 700; }
        .input {
            width: 100%; height: 39px; padding: 0 12px;
            border: 1px solid rgba(198, 231, 215, .16); border-radius: 7px; outline: none;
            background: rgba(0, 10, 7, .5); color: var(--text); font-size: 12px;
        }
        textarea.input { height: 90px; padding: 10px 12px; resize: vertical; line-height: 1.5; }
        .input:focus { border-color: rgba(156, 255, 0, .5); }
        .input.is-invalid { border-color: rgba(255, 90, 90, .6); }
        input[type="file"].input { height: auto; padding: 8px 10px; font-size: 11px; color: var(--text-soft); }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .segmented { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; padding: 4px; border: 1px solid var(--line); border-radius: 9px; }
        .segmented input { position: absolute; opacity: 0; pointer-events: none; }
        .segmented label { height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 6px; color: var(--text-soft); font-size: 11px; font-weight: 800; cursor: pointer; }
        .segmented input:checked + label { background: var(--lime); color: var(--ink-dark); }
        .segmented input:focus-visible + label { outline: 2px solid var(--lime); outline-offset: 2px; }

        .checkbox { display: flex; align-items: center; gap: 9px; height: 39px; color: var(--text-soft); font-size: 12px; cursor: pointer; }
        .checkbox input { width: 16px; height: 16px; accent-color: var(--lime); }

        .preview { margin-top: 9px; width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; border: 1px solid var(--line); }
        .current-file { margin-top: 7px; color: var(--text-muted); font-size: 10px; word-break: break-all; }

        .form-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 6px; padding-top: 16px; border-top: 1px solid var(--line); }

        .error-box { margin-bottom: 16px; padding: 11px 13px; border: 1px solid rgba(255, 90, 90, .5); border-radius: 8px; background: #2a0b10; color: #ff9a9a; font-size: 11px; line-height: 1.6; }
        .error-box ul { padding-left: 16px; }

        /* ---------- Event ---------- */
        .event-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
        .event-item { display: flex; flex-direction: column; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; }
        .event-item.inactive .event-poster img { opacity: .35; filter: grayscale(.6); }
        .event-poster { position: relative; aspect-ratio: 4 / 5; background: #000; }
        .event-poster img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .badge.open   { background: rgba(67, 190, 77, .9); color: var(--ink-dark); }
        .badge.closed { background: rgba(255, 255, 255, .85); color: var(--ink-dark); }
        .badge.full   { background: rgba(245, 174, 0, .95); color: var(--ink-dark); }
        .badge.past   { background: rgba(0, 0, 0, .7); color: rgba(255, 255, 255, .8); }
        .event-facts { margin-top: 8px; display: grid; gap: 4px; color: var(--text-muted); font-size: 10px; }
        .event-facts b { color: var(--text-soft); font-weight: 700; }
        .event-price { margin-top: 8px; color: var(--lime); font-size: 16px; font-weight: 900; }
        .field-row.three { grid-template-columns: 1fr 1fr 1fr; }
        select.input { cursor: pointer; }
        select.input option { background: #07150f; color: #fff; }
        .poster-preview { margin-top: 9px; width: 100%; max-width: 260px; border-radius: 8px; border: 1px solid var(--line); display: block; }
        @media (max-width: 760px) { .field-row.three { grid-template-columns: 1fr; } }

        /* ---------- Program ---------- */
        .program-grid-admin { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 14px; }
        .program-item { display: flex; flex-direction: column; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; }
        .program-item.inactive .program-banner { opacity: .35; filter: grayscale(.6); }
        .program-banner {
            position: relative; height: 110px;
            background-image: linear-gradient(to bottom, rgba(0, 0, 0, .02), rgba(4, 18, 11, .98)), var(--card-img);
            background-position: center, var(--card-pos, center);
            background-size: cover;
        }
        .program-level { color: var(--lime); font-size: 9px; font-weight: 800; letter-spacing: 2px; margin-bottom: 4px; }
        .program-desc { margin-top: 5px; color: var(--text-muted); font-size: 10px; line-height: 1.4; }
        .feature-list { list-style: none; margin-top: 8px; display: grid; gap: 4px; }
        .feature-list li { position: relative; padding-left: 11px; color: var(--text-soft); font-size: 10px; }
        .feature-list li::before { content: ""; position: absolute; left: 0; top: 5px; width: 4px; height: 4px; border-radius: 50%; background: var(--lime); }
        .banner-preview {
            margin-top: 9px; height: 120px; max-width: 360px; border-radius: 8px; border: 1px solid var(--line);
            background-image: linear-gradient(to bottom, rgba(0, 0, 0, .02), rgba(4, 18, 11, .9)), var(--card-img);
            background-position: center, var(--card-pos, center);
            background-size: cover;
        }

        /* ---------- Contact ---------- */
        .section-card { border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); padding: 22px; margin-bottom: 18px; }
        .section-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .section-card-head h2 { font-size: 16px; font-weight: 900; }
        .section-card-head p { margin-top: 4px; color: var(--text-muted); font-size: 11px; }
        .location-rows { display: grid; gap: 10px; }
        .location-row {
            display: grid; grid-template-columns: 34px minmax(0, 1fr) auto; align-items: center; gap: 14px;
            padding: 14px; border: 1px solid var(--line); border-radius: 10px; background: rgba(0, 0, 0, .18);
        }
        .location-row.inactive { opacity: .5; }
        .location-index {
            width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; border: 1px solid rgba(156, 255, 0, .45); color: var(--lime); font-size: 13px; font-weight: 900;
        }
        .location-row strong { display: block; font-size: 14px; }
        .location-row .meta { margin-top: 3px; color: #79df48; font-size: 11px; font-weight: 700; }
        .location-row .note { margin-top: 4px; color: var(--text-muted); font-size: 11px; }
        .location-row .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .location-row .actions form { display: contents; }
        .location-row .actions a,
        .location-row .actions button {
            height: 31px; padding: 0 12px; display: inline-flex; align-items: center; border-radius: 6px;
            border: 1px solid var(--line); background: transparent; color: var(--text-soft);
            font-size: 10px; font-weight: 800; text-decoration: none;
        }
        .location-row .actions a:hover, .location-row .actions button:hover { border-color: rgba(156, 255, 0, .4); color: var(--lime); }
        .location-row .actions .danger:hover { border-color: rgba(216, 35, 61, .7); color: #ff7a8c; }
        .map-preview { margin-top: 10px; width: 100%; height: 240px; border: 1px solid var(--line); border-radius: 8px; filter: grayscale(.3); }
        .inline-actions { display: flex; gap: 8px; align-items: center; }
        @media (max-width: 760px) {
            .location-row { grid-template-columns: 34px minmax(0, 1fr); }
            .location-row .actions { grid-column: 1 / -1; }
        }

        /* ---------- Customer ---------- */
        .stat-row { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
        .stat-box { padding: 15px 16px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .stat-box span { display: block; color: var(--text-muted); font-size: 10px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; }
        .stat-box strong { display: block; margin-top: 6px; font-size: 26px; font-weight: 900; line-height: 1; }
        .stat-box small { display: block; margin-top: 6px; color: #72ed4c; font-size: 10px; }

        .toolbar { display: grid; grid-template-columns: minmax(0, 1fr) 190px auto auto; gap: 8px; margin-bottom: 14px; }
        .toolbar .btn { height: 39px; }

        .table-card { border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; }
        .table-scroll { overflow-x: auto; }
        .data-table { width: 100%; min-width: 760px; border-collapse: collapse; }
        .data-table th {
            height: 38px; padding: 0 14px; background: rgba(0, 0, 0, .22); color: var(--text-muted);
            text-align: left; font-size: 9px; font-weight: 900; letter-spacing: .6px; text-transform: uppercase;
        }
        .data-table td { padding: 11px 14px; border-top: 1px solid rgba(255, 255, 255, .06); color: var(--text-soft); font-size: 12px; vertical-align: middle; }
        .data-table tbody tr:hover { background: rgba(58, 150, 57, .10); }
        .data-table .num { text-align: center; }

        .person { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .person-avatar {
            width: 34px; height: 34px; flex: 0 0 34px; display: flex; align-items: center; justify-content: center;
            border-radius: 50%; background: linear-gradient(135deg, #3d7bd9, #5743b6); color: #fff; font-size: 11px; font-weight: 900;
        }
        .person-avatar.offline { background: linear-gradient(135deg, #b8860b, #d2691e); }
        .person strong { display: block; color: #fff; font-size: 12px; font-weight: 800; }
        .person small { display: block; margin-top: 2px; color: var(--text-muted); font-size: 10px; }

        .row-actions { display: flex; gap: 6px; justify-content: flex-end; }
        .row-actions a {
            height: 29px; padding: 0 11px; display: inline-flex; align-items: center; border-radius: 6px;
            border: 1px solid var(--line); color: var(--text-soft); font-size: 10px; font-weight: 800; text-decoration: none;
        }
        .row-actions a:hover { border-color: rgba(156, 255, 0, .4); color: var(--lime); }

        .status-pill { display: inline-flex; padding: 4px 9px; border-radius: 999px; font-size: 9px; font-weight: 900; white-space: nowrap; }
        .status-pill.pending   { background: rgba(245, 174, 0, .18); color: #ffc62d; }
        .status-pill.booked    { background: rgba(67, 190, 77, .18); color: #68ed62; }
        .status-pill.cancelled,
        .status-pill.rejected  { background: rgba(216, 35, 61, .18); color: #ff7a8c; }
        .status-pill.neutral   { background: rgba(255, 255, 255, .08); color: var(--text-soft); }
        .muted { color: var(--text-muted); }

        .pager { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 14px; border-top: 1px solid var(--line); color: var(--text-muted); font-size: 11px; }
        .pager-links { display: flex; gap: 6px; }
        .pager-links a, .pager-links span {
            height: 30px; padding: 0 12px; display: inline-flex; align-items: center; border-radius: 6px;
            border: 1px solid var(--line); font-size: 11px; font-weight: 800; text-decoration: none; color: var(--text-soft);
        }
        .pager-links span { opacity: .35; }
        .pager-links a:hover { border-color: rgba(156, 255, 0, .4); color: var(--lime); }

        .profile-grid { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 16px; align-items: start; }
        .profile-card { padding: 22px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .profile-head { display: flex; align-items: center; gap: 14px; padding-bottom: 16px; border-bottom: 1px solid var(--line); }
        .profile-head .person-avatar { width: 58px; height: 58px; flex-basis: 58px; font-size: 18px; }
        .profile-head h2 { font-size: 18px; font-weight: 900; }
        .profile-list { display: grid; gap: 10px; padding: 16px 0; border-bottom: 1px solid var(--line); }
        .profile-list div { display: grid; grid-template-columns: 90px minmax(0, 1fr); gap: 8px; font-size: 12px; }
        .profile-list span:first-child { color: var(--text-muted); }
        .profile-list span:last-child { color: var(--text-soft); font-weight: 700; word-break: break-word; }
        .profile-actions { display: grid; gap: 8px; padding-top: 16px; }
        .profile-actions .btn { width: 100%; }

        .upcoming-card { margin-bottom: 16px; padding: 18px; border: 1px solid rgba(156, 255, 0, .3); border-radius: var(--radius); background: rgba(156, 255, 0, .05); }
        .upcoming-card span { color: var(--lime); font-size: 10px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; }
        .upcoming-card strong { display: block; margin-top: 6px; font-size: 18px; font-weight: 900; }
        .upcoming-card small { display: block; margin-top: 4px; color: var(--text-muted); font-size: 11px; }
        .table-title { padding: 14px; font-size: 14px; font-weight: 900; border-bottom: 1px solid var(--line); }

        @media (max-width: 1050px) {
            .stat-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .profile-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 760px) {
            .toolbar { grid-template-columns: 1fr 1fr; }
            .toolbar .input:first-child { grid-column: 1 / -1; }
        }

        /* ---------- Toast ---------- */
        .toast {
            position: fixed; top: 78px; right: 24px; z-index: 120;
            padding: 13px 18px; border: 1px solid rgba(156, 255, 0, .4); border-radius: 9px;
            background: #0b2a17; color: var(--lime); font-size: 13px; font-weight: 800;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .4);
        }
        .toast.error { border-color: rgba(255, 90, 90, .5); background: #2a0b10; color: #ff8a8a; }

        /* ---------- Responsive ---------- */
        @media (max-width: 1050px) {
            .admin-page { grid-template-columns: 200px 1fr; }
            .brand { font-size: 19px; }
        }
        @media (max-width: 760px) {
            body { overflow: auto; }
            .admin-page { display: block; height: auto; min-height: 100vh; }
            .topbar { height: 62px; }
            .sidebar { display: none; }
            .admin-page.sidebar-toggled .sidebar { display: block; height: auto; }
            .main { height: auto; padding: 16px; }
            .main-scroll { height: auto; overflow: visible; }
            .field-row { grid-template-columns: 1fr; }
        }
        @media (max-width: 520px) {
            .brand { font-size: 17px; }
            .admin-name { display: none; }
        }
    </style>

    @stack('styles')
</head>
<body>

<div class="admin-page" id="adminPage">

    <header class="topbar">
        <div class="top-left">
            <a href="{{ route('admin.dashboard') }}" class="brand">
                @include('partials.brand-logo', ['iconClass' => 'brand-icon', 'accentClass' => 'accent'])
            </a>
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka/tutup menu">☰</button>
        </div>

        <div class="top-right">
            @include('partials.notification-bell')

            <div class="admin-profile">
                <div class="admin-avatar">{{ mb_strtoupper(mb_substr($adminName, 0, 1)) }}</div>
                <div class="admin-name">
                    <strong>{{ $adminName }}</strong>
                    <small>Administrator</small>
                </div>
            </div>
        </div>
    </header>

    <aside class="sidebar">
        <div class="sidebar-scroll thin-scroll">
            <div class="sidebar-section">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="sidebar-icon">⌂</span> Dashboard
                </a>
            </div>

            <div class="sidebar-title">Booking</div>
            <div class="sidebar-section">
                <a href="{{ route('admin.dashboard') }}#booking-list" class="sidebar-link">
                    <span class="sidebar-icon">▣</span> All Booking
                </a>
                <a href="{{ route('admin.dashboard', ['status' => 'pending']) }}#booking-list" class="sidebar-link">
                    <span class="sidebar-icon">◷</span> Pending Booking
                    <span class="sidebar-badge">{{ $pendingCount }}</span>
                </a>
                <a href="{{ route('admin.offline-booking.create') }}" class="sidebar-link">
                    <span class="sidebar-icon">＋</span> Create Offline Booking
                </a>
                <a href="{{ route('admin.schedule-blocks.index') }}" class="sidebar-link {{ request()->routeIs('admin.schedule-blocks.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">⊘</span> Kelola Jadwal
                </a>
            </div>

            <div class="sidebar-divider"></div>

            <div class="sidebar-title">Content</div>
            <div class="sidebar-section">
                <a href="{{ route('admin.gallery.index') }}" class="sidebar-link {{ request()->routeIs('admin.gallery.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">▧</span> Gallery
                </a>
                <a href="{{ route('admin.events.index') }}" class="sidebar-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">▦</span> Event
                </a>
                <a href="{{ route('admin.programs.index') }}" class="sidebar-link {{ request()->routeIs('admin.programs.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">▤</span> Program
                </a>
                <a href="{{ route('admin.contact.index') }}" class="sidebar-link {{ request()->routeIs('admin.contact.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">☎</span> Contact
                </a>
            </div>

            <div class="sidebar-title">Customer</div>
            <div class="sidebar-section">
                <a href="{{ route('admin.customers.index') }}" class="sidebar-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">♟</span> Customer
                </a>
            </div>

            <div class="sidebar-title">System</div>
            <div class="sidebar-section">
                <a href="{{ route('admin.settings.index') }}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <span class="sidebar-icon">⚙</span> Settings
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-link">
                        <span class="sidebar-icon">⇥</span> Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <main class="main" style="background-image: linear-gradient(90deg, rgba(2,16,11,.96) 0%, rgba(2,16,11,.9) 45%, rgba(2,16,11,.8) 100%), url('{{ \App\Support\Brand::background('admin') }}'); background-size: cover; background-position: center;">
        <div class="main-scroll thin-scroll">
            @yield('content')
        </div>
    </main>
</div>

@if ($flashType)
    <div class="toast {{ $flashType === 'error' ? 'error' : '' }}" id="toast">{{ $flashMessage }}</div>
@endif

<script>
    document.getElementById('menuToggle')?.addEventListener('click', () => {
        document.getElementById('adminPage').classList.toggle('sidebar-toggled');
    });

    const toast = document.getElementById('toast');
    if (toast) setTimeout(() => toast.remove(), 3500);
</script>

@stack('scripts')

</body>
</html>
SET_EOF;

$files['resources/views/admin/settings/index.blade.php'] = <<<'SET_EOF'
@extends('admin.layouts.panel')

@section('title', 'Settings')

@php
    use App\Models\Setting;
    use App\Support\Brand;

    $hero = Brand::hero();
@endphp

@push('styles')
    <style>
        .st-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }

        .st-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 42px;
            padding: 0 16px;
            border: 1px solid rgba(156, 255, 0, .2);
            border-radius: 999px;
            background: rgba(2, 20, 14, .75);
            color: rgba(255, 255, 255, .85);
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
        }

        .st-tab:hover { border-color: rgba(156, 255, 0, .5); }
        .st-tab.active { background: var(--lime); border-color: var(--lime); color: var(--ink-dark); }

        .st-tab.soon { opacity: .55; cursor: not-allowed; }
        .st-tab small { padding: 2px 7px; border-radius: 999px; background: rgba(255, 255, 255, .1); font-size: 9px; }

        .st-card { padding: 22px; margin-bottom: 16px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .st-card h2 { font-size: 17px; font-weight: 900; }
        .st-card > p.st-sub { margin: 4px 0 18px; color: var(--text-muted); font-size: 12px; line-height: 1.5; }

        .st-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .st-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }

        .st-box { padding: 14px; border: 1px solid var(--line); border-radius: 10px; background: rgba(0, 0, 0, .18); }

        .st-upload { display: flex; gap: 14px; align-items: center; }
        .st-upload.stack { flex-direction: column; align-items: stretch; }

        .st-preview {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed rgba(156, 255, 0, .35);
            border-radius: 10px;
            background: rgba(0, 0, 0, .3);
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            overflow: hidden;
        }

        .st-preview img { width: 100%; height: 100%; object-fit: contain; }
        .st-preview.bg img { object-fit: cover; }
        .st-preview.logo { width: 76px; height: 76px; }
        .st-preview.icon { width: 52px; height: 52px; }
        .st-preview.bg { width: 100%; height: 120px; }

        .st-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            cursor: pointer;
        }

        .st-toggle strong { display: block; font-size: 13px; }
        .st-toggle small { display: block; margin-top: 3px; color: var(--text-muted); font-size: 11px; line-height: 1.5; }
        .st-toggle input { width: 20px; height: 20px; flex: 0 0 20px; accent-color: var(--lime); }

        .st-remove { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; color: #ff9aa6; font-size: 11px; font-weight: 700; cursor: pointer; }
        .st-remove input { accent-color: #d8233d; }

        .st-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line); }

        .st-soon { padding: 50px 20px; text-align: center; color: var(--text-muted); font-size: 13px; line-height: 1.7; }
        .st-soon strong { display: block; color: #fff; font-size: 16px; margin-bottom: 6px; }

        .st-hero-preview {
            padding: 22px;
            border-radius: 10px;
            background-size: cover;
            background-position: center;
        }

        .st-hero-preview small { color: var(--lime); font-size: 10px; font-weight: 800; letter-spacing: 2px; }
        .st-hero-preview h3 { margin-top: 6px; font-size: 26px; font-weight: 900; line-height: 1; letter-spacing: -1px; }
        .st-hero-preview h3 span { color: var(--lime); }
        .st-hero-preview p { margin-top: 8px; max-width: 460px; color: rgba(255, 255, 255, .78); font-size: 12px; line-height: 1.5; }

        @media (max-width: 1050px) { .st-grid-3 { grid-template-columns: 1fr; } }
        @media (max-width: 760px) { .st-grid-2 { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <section class="page-head">
        <div>
            <h1>Settings</h1>
            <p>Atur identitas usaha, tampilan, dan akun admin. Perubahan langsung berlaku setelah disimpan.</p>
        </div>
    </section>

    {{-- ================= TAB ================= --}}
    <nav class="st-tabs" aria-label="Kategori pengaturan">
        @foreach ($tabs as $key => $item)
            @if ($item['ready'])
                <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                   class="st-tab {{ $tab === $key ? 'active' : '' }}"
                   @if ($tab === $key) aria-current="page" @endif>
                    {{ $item['label'] }}
                </a>
            @else
                <span class="st-tab soon" title="Segera hadir di {{ $item['stage'] }}">
                    {{ $item['label'] }} <small>{{ $item['stage'] }}</small>
                </span>
            @endif
        @endforeach
    </nav>

    @if ($errors->any())
        <div class="error-box">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= UMUM & BRANDING ================= --}}
    @if ($tab === 'general')
        <form method="POST" action="{{ route('admin.settings.general') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <section class="st-card">
                <h2>Identitas usaha</h2>
                <p class="st-sub">Tampil di navbar, judul tab browser, halaman maintenance, dan bukti pembayaran.</p>

                <div class="st-grid-2">
                    <div class="field">
                        <label for="sName">Nama usaha</label>
                        <input type="text" name="site_name" id="sName" class="input" maxlength="60" required
                               value="{{ old('site_name', Brand::name()) }}">
                        <p class="field-hint">Kata di tengah tampil hijau, misalnya Golf <strong style="color: var(--lime)">Booking</strong> Lesson.</p>
                    </div>
                    <div class="field">
                        <label for="sTagline">Slogan</label>
                        <input type="text" name="tagline" id="sTagline" class="input" maxlength="120"
                               value="{{ old('tagline', Brand::tagline()) }}">
                    </div>
                </div>

                <div class="st-grid-2">
                    <div class="st-box">
                        <div class="st-upload">
                            <div class="st-preview logo">
                                @if (Brand::logoUrl())
                                    <img src="{{ Brand::logoUrl() }}" alt="Logo saat ini">
                                @else
                                    <span style="font-size: 30px">⛳</span>
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0">
                                <label class="field-label" for="sLogo">Logo</label>
                                <input type="file" name="logo" id="sLogo" class="input" accept="image/png,image/webp">
                                <p class="field-hint">PNG atau WEBP, latar transparan, maks. 1 MB.</p>
                                @if (Setting::get('logo'))
                                    <label class="st-remove"><input type="checkbox" name="remove_logo" value="1"> Hapus, kembali ke ikon bawaan</label>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="st-box">
                        <div class="st-upload">
                            <div class="st-preview icon">
                                @if (Brand::faviconUrl())
                                    <img src="{{ Brand::faviconUrl() }}" alt="Favicon saat ini">
                                @else
                                    Ikon
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0">
                                <label class="field-label" for="sFavicon">Favicon (ikon tab browser)</label>
                                <input type="file" name="favicon" id="sFavicon" class="input" accept="image/png">
                                <p class="field-hint">PNG persegi, misalnya 512×512 px, maks. 512 KB.</p>
                                @if (Setting::get('favicon'))
                                    <label class="st-remove"><input type="checkbox" name="remove_favicon" value="1"> Hapus favicon</label>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if (\Illuminate\Support\Facades\Route::has('admin.contact.index'))
                    <p class="field-hint" style="margin-top: 14px">
                        Alamat lokasi, nomor WhatsApp publik, email, dan jam operasional diatur di menu
                        <a href="{{ route('admin.contact.index') }}" style="color: var(--lime)">Contact</a>.
                    </p>
                @endif
            </section>

            <section class="st-card">
                <h2>Mode maintenance</h2>
                <p class="st-sub">Tutup website sementara untuk customer & visitor. Admin tetap bisa login dan bekerja seperti biasa.</p>

                <input type="hidden" name="maintenance" value="0">
                <label class="st-toggle st-box">
                    <span>
                        <strong>Aktifkan mode maintenance</strong>
                        <small>Customer & visitor melihat halaman "Sedang dalam perbaikan" dan tidak bisa booking.</small>
                    </span>
                    <input type="checkbox" name="maintenance" value="1" @checked(old('maintenance', Brand::maintenance() ? '1' : '0') === '1')>
                </label>

                <div class="field" style="margin-top: 14px">
                    <label for="sMaintMsg">Pesan yang ditampilkan</label>
                    <textarea name="maintenance_message" id="sMaintMsg" class="input" maxlength="300">{{ old('maintenance_message', Brand::maintenanceMessage()) }}</textarea>
                </div>
            </section>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Simpan pengaturan umum</button>
            </div>
        </form>
    @endif

    {{-- ================= TAMPILAN ================= --}}
    @if ($tab === 'appearance')
        <form method="POST" action="{{ route('admin.settings.appearance') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <section class="st-card">
                <h2>Background</h2>
                <p class="st-sub">JPG atau WEBP landscape, disarankan minimal 1920×1080 px, maks. 3 MB. Kosong = foto lapangan golf bawaan.</p>

                <div class="st-grid-3">
                    @foreach ([
                        'bg_public' => ['Halaman publik', 'Home, Program, Galeri, Event, Contact, Booking', 'public'],
                        'bg_auth'   => ['Login & Register', 'Halaman masuk dan daftar akun', 'auth'],
                        'bg_admin'  => ['Panel admin', 'Latar belakang semua halaman admin', 'admin'],
                    ] as $field => $meta)
                        @php
                            [$label, $desc, $area] = $meta;
                        @endphp
                        <div class="st-box">
                            <div class="st-upload stack">
                                <div class="st-preview bg">
                                    <img src="{{ Brand::background($area) }}" alt="Background {{ strtolower($label) }} saat ini">
                                </div>
                                <div>
                                    <label class="field-label" for="{{ $field }}">{{ $label }}</label>
                                    <input type="file" name="{{ $field }}" id="{{ $field }}" class="input" accept="image/jpeg,image/webp">
                                    <p class="field-hint">{{ $desc }}</p>
                                    @if (Setting::get($field))
                                        <label class="st-remove"><input type="checkbox" name="remove_{{ $field }}" value="1"> Kembali ke bawaan</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="st-card">
                <h2>Teks halaman Home</h2>
                <p class="st-sub">Tampil di halaman Home untuk visitor dan dashboard customer.</p>

                <div class="st-hero-preview" style="background-image: linear-gradient(90deg, rgba(2,18,12,.92), rgba(2,18,12,.55)), url('{{ Brand::background('public') }}')">
                    <small id="pLabel">{{ $hero['hero_label'] }}</small>
                    <h3><span style="color: #fff" id="pTitle1">{{ $hero['hero_title_1'] }}</span><br><span style="color: #fff" id="pTitle2">{{ $hero['hero_title_2'] }}</span> <span id="pHighlight">{{ $hero['hero_highlight'] }}</span></h3>
                    <p id="pText">{{ $hero['hero_text'] }}</p>
                </div>

                <div class="st-grid-2" style="margin-top: 16px">
                    <div class="field">
                        <label for="hLabel">Label kecil di atas judul</label>
                        <input type="text" name="hero_label" id="hLabel" class="input" maxlength="60" data-preview="pLabel"
                               value="{{ old('hero_label', $hero['hero_label']) }}">
                    </div>
                    <div class="field">
                        <label for="hTitle1">Judul baris 1</label>
                        <input type="text" name="hero_title_1" id="hTitle1" class="input" maxlength="60" data-preview="pTitle1"
                               value="{{ old('hero_title_1', $hero['hero_title_1']) }}">
                    </div>
                    <div class="field">
                        <label for="hTitle2">Judul baris 2</label>
                        <input type="text" name="hero_title_2" id="hTitle2" class="input" maxlength="60" data-preview="pTitle2"
                               value="{{ old('hero_title_2', $hero['hero_title_2']) }}">
                    </div>
                    <div class="field">
                        <label for="hHighlight">Kata berwarna hijau (di akhir baris 2)</label>
                        <input type="text" name="hero_highlight" id="hHighlight" class="input" maxlength="40" data-preview="pHighlight"
                               value="{{ old('hero_highlight', $hero['hero_highlight']) }}">
                    </div>
                </div>

                <div class="field">
                    <label for="hText">Deskripsi</label>
                    <textarea name="hero_text" id="hText" class="input" maxlength="300" data-preview="pText">{{ old('hero_text', $hero['hero_text']) }}</textarea>
                </div>
            </section>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Simpan tampilan</button>
            </div>
        </form>
    @endif

    {{-- ================= TAB TAHAP BERIKUTNYA ================= --}}
    @if (! ($tabs[$tab]['ready'] ?? false))
        <section class="st-card st-soon">
            <strong>{{ $tabs[$tab]['label'] }}</strong>
            Bagian ini akan tersedia di {{ $tabs[$tab]['stage'] }}.
        </section>
    @endif

    {{-- ================= AKUN ADMIN ================= --}}
    @if ($tab === 'account')
        <form method="POST" action="{{ route('admin.settings.profile') }}" class="st-card">
            @csrf
            @method('PUT')

            <h2>Profil admin</h2>
            <p class="st-sub">Nama dan email untuk login. Masukkan password saat ini untuk menyimpan.</p>

            <div class="st-grid-3">
                <div class="field">
                    <label for="aName">Nama</label>
                    <input type="text" name="name" id="aName" class="input" required maxlength="255"
                           value="{{ old('name', $user->name) }}">
                </div>
                <div class="field">
                    <label for="aEmail">Email</label>
                    <input type="email" name="email" id="aEmail" class="input" required maxlength="255"
                           value="{{ old('email', $user->email) }}" autocomplete="email">
                </div>
                <div class="field">
                    <label for="aCurrent1">Password saat ini</label>
                    <input type="password" name="current_password" id="aCurrent1" class="input" required autocomplete="current-password">
                </div>
            </div>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Simpan profil</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.settings.password') }}" class="st-card">
            @csrf
            @method('PUT')

            <h2>Ganti password</h2>
            <p class="st-sub">Minimal 8 karakter. Setelah diganti, login di perangkat lain akan dikeluarkan.</p>

            <div class="st-grid-3">
                <div class="field">
                    <label for="aCurrent2">Password saat ini</label>
                    <input type="password" name="current_password" id="aCurrent2" class="input" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label for="aNew">Password baru</label>
                    <input type="password" name="password" id="aNew" class="input" required minlength="8" autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="aConfirm">Ulangi password baru</label>
                    <input type="password" name="password_confirmation" id="aConfirm" class="input" required minlength="8" autocomplete="new-password">
                </div>
            </div>

            <div class="st-actions">
                <button type="submit" class="btn btn-primary">Ganti password</button>
            </div>
        </form>

        <section class="st-card">
            <h2>Verifikasi 2 langkah (2FA)</h2>
            <p class="st-sub" style="margin-bottom: 0">
                @if ($user->two_factor_confirmed_at ?? null)
                    <strong style="color: var(--lime)">Aktif.</strong> Akun admin terlindungi kode dari aplikasi authenticator.
                @else
                    Belum aktif. Pengaturan 2FA akan tersedia di tab Keamanan (Tahap 3).
                @endif
            </p>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        // Pratinjau teks Home langsung saat diketik
        document.querySelectorAll('[data-preview]').forEach((input) => {
            const target = document.getElementById(input.dataset.preview);
            if (!target) return;
            input.addEventListener('input', () => { target.textContent = input.value; });
        });

        // Pratinjau gambar sebelum diunggah
        document.querySelectorAll('input[type="file"]').forEach((input) => {
            input.addEventListener('change', () => {
                const file = input.files[0];
                const preview = input.closest('.st-upload')?.querySelector('.st-preview');
                if (!file || !preview) return;
                preview.innerHTML = '';
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = 'Pratinjau';
                preview.appendChild(img);
            });
        });
    </script>
@endpush
SET_EOF;


/* ----------------------------------------------------------------------
 | 1. CEK
 * ---------------------------------------------------------------------- */
out();
out('== Settings Tahap 1 ==');
out();
out('[1/7] Mengecek struktur proyek...');

$webPath = $root . '/routes/web.php';
$web     = (string) @file_get_contents($webPath);

foreach ([
    'resources/views/admin/layouts/panel.blade.php' => 'layout panel admin',
    'resources/views/partials/site-navbar.blade.php' => 'navbar seragam (install-navbar.php)',
] as $required => $label) {
    if (! file_exists($root . '/' . $required)) {
        fwrite(STDERR, "X {$label} belum ada ({$required}). Jalankan installer sebelumnya terlebih dahulu.\n");
        exit(1);
    }
}

if (strpos($web, 'AdminScheduleBlockController') === false) {
    fwrite(STDERR, "X Fitur Kelola Jadwal belum terpasang. Layout panel admin terbaru membutuhkannya.\n");
    exit(1);
}

out('  OK');

/* ----------------------------------------------------------------------
 | 2. TULIS FILE
 * ---------------------------------------------------------------------- */
out();
out('[2/7] Menulis file...');

foreach ($files as $relative => $content) {
    $path = $root . '/' . $relative;

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    $existed = file_exists($path);
    backup($path);
    file_put_contents($path, $content . "\n");
    out(($existed ? '  ~ diganti : ' : '  + dibuat  : ') . $relative);
}

/* ----------------------------------------------------------------------
 | 3. ROUTE
 * ---------------------------------------------------------------------- */
out();
out('[3/7] Mendaftarkan route Settings...');

if (strpos($web, 'AdminSettingController') !== false) {
    out('  = routes/web.php sudah memuat route Settings');
} else {
    $eol = eolOf($web);

    $useOk     = false;
    $anchorUse = 'use App\Http\Controllers\AdminDashboardController;';
    $posUse    = strpos($web, $anchorUse);

    if ($posUse !== false) {
        $web   = substr_replace($web, $anchorUse . $eol . 'use App\Http\Controllers\AdminSettingController;', $posUse, strlen($anchorUse));
        $useOk = true;
    }

    $routeBlock = $eol . $eol
        . '    /*' . $eol
        . '    |--------------------------------------------------------------------------' . $eol
        . '    | Admin Settings' . $eol
        . '    |--------------------------------------------------------------------------' . $eol
        . '    */' . $eol . $eol
        . "    Route::get('/admin/settings', [AdminSettingController::class, 'index'])" . $eol
        . "        ->name('admin.settings.index');" . $eol . $eol
        . "    Route::put('/admin/settings/general', [AdminSettingController::class, 'updateGeneral'])" . $eol
        . "        ->name('admin.settings.general');" . $eol . $eol
        . "    Route::put('/admin/settings/appearance', [AdminSettingController::class, 'updateAppearance'])" . $eol
        . "        ->name('admin.settings.appearance');" . $eol . $eol
        . "    Route::put('/admin/settings/profile', [AdminSettingController::class, 'updateProfile'])" . $eol
        . "        ->name('admin.settings.profile');" . $eol . $eol
        . "    Route::put('/admin/settings/password', [AdminSettingController::class, 'updatePassword'])" . $eol
        . "        ->name('admin.settings.password');";

    $routeOk = false;

    foreach ([
        "/->name\\(\\s*'admin\\.bookings\\.reschedule\\.update'\\s*\\);/",
        "/->name\\(\\s*'admin\\.payments\\.receipt'\\s*\\);/",
        "/->name\\(\\s*'admin\\.customers\\.show'\\s*\\);/",
        "/->name\\(\\s*'admin\\.bookings\\.reject'\\s*\\);/",
    ] as $pattern) {
        if (preg_match($pattern, $web, $m, PREG_OFFSET_CAPTURE)) {
            $web     = substr_replace($web, $routeBlock, $m[0][1] + strlen($m[0][0]), 0);
            $routeOk = true;
            break;
        }
    }

    if ($useOk && $routeOk) {
        backup($webPath);
        file_put_contents($webPath, $web);
        out('  ~ diupdate : routes/web.php');
    } else {
        $warnings[] = "routes/web.php tidak bisa diubah otomatis. Tambahkan route admin.settings.* secara manual di dalam group admin.";
        out('  ! posisi route tidak ditemukan');
    }
}

/* ----------------------------------------------------------------------
 | 4. MIDDLEWARE MAINTENANCE
 * ---------------------------------------------------------------------- */
out();
out('[4/7] Mendaftarkan mode maintenance...');

$bootstrapPath = $root . '/bootstrap/app.php';
$bootstrap     = (string) @file_get_contents($bootstrapPath);

if (strpos($bootstrap, 'SiteMaintenance') !== false) {
    out('  = bootstrap/app.php sudah memuat SiteMaintenance');
} elseif (preg_match('/\$middleware->alias\(\[[^\]]*\]\);/s', $bootstrap, $m, PREG_OFFSET_CAPTURE)) {
    $eol    = eolOf($bootstrap);
    $insert = $eol . $eol
        . '        /*' . $eol
        . '        |--------------------------------------------------------------------------' . $eol
        . '        | Mode Maintenance (diatur dari menu Settings)' . $eol
        . '        |--------------------------------------------------------------------------' . $eol
        . '        */' . $eol . $eol
        . '        $middleware->web(append: [' . $eol
        . '            \App\Http\Middleware\SiteMaintenance::class,' . $eol
        . '        ]);';

    $bootstrap = substr_replace($bootstrap, $insert, $m[0][1] + strlen($m[0][0]), 0);
    backup($bootstrapPath);
    file_put_contents($bootstrapPath, $bootstrap);
    out('  ~ diupdate : bootstrap/app.php');
} else {
    $warnings[] = "bootstrap/app.php tidak bisa diubah otomatis. Tambahkan di dalam ->withMiddleware(...):\n"
        . "    \$middleware->web(append: [\\App\\Http\\Middleware\\SiteMaintenance::class]);";
    out('  ! posisi middleware tidak ditemukan');
}

/* ----------------------------------------------------------------------
 | 5. HALAMAN: favicon, judul, background
 * ---------------------------------------------------------------------- */
out();
out('[5/7] Menghubungkan halaman ke Settings...');

$viewsRoot = $root . '/resources/views/';
$pages     = [];

foreach ([
    'program.blade.php', 'galeri.blade.php', 'event.blade.php', 'contact.blade.php', 'payment.blade.php',
    'dashboard.blade.php', 'booking.blade.php', 'payments/booking.blade.php',
    'notifications/customer-layout.blade.php', 'admin/payments/receipt.blade.php',
] as $relative) {
    $pages[$relative] = 'public';
}

$pages['admin/offline-booking.blade.php'] = 'admin';

foreach (['auth', 'pages/auth', 'layouts/auth', 'layouts'] as $dir) {
    foreach (glob($viewsRoot . $dir . '/*.blade.php') ?: [] as $file) {
        $relative = substr(str_replace('\\', '/', $file), strlen(str_replace('\\', '/', $viewsRoot)));
        $pages[$relative] = str_contains($relative, 'auth') ? 'auth' : 'public';
    }
}

$bgCount = 0;

foreach ($pages as $relative => $area) {
    $path = $viewsRoot . $relative;

    if (! file_exists($path)) {
        continue;
    }

    $content  = file_get_contents($path);
    $original = $content;
    $eol      = eolOf($content);
    $bgUrl    = "url('{{ \\App\\Support\\Brand::background('" . $area . "') }}')";

    // a) Background bawaan -> background dari Settings
    $content = preg_replace_callback(
        [
            '/url\(\s*([\'"]?)\/?images\/background\.golf\.jpeg\1\s*\)/',
            '/url\(\s*([\'"]?)\{\{\s*asset\(\s*[\'"]\/?images\/background\.golf\.jpeg[\'"]\s*\)\s*\}\}\1\s*\)/',
        ],
        fn () => $bgUrl,
        $content,
        -1,
        $replaced
    );
    $bgCount += $replaced;

    // b) Favicon & deskripsi
    if (strpos($content, 'partials.brand-head') === false && stripos($content, '</head>') !== false) {
        $content = preg_replace('/(\s*)<\/head>/i', $eol . "    @include('partials.brand-head')" . '$1</head>', $content, 1);
    }

    // c) Nama usaha di judul tab browser
    $content = preg_replace_callback('/<title>(.*?)<\/title>/s', function ($m) {
        return '<title>' . str_replace('Golf Booking Lesson', '{{ \App\Support\Brand::name() }}', $m[1]) . '</title>';
    }, $content, 1);

    if ($content !== $original) {
        backup($path);
        file_put_contents($path, $content);
        $report[] = $relative;
    }
}

foreach ($report as $relative) {
    out('  ~ ' . $relative);
}

out("  Background terhubung di {$bgCount} tempat.");

/* ----------------------------------------------------------------------
 | 6. TEKS HALAMAN HOME
 * ---------------------------------------------------------------------- */
out();
out('[6/7] Menghubungkan teks halaman Home...');

$homePath = $viewsRoot . 'dashboard.blade.php';
$home     = (string) @file_get_contents($homePath);

if ($home === '') {
    $warnings[] = 'resources/views/dashboard.blade.php tidak ditemukan.';
} elseif (strpos($home, '$brandHero') !== false) {
    out('  = teks Home sudah terhubung');
} else {
    $updated = $home;
    $ok      = true;

    $patterns = [
        '/<div class="small-title">\s*GOLF BOOKING LESSON\s*<\/div>/'
            => '<div class="small-title">{{ $brandHero[\'hero_label\'] }}</div>',
        '/<h1 class="main-title">\s*FROM FIRST SWING\s*<br>\s*TO\s*<span class="green">CHAMPIONSHIP<\/span>\s*<\/h1>/'
            => '<h1 class="main-title">{{ $brandHero[\'hero_title_1\'] }}<br>{{ $brandHero[\'hero_title_2\'] }} <span class="green">{{ $brandHero[\'hero_highlight\'] }}</span></h1>',
        '/<p class="description">\s*Tingkatkan permainan golf.*?<\/p>/s'
            => '<p class="description">{{ $brandHero[\'hero_text\'] }}</p>',
    ];

    foreach ($patterns as $pattern => $replacement) {
        $updated = preg_replace_callback($pattern, fn () => $replacement, $updated, 1, $count);

        if ($count !== 1) {
            $ok = false;
            break;
        }
    }

    if ($ok) {
        $updated = "@php" . eolOf($home) . "    \$brandHero = \\App\\Support\\Brand::hero();" . eolOf($home) . "@endphp" . eolOf($home) . $updated;
        backup($homePath);
        file_put_contents($homePath, $updated);
        out('  ~ diupdate : resources/views/dashboard.blade.php');
    } else {
        $warnings[] = 'Teks di dashboard.blade.php berbeda dari yang diharapkan, sehingga teks Home belum terhubung ke Settings.';
        out('  ! teks Home tidak ditemukan');
    }
}

/* ----------------------------------------------------------------------
 | 7. ARTISAN
 * ---------------------------------------------------------------------- */
out();
out('[7/7] Menjalankan artisan...');

$php = escapeshellarg(PHP_BINARY);

out();
out('> php artisan migrate');
passthru("{$php} artisan migrate", $code);

if ($code !== 0) {
    $warnings[] = "'php artisan migrate' gagal. Cek koneksi database di .env, lalu jalankan ulang installer.";
}

if (! file_exists($root . '/public/storage')) {
    out();
    out('> php artisan storage:link');
    passthru("{$php} artisan storage:link");
}

out();
out('> php artisan optimize:clear');
passthru("{$php} artisan optimize:clear");

/* ----------------------------------------------------------------------
 | SELESAI
 * ---------------------------------------------------------------------- */
out();
out('== Selesai ==');

foreach ($warnings as $warning) {
    out();
    out('! ' . $warning);
}

out();
out('Backup file lama berakhiran .bak-' . $stamp);
out('Buka: /admin/settings');
out();
