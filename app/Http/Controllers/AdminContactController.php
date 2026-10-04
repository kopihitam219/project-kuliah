<?php

namespace App\Http\Controllers;

use App\Models\ContactLocation;
use App\Models\ContactSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminContactController extends Controller
{
    /**
     * Halaman kelola Contact: informasi kontak + daftar lokasi.
     */
    public function index(): View
    {
        $setting   = ContactSetting::current();
        $locations = ContactLocation::ordered()->get();

        return view('admin.contact.index', compact('setting', 'locations'));
    }

    /**
     * Menyimpan informasi kontak.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'description'   => ['nullable', 'string', 'max:500'],
            'whatsapp'      => ['required', 'string', 'max:30', 'regex:/^[0-9+\s\-()]{8,30}$/'],
            'email'         => ['required', 'email', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:100'],
        ], [
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex'    => 'Nomor WhatsApp hanya boleh berisi angka, spasi, +, atau tanda hubung.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
        ]);

        ContactSetting::current()->update($validated);

        return redirect()
            ->route('admin.contact.index')
            ->with('success', 'Informasi kontak berhasil diperbarui.');
    }
}
