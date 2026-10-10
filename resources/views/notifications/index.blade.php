@extends(auth()->user()->role === 'admin' ? 'admin.layouts.panel' : 'notifications.customer-layout')

@section('title', 'Notifikasi')

@section('content')
    @include('notifications._styles')

    @php
        $nfEvents = [
            'created'     => ['icon' => '＋', 'label' => 'Booking baru'],
            'rescheduled' => ['icon' => '↻',  'label' => 'Reschedule'],
            'cancelled'   => ['icon' => '×',  'label' => 'Dibatalkan'],
            'rejected'    => ['icon' => '×',  'label' => 'Ditolak'],
            'approved'    => ['icon' => '✓',  'label' => 'Disetujui'],
            'paid'        => ['icon' => 'Rp', 'label' => 'Pembayaran'],
            'chat'        => ['icon' => '✉',  'label' => 'Chat'],
            'broadcast'   => ['icon' => '📢', 'label' => 'Pengumuman'],
        ];
    @endphp

    <div class="nf-wrap">

        <div class="nf-head">
            <div>
                <h1>Notifikasi</h1>
                <p>
                    @if ($unreadCount > 0)
                        Ada {{ $unreadCount }} notifikasi yang belum dibaca.
                    @else
                        Semua notifikasi sudah dibaca.
                    @endif
                </p>
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="nf-btn nf-btn-outline">✓ Tandai semua dibaca</button>
                </form>
            @endif
        </div>

        @if (session('success') && auth()->user()->role !== 'admin')
            <div class="nf-flash">{{ session('success') }}</div>
        @endif

        <nav class="nf-tabs">
            <a href="{{ route('notifications.index') }}" class="nf-tab {{ $filter === 'all' ? 'active' : '' }}">
                Semua<span>{{ $totalCount }}</span>
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="nf-tab {{ $filter === 'unread' ? 'active' : '' }}">
                Belum dibaca<span>{{ $unreadCount }}</span>
            </a>
        </nav>

        @if ($notifications->isEmpty())
            <div class="nf-empty">
                {{ $filter === 'unread' ? 'Tidak ada notifikasi yang belum dibaca.' : 'Belum ada notifikasi.' }}
            </div>
        @else
            <div class="nf-list">
                @foreach ($notifications as $notification)
                    @php
                        $event = $notification->data['event'] ?? 'created';
                        $meta  = $nfEvents[$event] ?? ['icon' => '•', 'label' => 'Info'];
                    @endphp

                    <article class="nf-card {{ $notification->read_at ? '' : 'unread' }}">
                        <span class="nf-icon {{ $event }}">{{ $meta['icon'] }}</span>

                        <div>
                            <div class="nf-title">
                                {{ $notification->data['title'] ?? 'Notifikasi' }}
                                <span class="nf-badge">{{ $meta['label'] }}</span>
                                @unless ($notification->read_at)
                                    <span class="nf-new">Baru</span>
                                @endunless
                            </div>

                            <p class="nf-message">{{ $notification->data['message'] ?? '' }}</p>

                            <div class="nf-time">
                                {{ $notification->created_at->locale('id')->diffForHumans() }}
                                · {{ $notification->created_at->locale('id')->translatedFormat('d M Y, H:i') }}
                            </div>
                        </div>

                        <div class="nf-actions">
                            <a href="{{ route('notifications.show', $notification->id) }}" class="nf-btn nf-btn-primary">Lihat</a>

                            <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="nf-delete">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="nf-btn nf-btn-ghost" title="Hapus notifikasi">Hapus</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($notifications->hasPages())
                <div class="nf-pager">
                    <span>Halaman {{ $notifications->currentPage() }} dari {{ $notifications->lastPage() }}</span>
                    <div>
                        @if (! $notifications->onFirstPage())
                            <a href="{{ $notifications->previousPageUrl() }}" class="nf-btn nf-btn-ghost">← Sebelumnya</a>
                        @endif
                        @if ($notifications->hasMorePages())
                            <a href="{{ $notifications->nextPageUrl() }}" class="nf-btn nf-btn-ghost">Berikutnya →</a>
                        @endif
                    </div>
                </div>
            @endif
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.nf-delete').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!confirm('Hapus notifikasi ini?')) event.preventDefault();
            });
        });
    </script>
@endpush
