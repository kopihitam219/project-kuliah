<?php

namespace App\Http\Controllers;

use App\Models\ContactLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminContactLocationController extends Controller
{
    public function create(): View
    {
        $location = new ContactLocation([
            'sort_order' => (int) ContactLocation::max('sort_order') + 1,
            'is_active'  => true,
        ]);

        return view('admin.contact.location-create', compact('location'));
    }

    public function store(Request $request): RedirectResponse
    {
        ContactLocation::create($this->validatedData($request));

        return redirect()
            ->route('admin.contact.index')
            ->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function edit(ContactLocation $location): View
    {
        return view('admin.contact.location-edit', compact('location'));
    }

    /**
     * quick_toggle=1 hanya membalik status tampil / sembunyi.
     */
    public function update(Request $request, ContactLocation $location): RedirectResponse
    {
        if ($request->boolean('quick_toggle')) {
            $location->update(['is_active' => ! $location->is_active]);

            return back()->with(
                'success',
                $location->is_active
                    ? 'Lokasi ditampilkan di halaman Contact.'
                    : 'Lokasi disembunyikan dari halaman Contact.'
            );
        }

        $location->update($this->validatedData($request));

        return redirect()
            ->route('admin.contact.index')
            ->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(ContactLocation $location): RedirectResponse
    {
        $location->delete();

        return redirect()
            ->route('admin.contact.index')
            ->with('success', 'Lokasi berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'area'       => ['nullable', 'string', 'max:100'],
            'note'       => ['nullable', 'string', 'max:255'],
            'maps_query' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
        ]);

        return [
            'name'       => $request->input('name'),
            'area'       => $request->input('area'),
            'note'       => $request->input('note'),
            'maps_query' => $request->input('maps_query'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active'  => $request->boolean('is_active'),
        ];
    }
}
