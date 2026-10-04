@extends(auth()->user()->role === 'admin' ? 'admin.layouts.panel' : 'notifications.customer-layout')

@section('title', $item->data['title'] ?? 'Notifikasi')

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
            'blocked'     => ['icon' => '⊘',  'label' => 'Jadwal ditutup'],
            'reopened'    => ['icon' => '↺',  'label' => 'Jadwal dibuka'],
        ];

        $event   = $item->data['event'] ?? 'created';
        $meta    = $nfEvents[$event] ?? ['icon' => '•', 'label' => 'Info'];
        $isAdmin = auth()->user()->role === 'admin';

        $bookingStatus = [
            'pending'   => 'Pending (menunggu approval)',
            'booked'    => 'Booked (disetujui)',
            'cancelled' => 'Dibatalkan',
            'rejected'  => 'Ditolak',
        ];

        $customerName = $booking
            ? ($booking->user->name ?? $booking->offline_customer_name ?? 'Customer')
            : null;
    @endphp

    <div class="nf-wrap">

        <div class="nf-head">
            <a href="{{ route('notifications.index') }}" class="nf-btn nf-btn-ghost">← Semua notifikasi</a>
        </div>

        <article class="nf-detail">
            <div class="nf-detail-head">
                <span class="nf-icon {{ $event }}">{{ $meta['icon'] }}</span>
                <div>
                    <h1>{{ $item->data['title'] ?? 'Notifikasi' }}</h1>
                    <div class="nf-time">
                        {{ $meta['label'] }} ·
                        {{ $item->created_at->locale('id')->translatedFormat('l, d F Y · H:i') }}
                        ({{ $item->created_at->locale('id')->diffForHumans() }})
                    </div>
                </div>
            </div>

            <p class="nf-detail-message">{{ $item->data['message'] ?? '' }}</p>

            @if ($booking)
                <div class="nf-section-title">Detail booking</div>

                <div class="nf-rows">
                    @if ($isAdmin)
                        <div class="nf-row"><span>Customer</span><span>{{ $customerName }}</span></div>
                    @endif
                    <div class="nf-row">
                        <span>Tanggal</span>
                        <span>{{ $booking->booking_date?->locale('id')->translatedFormat('l, d F Y') ?? '-' }}</span>
                    </div>
                    <div class="nf-row">
                        <span>Jam</span>
                        <span>{{ substr((string) $booking->start_time, 0, 5) }} – {{ substr((string) $booking->end_time, 0, 5) }}</span>
                    </div>
                    <div class="nf-row">
                        <span>Status booking (saat ini)</span>
                        <span>{{ $bookingStatus[$booking->status] ?? ucfirst($booking->status) }}</span>
                    </div>
                    @if ($booking->source === 'offline')
                        <div class="nf-row">
                            <span>Pembayaran</span>
                            <span>Lunas · Dibayar di tempat (booking offline)</span>
                        </div>
                    @elseif ($payment)
                        <div class="nf-row">
                            <span>Pembayaran</span>
                            <span>
                                {{ $payment->status_label }} · {{ $payment->amount_label }}
                                @if ($payment->method_label) ({{ $payment->method_label }}) @endif
                            </span>
                        </div>
                    @endif
                </div>
            @elseif (! empty($item->data['booking_id']))
                <div class="nf-section-title">Detail booking</div>
                <p class="nf-time">Booking terkait sudah tidak tersedia.</p>
            @endif

            <div class="nf-detail-actions">
                @if ($booking)
                    @if ($isAdmin)
                        <a href="{{ route('admin.dashboard') }}#booking-list" class="nf-btn nf-btn-primary">Buka daftar booking</a>
                    @else
                        @if ($booking->source !== 'offline' && (! $payment || $payment->status !== 'paid') && in_array($booking->status, ['pending', 'booked'], true) && \Illuminate\Support\Facades\Route::has('payment.booking'))
                            <a href="{{ route('payment.booking', $booking) }}" class="nf-btn nf-btn-primary">Bayar sekarang</a>
                        @endif
                        <a href="{{ route('booking', ['date' => $booking->booking_date?->format('Y-m-d')]) }}" class="nf-btn nf-btn-outline">Lihat booking</a>
                    @endif
                @endif

                <form method="POST" action="{{ route('notifications.destroy', $item->id) }}" id="deleteForm" style="margin-left: auto;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="nf-btn nf-btn-ghost">Hapus notifikasi</button>
                </form>
            </div>
        </article>

    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('deleteForm')?.addEventListener('submit', (event) => {
            if (!confirm('Hapus notifikasi ini?')) event.preventDefault();
        });
    </script>
@endpush
