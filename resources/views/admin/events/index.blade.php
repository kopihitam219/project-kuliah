@extends('admin.layouts.panel')

@section('title', 'Kelola Event')

@section('content')
    @php
        $tabs = [
            ''         => ['label' => 'Semua',     'count' => $counts['all']],
            'upcoming' => ['label' => 'Mendatang', 'count' => $counts['upcoming']],
            'past'     => ['label' => 'Selesai',   'count' => $counts['past']],
        ];
    @endphp

    <section class="page-head">
        <div>
            <h1>Kelola Event</h1>
            <p>Event yang aktif dan belum lewat tanggalnya tampil di halaman Event untuk customer dan visitor, diurutkan dari tanggal terdekat.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('event') }}" target="_blank" rel="noopener" class="btn btn-outline">◉ Lihat halaman publik</a>
            <a href="{{ route('admin.events.create') }}" class="btn btn-primary">＋ Tambah event</a>
        </div>
    </section>

    <nav class="tabs">
        @foreach ($tabs as $tabFilter => $tab)
            <a href="{{ route('admin.events.index', $tabFilter ? ['filter' => $tabFilter] : []) }}"
               class="tab {{ ($filter ?? '') === $tabFilter ? 'active' : '' }}">
                {{ $tab['label'] }}<span>{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </nav>

    @if ($events->isEmpty())
        <div class="empty-state">
            Belum ada event{{ $filter === 'past' ? ' yang sudah selesai' : ($filter === 'upcoming' ? ' mendatang' : '') }}.
            <br>
            <a href="{{ route('admin.events.create') }}" class="btn btn-primary">＋ Tambah event</a>
        </div>
    @else
        <section class="event-grid-admin">
            @foreach ($events as $event)
                @php
                    $badgeClass = $event->isPast() ? 'past' : $event->registration_status;
                @endphp

                <article class="event-item {{ $event->is_active ? '' : 'inactive' }}">
                    <div class="event-poster">
                        @if ($event->poster_url)
                            <img src="{{ $event->poster_url }}" alt="{{ $event->title }}" loading="lazy">
                        @else
                            <div class="no-thumb">▦</div>
                        @endif

                        <div class="badges">
                            <span class="badge {{ $badgeClass }}">{{ $event->status_label }}</span>
                            <span class="badge {{ $event->is_active ? 'active' : 'inactive' }}">
                                {{ $event->is_active ? 'Tampil' : 'Disembunyikan' }}
                            </span>
                        </div>
                    </div>

                    <div class="item-body">
                        <strong>{{ $event->title }}</strong>

                        <div class="event-facts">
                            <span><b>Tanggal:</b> {{ $event->date_label }}</span>
                            <span><b>Waktu:</b> {{ $event->time_range }}</span>
                            <span><b>Lokasi:</b> {{ $event->location ?: '-' }}</span>
                        </div>

                        <div class="event-price">{{ $event->price_label }}</div>
                    </div>

                    <div class="item-actions">
                        <a href="{{ route('admin.events.edit', $event) }}">Edit</a>

                        <form method="POST" action="{{ route('admin.events.update', $event) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="quick_toggle" value="1">
                            <button type="submit">{{ $event->is_active ? 'Sembunyikan' : 'Tampilkan' }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                              data-confirm="Hapus event &quot;{{ $event->title }}&quot;? Posternya juga akan dihapus.">
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
