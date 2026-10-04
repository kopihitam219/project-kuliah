<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Brand;
use App\Support\BookingRules;
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
        'booking'    => ['label' => 'Booking & Jadwal',      'ready' => true],
        'payment'    => ['label' => 'Pembayaran',            'ready' => true],
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
     | BOOKING & JADWAL
     * --------------------------------------------------------------- */
    public function updateBooking(Request $request): RedirectResponse
    {
        $request->validate([
            'open_time'         => ['required', 'date_format:H:i'],
            'close_time'        => ['required', 'date_format:H:i', 'after:open_time'],
            'slot_minutes'      => ['required', Rule::in(['30', '60'])],
            'min_minutes'       => ['required', Rule::in(['30', '60', '90', '120'])],
            'cancel_days'       => ['required', Rule::in(['0', '1', '2', '3'])],
            'max_active'        => ['required', Rule::in(['0', '1', '2', '3', '4', '5'])],
            'auto_approve_paid' => ['nullable', 'boolean'],
        ], [
            'open_time.required'  => 'Jam buka wajib diisi.',
            'close_time.required' => 'Jam tutup wajib diisi.',
            'close_time.after'    => 'Jam tutup harus setelah jam buka.',
        ]);

        $open    = $request->input('open_time');
        $close   = $request->input('close_time');
        $minutes = (strtotime($close) - strtotime($open)) / 60;

        if ($minutes < (int) $request->input('min_minutes')) {
            return back()->withInput()->withErrors(['close_time' => 'Rentang jam buka terlalu pendek untuk durasi minimal yang dipilih.']);
        }

        Setting::put([
            'open_time'         => $open,
            'close_time'        => $close,
            'slot_minutes'      => $request->input('slot_minutes'),
            'min_minutes'       => $request->input('min_minutes'),
            'cancel_days'       => $request->input('cancel_days'),
            'max_active'        => $request->input('max_active'),
            'auto_approve_paid' => $request->boolean('auto_approve_paid') ? '1' : '0',
        ]);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'booking'])
            ->with('success', 'Aturan booking & jadwal disimpan. Berlaku untuk booking berikutnya.');
    }

    /* ---------------------------------------------------------------
     | PEMBAYARAN
     * --------------------------------------------------------------- */
    public function updatePayment(Request $request): RedirectResponse
    {
        $account = ['nullable', 'string', 'max:30', 'regex:/^[0-9\s\-]+$/'];

        $request->validate([
            'price_per_hour'       => ['required', 'integer', 'min:0', 'max:100000000'],
            'payment_expiry_hours' => ['required', Rule::in(['1', '3', '6', '12', '24', '48'])],
            'require_paid'         => ['nullable', 'boolean'],
            'payment_mode'         => ['required', Rule::in(['demo', 'live'])],

            'pay_qris_enabled'     => ['nullable', 'boolean'],
            'qris_image'           => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
            'qris_merchant'        => ['nullable', 'string', 'max:60'],
            'qris_nmid'            => ['nullable', 'string', 'max:30'],

            'pay_mandiri_enabled'  => ['nullable', 'boolean'],
            'mandiri_account'      => $account,
            'mandiri_holder'       => ['nullable', 'string', 'max:60'],

            'pay_bca_enabled'      => ['nullable', 'boolean'],
            'bca_account'          => $account,
            'bca_holder'           => ['nullable', 'string', 'max:60'],
        ], [
            'price_per_hour.required' => 'Harga per jam wajib diisi.',
            'price_per_hour.integer'  => 'Harga harus berupa angka tanpa titik, misalnya 900000.',
            'qris_image.mimes'        => 'Gambar QRIS harus PNG atau JPG.',
            'qris_image.max'          => 'Ukuran gambar QRIS maksimal 2 MB.',
            '*_account.regex'         => 'Nomor rekening hanya boleh berisi angka.',
        ]);

        $enabled = array_filter(['qris', 'mandiri', 'bca'], fn ($key) => $request->boolean("pay_{$key}_enabled"));

        if (empty($enabled)) {
            return back()->withInput()->withErrors(['pay_qris_enabled' => 'Minimal satu metode pembayaran harus aktif.']);
        }

        // Mode live: bank yang diaktifkan wajib punya nomor rekening & atas nama asli
        if ($request->input('payment_mode') === 'live') {
            foreach (['mandiri' => 'Mandiri', 'bca' => 'BCA'] as $key => $label) {
                if ($request->boolean("pay_{$key}_enabled")
                    && (! $request->filled("{$key}_account") || ! $request->filled("{$key}_holder"))) {
                    return back()->withInput()->withErrors([
                        "{$key}_account" => "Mode Live: nomor rekening dan atas nama {$label} wajib diisi jika {$label} diaktifkan.",
                    ]);
                }
            }

            if ($request->boolean('pay_qris_enabled') && ! Setting::get('qris_image') && ! $request->hasFile('qris_image')) {
                return back()->withInput()->withErrors([
                    'qris_image' => 'Mode Live: upload gambar QRIS asli, atau nonaktifkan QRIS.',
                ]);
            }
        }

        $values = [
            'price_per_hour'       => (string) (int) $request->input('price_per_hour'),
            'payment_expiry_hours' => $request->input('payment_expiry_hours'),
            'require_paid'         => $request->boolean('require_paid') ? '1' : '0',
            'payment_mode'         => $request->input('payment_mode'),
            'qris_merchant'        => trim((string) $request->input('qris_merchant')),
            'qris_nmid'            => trim((string) $request->input('qris_nmid')),
        ];

        foreach (['qris', 'mandiri', 'bca'] as $key) {
            $values["pay_{$key}_enabled"] = $request->boolean("pay_{$key}_enabled") ? '1' : '0';
        }

        foreach (['mandiri', 'bca'] as $key) {
            $values["{$key}_account"] = preg_replace('/\D+/', '', (string) $request->input("{$key}_account"));
            $values["{$key}_holder"]  = trim((string) $request->input("{$key}_holder"));
        }

        Setting::put($values);

        $this->handleImage($request, 'qris_image', 'qris_image', 'settings/payment');

        return redirect()
            ->route('admin.settings.index', ['tab' => 'payment'])
            ->with('success', 'Pengaturan pembayaran disimpan. Harga baru berlaku untuk tagihan berikutnya.');
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
