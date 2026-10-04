@extends('admin.layouts.panel')

@section('title', $name)

@section('content')
    @php
        $parts    = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        $initials = mb_strtoupper(count($parts) > 1
            ? mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1)
            : mb_substr($parts[0] ?? 'C', 0, 2));
    @endphp

    <section class="page-head">
        <div>
            <h1>Detail customer offline</h1>
            <p>Customer tanpa akun, dikenali dari nomor HP pada booking offline.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.customers.index', ['tab' => 'offline']) }}" class="btn btn-ghost">← Kembali ke daftar</a>
        </div>
    </section>

    <section class="stat-row">
        <div class="stat-box"><span>Total booking</span><strong>{{ $summary['total'] }}</strong></div>
        <div class="stat-box"><span>Booked</span><strong>{{ $summary['booked'] }}</strong></div>
        <div class="stat-box"><span>Pending</span><strong>{{ $summary['pending'] }}</strong></div>
        <div class="stat-box"><span>Dibatalkan / ditolak</span><strong>{{ $summary['cancelled'] }}</strong></div>
    </section>

    <div class="profile-grid">
        <aside class="profile-card">
            <div class="profile-head">
                <span class="person-avatar offline">{{ $initials }}</span>
                <div>
                    <h2>{{ $name }}</h2>
                    <span class="status-pill neutral">Customer offline</span>
                </div>
            </div>

            <div class="profile-list">
                <div><span>No. HP</span><span>{{ $phone }}</span></div>
                <div><span>Email</span><span>{{ $email ?: '—' }}</span></div>
                <div><span>Booking pertama</span><span>{{ $bookings->last()?->created_at?->locale('id')->translatedFormat('d F Y') ?? '—' }}</span></div>
                <div><span>Tipe</span><span>Offline (tanpa akun)</span></div>
            </div>

            <div class="profile-actions">
                @if ($waUrl)
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-primary">☎ Hubungi via WhatsApp</a>
                @endif
                @if ($email)
                    <a href="mailto:{{ $email }}" class="btn btn-outline">✉ Kirim email</a>
                @endif
                <a href="{{ route('admin.offline-booking.create') }}" class="btn btn-ghost">＋ Buat booking lagi</a>
            </div>
        </aside>

        <div>
            @if ($upcoming)
                <div class="upcoming-card">
                    <span>Jadwal terdekat</span>
                    <strong>
                        {{ $upcoming->booking_date->locale('id')->translatedFormat('l, d F Y') }}
                        · {{ substr($upcoming->start_time, 0, 5) }} – {{ substr($upcoming->end_time, 0, 5) }}
                    </strong>
                    <small>Status: {{ $statuses[$upcoming->status] ?? ucfirst($upcoming->status) }}</small>
                </div>
            @endif

            @include('admin.customers._history', ['bookings' => $bookings, 'statuses' => $statuses])
        </div>
    </div>
@endsection
