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
