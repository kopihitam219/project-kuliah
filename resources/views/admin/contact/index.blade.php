@extends('admin.layouts.panel')

@section('title', 'Kelola Contact')

@section('content')
    <section class="page-head">
        <div>
            <h1>Kelola Contact</h1>
            <p>Perubahan di halaman ini langsung tampil di halaman Contact untuk customer dan visitor.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('contact') }}" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat halaman publik</a>
        </div>
    </section>

    {{-- ================= INFORMASI KONTAK ================= --}}
    <form method="POST" action="{{ route('admin.contact.update') }}" class="section-card" id="settingForm">
        @csrf
        @method('PUT')

        <div class="section-card-head">
            <div>
                <h2>Informasi kontak</h2>
                <p>Dipakai untuk tombol WhatsApp, email, dan form pesan di halaman Contact.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="error-box">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="field">
            <label for="fDescription">Teks pengantar</label>
            <textarea name="description" id="fDescription" maxlength="500" class="input"
                      placeholder="Kalimat singkat di bawah judul CONTACT US">{{ old('description', $setting->description) }}</textarea>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="fWhatsapp">Nomor WhatsApp</label>
                <input type="text" name="whatsapp" id="fWhatsapp" maxlength="30" required
                       class="input @error('whatsapp') is-invalid @enderror"
                       value="{{ old('whatsapp', $setting->whatsapp) }}" placeholder="0858 8680 3126">
                <p class="field-hint" id="waPreview">
                    Link: {{ $setting->whatsapp_url ?? '-' }}
                </p>
            </div>

            <div class="field">
                <label for="fEmail">Email</label>
                <input type="email" name="email" id="fEmail" maxlength="255" required
                       class="input @error('email') is-invalid @enderror"
                       value="{{ old('email', $setting->email) }}" placeholder="info@golfbookinglesson.com">
            </div>
        </div>

        <div class="field">
            <label for="fHours">Jam operasional</label>
            <input type="text" name="opening_hours" id="fHours" maxlength="100" class="input"
                   value="{{ old('opening_hours', $setting->opening_hours) }}" placeholder="Senin – Minggu, 08:00 – 18:00">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan informasi kontak</button>
        </div>
    </form>

    {{-- ================= LOKASI ================= --}}
    <section class="section-card">
        <div class="section-card-head">
            <div>
                <h2>Lokasi latihan</h2>
                <p>Lokasi berstatus "Tampil" muncul sebagai pilihan lokasi dan peta di halaman Contact.</p>
            </div>

            <a href="{{ route('admin.contact.locations.create') }}" class="btn btn-primary">＋ Tambah lokasi</a>
        </div>

        @if ($locations->isEmpty())
            <div class="empty-state">Belum ada lokasi latihan.</div>
        @else
            <div class="location-rows">
                @foreach ($locations as $location)
                    <article class="location-row {{ $location->is_active ? '' : 'inactive' }}">
                        <span class="location-index">{{ $location->sort_order }}</span>

                        <div>
                            <strong>
                                {{ $location->name }}
                                @unless ($location->is_active)
                                    <span class="badge inactive">Disembunyikan</span>
                                @endunless
                            </strong>
                            @if ($location->area)
                                <div class="meta">📍 {{ $location->area }}</div>
                            @endif
                            @if ($location->note)
                                <div class="note">{{ $location->note }}</div>
                            @endif
                        </div>

                        <div class="actions">
                            <a href="{{ $location->map_link }}" target="_blank" rel="noopener">Peta</a>
                            <a href="{{ route('admin.contact.locations.edit', $location) }}">Edit</a>

                            <form method="POST" action="{{ route('admin.contact.locations.update', $location) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="quick_toggle" value="1">
                                <button type="submit">{{ $location->is_active ? 'Sembunyikan' : 'Tampilkan' }}</button>
                            </form>

                            <form method="POST" action="{{ route('admin.contact.locations.destroy', $location) }}"
                                  data-confirm="Hapus lokasi &quot;{{ $location->name }}&quot;?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="danger">Hapus</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!confirm(form.dataset.confirm)) event.preventDefault();
            });
        });

        // Pratinjau link WhatsApp saat nomor diketik
        (() => {
            const input   = document.getElementById('fWhatsapp');
            const preview = document.getElementById('waPreview');

            input.addEventListener('input', () => {
                let digits = input.value.replace(/\D+/g, '');
                if (digits.startsWith('0')) digits = '62' + digits.slice(1);
                preview.textContent = 'Link: ' + (digits ? 'https://wa.me/' + digits : '-');
            });
        })();
    </script>
@endpush
