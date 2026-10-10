{{-- Dipakai create & edit. Variabel: $coach, $action, $isEdit --}}
@php $isActive = (bool) old('is_active', $coach->is_active ?? true); @endphp

<style>
    .photo-preview { width: 150px; aspect-ratio: 4 / 4.3; border-radius: 16px; border: 1px solid var(--border); background: linear-gradient(160deg, #163d25, #04100b) center / cover; display: grid; place-items: center; color: var(--text-muted); font-size: 12px; text-align: center; padding: 8px; }
</style>

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="form-card" id="coachForm">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    @if ($errors->any())
        <div class="error-box"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="field">
        <label for="fPhoto">Foto coach</label>
        <input type="file" name="photo_file" id="fPhoto" accept="image/jpeg,image/png,image/webp" class="input @error('photo_file') is-invalid @enderror">
        <p class="field-hint">JPG, PNG, atau WEBP. Maksimal 4 MB. Foto potret (tegak) paling cocok. Jika kosong, tampil ilustrasi golfer.</p>
        <div class="photo-preview" id="photoPreview" @if ($coach->photo_url) style="background-image:url('{{ $coach->photo_url }}')" @endif>
            @unless ($coach->photo_url) Belum ada foto @endunless
        </div>
        @if ($isEdit && $coach->photo)
            <label class="checkbox"><input type="checkbox" name="remove_photo" value="1"> Hapus foto</label>
        @endif
    </div>

    <div class="field-row">
        <div class="field">
            <label for="fName">Nama coach</label>
            <input type="text" name="name" id="fName" maxlength="100" required class="input @error('name') is-invalid @enderror" value="{{ old('name', $coach->name) }}" placeholder="Contoh: Coach Andi">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="fBadge">Label di foto</label>
            <input type="text" name="badge" id="fBadge" maxlength="40" class="input" value="{{ old('badge', $coach->badge) }}" placeholder="Contoh: Head Coach">
        </div>
    </div>

    <div class="field">
        <label for="fRole">Peran / keahlian utama</label>
        <input type="text" name="role" id="fRole" maxlength="120" class="input" value="{{ old('role', $coach->role) }}" placeholder="Contoh: Head Coach · Swing & Driving">
    </div>

    <div class="field-row">
        <div class="field">
            <label for="fYears">Pengalaman (tahun)</label>
            <input type="number" name="years_experience" id="fYears" min="0" max="80" class="input" value="{{ old('years_experience', $coach->years_experience) }}" placeholder="12">
        </div>
        <div class="field">
            <label for="fStudents">Murid dilatih</label>
            <input type="text" name="students" id="fStudents" maxlength="30" class="input" value="{{ old('students', $coach->students) }}" placeholder="Contoh: 450+">
        </div>
    </div>

    <div class="field">
        <label for="fSkills">Keahlian</label>
        <textarea name="skills" id="fSkills" rows="3" class="input" placeholder="Full swing&#10;Driving&#10;Course strategy">{{ old('skills', $coach->skills_text) }}</textarea>
        <p class="field-hint">Satu keahlian per baris (atau pisahkan dengan koma). Maksimal 6.</p>
    </div>

    <div class="field">
        <label for="fQuote">Kutipan singkat</label>
        <input type="text" name="quote" id="fQuote" maxlength="200" class="input" value="{{ old('quote', $coach->quote) }}" placeholder="Contoh: Swing yang konsisten dimulai dari dasar yang benar.">
    </div>

    <div class="field-row">
        <div class="field">
            <label for="fSort">Urutan tampil</label>
            <input type="number" name="sort_order" id="fSort" min="0" class="input" value="{{ old('sort_order', $coach->sort_order ?? 0) }}">
            <p class="field-hint">Angka kecil tampil lebih dulu.</p>
        </div>
        <div class="field">
            <span class="field-label">Status</span>
            <input type="hidden" name="is_active" value="0">
            <label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked($isActive)> Tampilkan di halaman Home</label>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('admin.coaches.index') }}" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary" id="btnSubmit">{{ $isEdit ? 'Simpan perubahan' : 'Tambah coach' }}</button>
    </div>
</form>

@push('scripts')
    <script>
        (() => {
            const input = document.getElementById('fPhoto');
            const preview = document.getElementById('photoPreview');
            input.addEventListener('change', () => {
                const f = input.files[0]; if (!f) return;
                preview.style.backgroundImage = `url('${URL.createObjectURL(f)}')`; preview.textContent = '';
            });
            document.getElementById('coachForm').addEventListener('submit', () => {
                const b = document.getElementById('btnSubmit'); b.disabled = true; b.textContent = 'Menyimpan...';
            });
        })();
    </script>
@endpush
