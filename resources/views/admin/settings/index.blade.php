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
