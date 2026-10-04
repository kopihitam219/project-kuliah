@extends('admin.layouts.panel')

@section('title', $user->name)

@section('content')
    @php
        $parts    = preg_split('/\s+/', trim($user->name), -1, PREG_SPLIT_NO_EMPTY);
        $initials = mb_strtoupper(count($parts) > 1
            ? mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1)
            : mb_substr($parts[0] ?? 'C', 0, 2));
    @endphp

    <section class="page-head">
        <div>
            <h1>Detail member</h1>
            <p>Profil dan riwayat booking {{ $user->name }}.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.customers.index', ['tab' => 'member']) }}" class="btn btn-ghost">← Kembali ke daftar</a>
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
                <span class="person-avatar">{{ $initials }}</span>
                <div>
                    <h2>{{ $user->name }}</h2>
                    <span class="status-pill {{ $user->email_verified_at ? 'booked' : 'pending' }}">
                        {{ $user->email_verified_at ? 'Email terverifikasi' : 'Belum verifikasi email' }}
                    </span>
                </div>
            </div>

            <div class="profile-list">
                <div><span>Email</span><span>{{ $user->email }}</span></div>
                <div><span>No. HP</span><span>{{ $phone ?: '—' }}</span></div>
                <div><span>Bergabung</span><span>{{ $user->created_at?->locale('id')->translatedFormat('d F Y') ?? '—' }}</span></div>
                <div><span>Tipe</span><span>Member (punya akun)</span></div>
            </div>

            <div class="profile-actions">
                @if ($waUrl)
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-primary">☎ Hubungi via WhatsApp</a>
                @endif
                <a href="mailto:{{ $user->email }}" class="btn btn-outline">✉ Kirim email</a>
                <a href="{{ route('admin.offline-booking.create') }}" class="btn btn-ghost">＋ Buat booking untuk member ini</a>
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
