@extends('admin.layouts.panel')

@section('title', 'Customer')

@section('content')
    @php
        $initials = function (?string $name) {
            $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
            if (! $parts) return 'C';
            return mb_strtoupper(count($parts) > 1
                ? mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1)
                : mb_substr($parts[0], 0, 2));
        };

        $formatDate = fn ($date) => $date
            ? \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d M Y')
            : '—';
    @endphp

    <section class="page-head">
        <div>
            <h1>Customer</h1>
            <p>Semua customer Golf Booking Lesson: member yang punya akun dan customer offline dari booking admin.</p>
        </div>

        <div class="head-actions">
            <a href="{{ route('admin.offline-booking.create') }}" class="btn btn-primary">＋ Buat booking offline</a>
        </div>
    </section>

    {{-- Ringkasan --}}
    <section class="stat-row">
        <div class="stat-box">
            <span>Total member</span>
            <strong>{{ $stats['members'] }}</strong>
            <small>Punya akun</small>
        </div>
        <div class="stat-box">
            <span>Member baru</span>
            <strong>{{ $stats['new_month'] }}</strong>
            <small>Bulan ini</small>
        </div>
        <div class="stat-box">
            <span>Member aktif</span>
            <strong>{{ $stats['active_30'] }}</strong>
            <small>Booking 30 hari terakhir</small>
        </div>
        <div class="stat-box">
            <span>Customer offline</span>
            <strong>{{ $stats['offline'] }}</strong>
            <small>Tanpa akun</small>
        </div>
    </section>

    {{-- Tab --}}
    <nav class="tabs">
        <a href="{{ route('admin.customers.index', ['tab' => 'member']) }}" class="tab {{ $tab === 'member' ? 'active' : '' }}">
            Member<span>{{ $stats['members'] }}</span>
        </a>
        <a href="{{ route('admin.customers.index', ['tab' => 'offline']) }}" class="tab {{ $tab === 'offline' ? 'active' : '' }}">
            Offline<span>{{ $stats['offline'] }}</span>
        </a>
    </nav>

    {{-- Pencarian & urutan --}}
    <form method="GET" action="{{ route('admin.customers.index') }}" class="toolbar">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <input type="search" name="q" value="{{ $search }}" class="input"
               placeholder="{{ $tab === 'member' ? 'Cari nama, email, atau nomor HP...' : 'Cari nama atau nomor HP...' }}">

        <select name="sort" class="input" onchange="this.form.submit()">
            @foreach ($sorts as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary">Cari</button>

        @if ($search !== '' || $sort !== 'newest')
            <a href="{{ route('admin.customers.index', ['tab' => $tab]) }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    {{-- Tabel --}}
    <div class="table-card">
        @if ($customers->isEmpty())
            <div class="empty-state" style="border: 0;">
                @if ($search !== '')
                    Tidak ada customer yang cocok dengan "{{ $search }}".
                @elseif ($tab === 'member')
                    Belum ada member terdaftar.
                @else
                    Belum ada customer offline. Customer offline muncul otomatis setelah admin membuat booking offline.
                @endif
            </div>
        @else
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>No. HP</th>
                            <th>{{ $tab === 'member' ? 'Bergabung' : 'Booking pertama' }}</th>
                            <th class="num">Total booking</th>
                            <th>Booking terakhir</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            @if ($tab === 'member')
                                @php
                                    $phone = $customer->getAttributes()['phone'] ?? null;
                                    $waUrl = \App\Http\Controllers\AdminCustomerController::whatsappUrl($phone);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="person">
                                            <span class="person-avatar">{{ $initials($customer->name) }}</span>
                                            <div>
                                                <strong>{{ $customer->name }}</strong>
                                                <small>{{ $customer->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $phone ?: '—' }}</td>
                                    <td>{{ $formatDate($customer->created_at) }}</td>
                                    <td class="num"><strong>{{ $customer->bookings_count }}</strong></td>
                                    <td>{{ $formatDate($customer->last_booking_date) }}</td>
                                    <td>
                                        <div class="row-actions">
                                            @if ($waUrl)
                                                <a href="{{ $waUrl }}" target="_blank" rel="noopener">WhatsApp</a>
                                            @endif
                                            <a href="{{ route('admin.customers.show', $customer) }}">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                @php
                                    $waUrl = \App\Http\Controllers\AdminCustomerController::whatsappUrl($customer->phone);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="person">
                                            <span class="person-avatar offline">{{ $initials($customer->name) }}</span>
                                            <div>
                                                <strong>{{ $customer->name ?: 'Customer offline' }}</strong>
                                                <small>{{ $customer->email ?: 'Tanpa email' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $customer->phone }}</td>
                                    <td>{{ $formatDate($customer->first_seen) }}</td>
                                    <td class="num"><strong>{{ $customer->bookings_count }}</strong></td>
                                    <td>{{ $formatDate($customer->last_booking_date) }}</td>
                                    <td>
                                        <div class="row-actions">
                                            @if ($waUrl)
                                                <a href="{{ $waUrl }}" target="_blank" rel="noopener">WhatsApp</a>
                                            @endif
                                            <a href="{{ route('admin.customers.offline', ['phone' => $customer->phone]) }}">Detail</a>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.customers._pager', ['paginator' => $customers])
        @endif
    </div>
@endsection
