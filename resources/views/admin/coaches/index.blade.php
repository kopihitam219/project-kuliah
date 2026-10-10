@extends('admin.layouts.panel')

@section('title', 'About Coach')

@section('content')
    <style>
        .ab-grid { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 18px; align-items: start; }
        .ab-grid .form-card { max-width: none; }
        .ab-sec { margin: 6px 0 14px; padding-top: 14px; border-top: 1px solid var(--line); color: var(--lime); font-size: 11px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; }
        .ab-sec:first-of-type { border-top: 0; padding-top: 0; }
        .ab-photo { display: flex; gap: 14px; align-items: flex-start; }
        .ab-photo-prev { flex: 0 0 120px; aspect-ratio: 4 / 5; border-radius: 16px; border: 1px solid var(--border); background: linear-gradient(160deg, #163d25, #04100b) center top / cover; display: grid; place-items: center; color: var(--text-muted); font-size: 11px; text-align: center; padding: 8px; }
        .ab-photo > div:last-child { flex: 1; min-width: 0; }
        textarea.input.tall { height: 130px; }
        .ab-side { position: sticky; top: 84px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); padding: 18px; }
        .ab-side h3 { font-size: 14px; margin-bottom: 4px; }
        .ab-side p { color: var(--text-muted); font-size: 11.5px; line-height: 1.55; }
        .ab-side ul { margin: 10px 0 14px 16px; color: var(--text-soft); font-size: 11.5px; line-height: 1.7; }
        .ab-side .btn { width: 100%; justify-content: center; }
        @media (max-width: 1000px) { .ab-grid { grid-template-columns: 1fr; } .ab-side { position: static; } }
        @media (max-width: 560px) { .ab-photo { flex-direction: column; } .ab-photo-prev { width: 140px; } }
    </style>

    <section class="page-head">
        <div>
            <h1>About Coach</h1>
            <p>Profil coach yang tampil di halaman Home. Hanya informasi, customer tidak memilih coach saat booking.</p>
        </div>
        <div class="head-actions">
            <a href="{{ route('home') }}#coach" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat di Home</a>
        </div>
    </section>

    <div class="ab-grid">
        <form method="POST" action="{{ $coach->exists ? route('admin.coaches.update', $coach) : route('admin.coaches.store') }}" enctype="multipart/form-data" class="form-card" id="coachForm">
            @csrf
            @if ($coach->exists) @method('PUT') @endif

            @if ($errors->any())
                <div class="error-box"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="ab-sec">Profil</div>

            <div class="field ab-photo">
                <div class="ab-photo-prev" id="photoPreview" @if ($coach->photo_url) style="background-image:url('{{ $coach->photo_url }}')" @endif>
                    @unless ($coach->photo_url) Belum ada foto @endunless
                </div>
                <div>
                    <label for="fPhoto" class="field-label">Foto coach</label>
                    <input type="file" name="photo_file" id="fPhoto" accept="image/jpeg,image/png,image/webp" class="input @error('photo_file') is-invalid @enderror">
                    <p class="field-hint">JPG, PNG, atau WEBP, maksimal 4 MB. Foto potret (tegak) paling bagus. Jika kosong, tampil ilustrasi golfer.</p>
                    @if ($coach->photo)
                        <label class="checkbox"><input type="checkbox" name="remove_photo" value="1"> Hapus foto</label>
                    @endif
                </div>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="fName">Nama coach</label>
                    <input type="text" name="name" id="fName" maxlength="100" required class="input @error('name') is-invalid @enderror" value="{{ old('name', $coach->name) }}" placeholder="Contoh: Coach Andi">
                </div>
                <div class="field">
                    <label for="fBadge">Label di foto</label>
                    <input type="text" name="badge" id="fBadge" maxlength="40" class="input" value="{{ old('badge', $coach->badge) }}" placeholder="Contoh: Head Coach">
                </div>
            </div>

            <div class="field">
                <label for="fRole">Jabatan / spesialisasi</label>
                <input type="text" name="role" id="fRole" maxlength="120" class="input" value="{{ old('role', $coach->role) }}" placeholder="Contoh: Head Coach · Swing & Driving">
            </div>

            <div class="field">
                <label for="fBio">Tentang coach</label>
                <textarea name="bio" id="fBio" maxlength="1500" class="input tall" placeholder="Cerita singkat: mulai main golf, gaya melatih, cocok untuk siapa.">{{ old('bio', $coach->bio) }}</textarea>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="fYears">Pengalaman melatih (tahun)</label>
                    <input type="number" name="years_experience" id="fYears" min="0" max="80" class="input" value="{{ old('years_experience', $coach->years_experience) }}" placeholder="12">
                </div>
                <div class="field">
                    <label for="fStudents">Murid dilatih</label>
                    <input type="text" name="students" id="fStudents" maxlength="30" class="input" value="{{ old('students', $coach->students) }}" placeholder="Contoh: 450+">
                </div>
            </div>

            <div class="ab-sec">Pengalaman & kredensial</div>

            <div class="field">
                <label for="fExp">Riwayat pengalaman</label>
                <textarea name="experiences" id="fExp" class="input tall" placeholder="2020 – sekarang | Head Coach Golf Booking Lesson&#10;2016 – 2020 | Coach di driving range">{{ old('experiences', $coach->experiences_text) }}</textarea>
                <p class="field-hint">Satu baris satu pengalaman, format: <b>periode | keterangan</b>. Maksimal 8 baris.</p>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="fCert">Sertifikasi / lisensi</label>
                    <textarea name="certifications" id="fCert" class="input" placeholder="Sertifikat Pelatih Golf Nasional&#10;Lisensi Coach Level 2">{{ old('certifications', $coach->certifications_text) }}</textarea>
                    <p class="field-hint">Satu per baris.</p>
                </div>
                <div class="field">
                    <label for="fAch">Prestasi</label>
                    <textarea name="achievements" id="fAch" class="input" placeholder="Juara 1 Amateur Open 2012">{{ old('achievements', $coach->achievements_text) }}</textarea>
                    <p class="field-hint">Satu per baris.</p>
                </div>
            </div>

            <div class="field">
                <label for="fSkills">Keahlian yang diajarkan</label>
                <textarea name="skills" id="fSkills" class="input" placeholder="Full swing&#10;Driving&#10;Short game">{{ old('skills', $coach->skills_text) }}</textarea>
                <p class="field-hint">Satu per baris (atau pisahkan dengan koma). Maksimal 8.</p>
            </div>

            <div class="field">
                <label for="fQuote">Kutipan / motto</label>
                <input type="text" name="quote" id="fQuote" maxlength="200" class="input" value="{{ old('quote', $coach->quote) }}" placeholder="Contoh: Swing yang konsisten dimulai dari dasar yang benar.">
            </div>

            <div class="field">
                <input type="hidden" name="is_active" value="0">
                <label class="checkbox"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $coach->is_active ?? true))> Tampilkan bagian About Coach di halaman Home</label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan profil coach</button>
            </div>
        </form>

        <aside class="ab-side">
            <h3>Tampil di Home</h3>
            <p>Bagian "About Coach" di halaman Home menampilkan:</p>
            <ul>
                <li>Foto, nama, jabatan</li>
                <li>Tentang coach</li>
                <li>Tahun pengalaman, murid, jumlah sertifikasi</li>
                <li>Riwayat pengalaman (timeline)</li>
                <li>Sertifikasi & prestasi</li>
                <li>Keahlian & motto</li>
            </ul>
            <a href="{{ route('home') }}#coach" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat di Home</a>
        </aside>
    </div>
@endsection

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
