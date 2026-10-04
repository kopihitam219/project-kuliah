<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminGalleryController extends Controller
{
    private const YOUTUBE_PATTERN = '~^https?://(www\.|m\.)?(youtube\.com|youtu\.be)/~i';

    /**
     * Menampilkan daftar gallery.
     */
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), ['image', 'video'], true)
            ? $request->query('type')
            : null;

        $galleries = Gallery::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->ordered()
            ->get();

        $counts = [
            'all'   => Gallery::count(),
            'image' => Gallery::images()->count(),
            'video' => Gallery::videos()->count(),
        ];

        return view('admin.gallery.index', compact('galleries', 'counts', 'type'));
    }

    /**
     * Menampilkan form tambah gallery.
     */
    public function create(): View
    {
        $gallery = new Gallery([
            'type'       => 'image',
            'status'     => 'active',
            'sort_order' => 0,
        ]);

        return view('admin.gallery.create', compact('gallery'));
    }

    /**
     * Menyimpan gallery baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $gallery = new Gallery($this->validatedData($request));

        $this->handleMedia($request, $gallery);
        $gallery->save();

        return redirect()
            ->route('admin.gallery.index')
            ->with('success', 'Gallery berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit gallery.
     */
    public function edit(Gallery $gallery): View
    {
        return view('admin.gallery.edit', compact('gallery'));
    }

    /**
     * Mengupdate gallery.
     * Jika dikirim dengan quick_toggle=1, hanya status yang dibalik
     * (tombol Tampilkan / Sembunyikan di halaman daftar).
     */
    public function update(Request $request, Gallery $gallery): RedirectResponse
    {
        if ($request->boolean('quick_toggle')) {
            $gallery->update([
                'status' => $gallery->isActive() ? 'inactive' : 'active',
            ]);

            return back()->with(
                'success',
                $gallery->isActive()
                    ? 'Media ditampilkan ke customer & visitor.'
                    : 'Media disembunyikan dari customer & visitor.'
            );
        }

        $gallery->fill($this->validatedData($request, $gallery));

        $this->handleMedia($request, $gallery);
        $gallery->save();

        return redirect()
            ->route('admin.gallery.index')
            ->with('success', 'Gallery berhasil diperbarui.');
    }

    /**
     * Menghapus gallery beserta file-nya.
     */
    public function destroy(Gallery $gallery): RedirectResponse
    {
        $this->deleteStoredFile($gallery->image);
        $this->deleteStoredFile($gallery->video_file);

        $gallery->delete();

        return redirect()
            ->route('admin.gallery.index')
            ->with('success', 'Gallery berhasil dihapus.');
    }

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    private function validatedData(Request $request, ?Gallery $gallery = null): array
    {
        $type   = $request->input('type');
        $source = $request->input('video_source', 'youtube');

        // File wajib jika belum ada file lama
        $needsImage     = $type === 'image' && ! $gallery?->image;
        $needsVideoFile = $type === 'video' && $source === 'upload' && ! $gallery?->video_file;

        $request->validate([
            'type'         => ['required', Rule::in(['image', 'video'])],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'category'     => ['nullable', 'string', 'max:100'],
            'status'       => ['required', Rule::in(['active', 'inactive'])],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            'video_source' => ['nullable', Rule::in(['youtube', 'upload'])],
            'image_file'   => [Rule::requiredIf($needsImage), 'nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'video_url'    => [Rule::requiredIf($type === 'video' && $source === 'youtube'), 'nullable', 'url', 'max:255', 'regex:' . self::YOUTUBE_PATTERN],
            'video_upload' => [Rule::requiredIf($needsVideoFile), 'nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:51200'],
        ], [
            'title.required'        => 'Judul wajib diisi.',
            'image_file.required'   => 'Pilih file foto yang akan diunggah.',
            'image_file.image'      => 'File foto harus berupa gambar.',
            'image_file.max'        => 'Ukuran foto maksimal 5 MB.',
            'video_url.required'    => 'Link YouTube wajib diisi.',
            'video_url.regex'       => 'Link harus berasal dari YouTube.',
            'video_upload.required' => 'Pilih file video yang akan diunggah.',
            'video_upload.max'      => 'Ukuran video maksimal 50 MB.',
        ]);

        return [
            'type'        => $type,
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'category'    => $request->input('category'),
            'status'      => $request->input('status'),
            'sort_order'  => (int) $request->input('sort_order', 0),
        ];
    }

    private function handleMedia(Request $request, Gallery $gallery): void
    {
        // Foto (untuk tipe image) atau thumbnail (untuk tipe video)
        if ($request->hasFile('image_file')) {
            $this->deleteStoredFile($gallery->image);
            $gallery->image = $request->file('image_file')->store('gallery/images', 'public');
        }

        if ($gallery->type === 'image') {
            $gallery->video_url = null;
            $this->deleteStoredFile($gallery->video_file);
            $gallery->video_file = null;

            return;
        }

        if ($request->input('video_source', 'youtube') === 'youtube') {
            $gallery->video_url = $request->input('video_url');
            $this->deleteStoredFile($gallery->video_file);
            $gallery->video_file = null;

            return;
        }

        if ($request->hasFile('video_upload')) {
            $this->deleteStoredFile($gallery->video_file);
            $gallery->video_file = $request->file('video_upload')->store('gallery/videos', 'public');
        }

        $gallery->video_url = null;
    }

    /**
     * Hanya menghapus file yang diunggah lewat admin (folder storage gallery/),
     * file bawaan di public/images tidak akan tersentuh.
     */
    private function deleteStoredFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'gallery/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
