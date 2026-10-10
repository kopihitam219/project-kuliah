@extends('admin.layouts.panel')

@section('title', 'Kelola Coach')

@section('content')
    <style>
        .coach-thumb { position: relative; height: 150px; display: grid; place-items: center; background: linear-gradient(160deg, #163d25, #04100b); background-size: cover; background-position: center top; }
        .coach-thumb .initial { width: 64px; height: 64px; display: grid; place-items: center; border-radius: 20px; background: rgba(156, 255, 0, .15); color: var(--lime); font-size: 26px; font-weight: 900; }
        .coach-thumb .badges { position: absolute; top: 10px; left: 10px; right: 10px; display: flex; justify-content: space-between; }
        .coach-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .coach-meta span { padding: 3px 8px; border-radius: 99px; background: rgba(156, 255, 0, .1); color: var(--lime); font-size: 11px; font-weight: 700; }
    </style>

    <section class="page-head">
        <div>
            <h1>Kelola Coach</h1>
            <p>Coach berstatus "Tampil" muncul di bagian "Kenali Coach Kami" di halaman Home. Hanya informasi, customer tidak memilih coach saat booking.</p>
        </div>
        <div class="head-actions">
            <a href="{{ route('home') }}#coach" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat di Home</a>
            <a href="{{ route('admin.coaches.create') }}" class="btn btn-primary">＋ Tambah coach</a>
        </div>
    </section>

    @if ($coaches->isEmpty())
        <div class="empty-state">
            Belum ada coach.<br>
            <a href="{{ route('admin.coaches.create') }}" class="btn btn-primary">＋ Tambah coach</a>
        </div>
    @else
        <section class="program-grid-admin">
            @foreach ($coaches as $coach)
                <article class="program-item {{ $coach->is_active ? '' : 'inactive' }}">
                    <div class="coach-thumb" @if ($coach->photo_url) style="background-image: linear-gradient(to bottom, rgba(0,0,0,.05), rgba(4,18,11,.85)), url('{{ $coach->photo_url }}')" @endif>
                        @unless ($coach->photo_url)<span class="initial">{{ mb_strtoupper(mb_substr(preg_replace('/^coach\s+/i', '', $coach->name), 0, 1)) }}</span>@endunless
                        <div class="badges">
                            <span class="badge type">Urutan {{ $coach->sort_order }}</span>
                            <span class="badge {{ $coach->is_active ? 'active' : 'inactive' }}">{{ $coach->is_active ? 'Tampil' : 'Disembunyikan' }}</span>
                        </div>
                    </div>
                    <div class="item-body">
                        <div class="program-level">{{ $coach->badge ?: 'Coach' }}</div>
                        <strong>{{ $coach->name }}</strong>
                        @if ($coach->role)<p class="program-desc">{{ $coach->role }}</p>@endif
                        <div class="coach-meta">
                            @if ($coach->years_experience)<span>{{ $coach->years_experience }} thn pengalaman</span>@endif
                            @if ($coach->students)<span>{{ $coach->students }} murid</span>@endif
                            @foreach (($coach->skills ?? []) as $skill)<span>{{ $skill }}</span>@endforeach
                        </div>
                    </div>
                    <div class="item-actions">
                        <a href="{{ route('admin.coaches.edit', $coach) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.coaches.update', $coach) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="quick_toggle" value="1">
                            <button type="submit">{{ $coach->is_active ? 'Sembunyikan' : 'Tampilkan' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.coaches.destroy', $coach) }}" data-confirm="Hapus coach &quot;{{ $coach->name }}&quot;?">
                            @csrf @method('DELETE')
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
            form.addEventListener('submit', (event) => { if (!confirm(form.dataset.confirm)) event.preventDefault(); });
        });
    </script>
@endpush
