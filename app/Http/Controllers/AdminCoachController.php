<?php

namespace App\Http\Controllers;

use App\Models\Coach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Kelola Coach: profil coach yang tampil di bagian "Kenali Coach Kami" di halaman Home.
 * Hanya informasi. Customer tidak memilih coach saat booking.
 */
class AdminCoachController extends Controller
{
    public function index(): View
    {
        $coaches = Coach::ordered()->get();

        return view('admin.coaches.index', compact('coaches'));
    }

    public function create(): View
    {
        $coach = new Coach(['sort_order' => (int) Coach::max('sort_order') + 1, 'is_active' => true, 'skills' => []]);

        return view('admin.coaches.create', compact('coach'));
    }

    public function store(Request $request): RedirectResponse
    {
        $coach = new Coach($this->validatedData($request));
        $this->handlePhoto($request, $coach);
        $coach->save();

        return redirect()->route('admin.coaches.index')->with('success', 'Coach berhasil ditambahkan.');
    }

    public function edit(Coach $coach): View
    {
        return view('admin.coaches.edit', compact('coach'));
    }

    public function update(Request $request, Coach $coach): RedirectResponse
    {
        if ($request->boolean('quick_toggle')) {
            $coach->update(['is_active' => ! $coach->is_active]);

            return back()->with('success', $coach->is_active ? 'Coach ditampilkan di Home.' : 'Coach disembunyikan dari Home.');
        }

        $coach->fill($this->validatedData($request));
        $this->handlePhoto($request, $coach);
        $coach->save();

        return redirect()->route('admin.coaches.index')->with('success', 'Coach berhasil diperbarui.');
    }

    public function destroy(Coach $coach): RedirectResponse
    {
        $this->deletePhoto($coach->photo);
        $coach->delete();

        return redirect()->route('admin.coaches.index')->with('success', 'Coach berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'role'             => ['nullable', 'string', 'max:120'],
            'badge'            => ['nullable', 'string', 'max:40'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'students'         => ['nullable', 'string', 'max:30'],
            'skills'           => ['nullable', 'string', 'max:600'],
            'quote'            => ['nullable', 'string', 'max:200'],
            'photo_file'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
            'is_active'        => ['nullable', 'boolean'],
        ], [
            'name.required'    => 'Nama coach wajib diisi.',
            'photo_file.image' => 'File harus berupa gambar.',
            'photo_file.max'   => 'Ukuran foto maksimal 4 MB.',
        ]);

        $skills = collect(preg_split('/\r\n|\r|\n|,/', (string) $request->input('skills')))
            ->map(fn ($s) => trim($s))->filter()->map(fn ($s) => Str::limit($s, 30, ''))->take(6)->values()->all();

        return [
            'name'             => trim($request->input('name')),
            'role'             => $request->input('role'),
            'badge'            => $request->input('badge'),
            'years_experience' => $request->filled('years_experience') ? (int) $request->input('years_experience') : null,
            'students'         => $request->input('students'),
            'skills'           => $skills,
            'quote'            => $request->input('quote'),
            'sort_order'       => (int) $request->input('sort_order', 0),
            'is_active'        => $request->boolean('is_active'),
        ];
    }

    private function handlePhoto(Request $request, Coach $coach): void
    {
        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($coach->photo);
            $coach->photo = null;
        }

        if ($request->hasFile('photo_file')) {
            $this->deletePhoto($coach->photo);
            $coach->photo = $request->file('photo_file')->store('coaches', 'public');
        }
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && Str::startsWith($path, 'coaches/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
