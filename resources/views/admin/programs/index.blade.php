@extends('admin.layouts.panel')

@section('title', 'Kelola Program')

@section('content')
    <section class="page-head">
        <div>
            <h1>Kelola Program</h1>
            <p>Program berstatus "Tampil" muncul di halaman Program untuk customer dan visitor, diurutkan berdasarkan nomor urutan.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('program') }}" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat halaman publik</a>
            <a href="{{ route('admin.programs.create') }}" class="btn btn-primary">＋ Tambah program</a>
        </div>
    </section>

    @if ($programs->isEmpty())
        <div class="empty-state">
            Belum ada program.
            <br>
            <a href="{{ route('admin.programs.create') }}" class="btn btn-primary">＋ Tambah program</a>
        </div>
    @else
        <section class="program-grid-admin">
            @foreach ($programs as $program)
                <article class="program-item {{ $program->is_active ? '' : 'inactive' }}">
                    <div class="program-banner"
                         style="--card-img: url('{{ $program->image_url }}'); --card-pos: {{ $program->image_css_position }};">
                        <div class="badges">
                            <span class="badge type">Urutan {{ $program->sort_order }}</span>
                            <span class="badge {{ $program->is_active ? 'active' : 'inactive' }}">
                                {{ $program->is_active ? 'Tampil' : 'Disembunyikan' }}
                            </span>
                        </div>
                    </div>

                    <div class="item-body">
                        <div class="program-level">{{ $program->level }}</div>
                        <strong>{{ $program->name }}</strong>

                        @if ($program->description)
                            <p class="program-desc">{{ $program->description }}</p>
                        @endif

                        @if (! empty($program->features))
                            <ul class="feature-list">
                                @foreach ($program->features as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="item-actions">
                        <a href="{{ route('admin.programs.edit', $program) }}">Edit</a>

                        <form method="POST" action="{{ route('admin.programs.update', $program) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="quick_toggle" value="1">
                            <button type="submit">{{ $program->is_active ? 'Sembunyikan' : 'Tampilkan' }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.programs.destroy', $program) }}"
                              data-confirm="Hapus program &quot;{{ $program->name }}&quot;?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="danger">Hapus</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!confirm(form.dataset.confirm)) event.preventDefault();
            });
        });
    </script>
@endpush
