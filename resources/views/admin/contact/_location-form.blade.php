{{--
    Dipakai oleh location-create & location-edit
    Variabel: $location (model), $action (url), $isEdit (bool)
--}}
@php
    $isActive = (bool) old('is_active', $location->is_active ?? true);
@endphp

<form method="POST" action="{{ $action }}" class="form-card" id="locationForm">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

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
        <label for="fName">Nama lokasi</label>
        <input type="text" name="name" id="fName" maxlength="100" required
               class="input @error('name') is-invalid @enderror"
               value="{{ old('name', $location->name) }}" placeholder="Contoh: Driving Range Rawamangun">
    </div>

    <div class="field-row">
        <div class="field">
            <label for="fArea">Area</label>
            <input type="text" name="area" id="fArea" maxlength="100" class="input"
                   value="{{ old('area', $location->area) }}" placeholder="Contoh: Rawamangun, Jakarta Timur">
        </div>

        <div class="field">
            <label for="fNote">Keterangan singkat</label>
            <input type="text" name="note" id="fNote" maxlength="255" class="input"
                   value="{{ old('note', $location->note) }}" placeholder="Contoh: Cocok untuk lesson pagi dan sore">
        </div>
    </div>

    <div class="field">
        <label for="fMaps">Pencarian Google Maps</label>
        <div class="inline-actions">
            <input type="text" name="maps_query" id="fMaps" maxlength="255" class="input"
                   value="{{ old('maps_query', $location->maps_query) }}" placeholder="Nama tempat atau alamat lengkap">
            <button type="button" class="btn btn-outline" id="btnCheckMap">Cek di peta</button>
        </div>
        <p class="field-hint">Isi nama tempat atau alamat lengkap. Jika dikosongkan, memakai nama + area. Pastikan pin di pratinjau menunjuk ke tempat yang benar.</p>

        <iframe id="mapPreview" class="map-preview" loading="lazy" title="Pratinjau peta"
                src="{{ $location->exists ? $location->map_embed_url : 'about:blank' }}"></iframe>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="fSort">Urutan tampil</label>
            <input type="number" name="sort_order" id="fSort" min="0" class="input"
                   value="{{ old('sort_order', $location->sort_order ?? 0) }}">
            <p class="field-hint">Angka kecil tampil lebih dulu dan menjadi peta awal.</p>
        </div>

        <div class="field">
            <span class="field-label">Status</span>
            <input type="hidden" name="is_active" value="0">
            <label class="checkbox">
                <input type="checkbox" name="is_active" value="1" @checked($isActive)>
                Tampilkan di halaman Contact
            </label>
        </div>
    </div>

    <div class="form-actions">
        <a href="{{ route('admin.contact.index') }}" class="btn btn-ghost">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan perubahan' : 'Tambah lokasi' }}</button>
    </div>
</form>

@push('scripts')
    <script>
        (() => {
            const name    = document.getElementById('fName');
            const area    = document.getElementById('fArea');
            const maps    = document.getElementById('fMaps');
            const preview = document.getElementById('mapPreview');

            function refreshMap() {
                const query = maps.value.trim() || (name.value.trim() + ' ' + area.value.trim()).trim();
                if (!query) return;
                preview.src = 'https://maps.google.com/maps?q=' + encodeURIComponent(query) + '&z=15&output=embed';
            }

            document.getElementById('btnCheckMap').addEventListener('click', refreshMap);
            if (preview.getAttribute('src') === 'about:blank') refreshMap();
        })();
    </script>
@endpush
