@extends('admin.layouts.panel')

@section('title', $user->name)

@push('styles')
    <style>
        .alert-warning { padding: 11px 14px; border: 1px solid rgba(255, 198, 45, .4); border-radius: 9px; background: rgba(255, 198, 45, .08); color: #ffd56a; font-size: 12px; line-height: 1.5; }
        .btn-danger { border-color: rgba(216, 35, 61, .7); background: rgba(216, 35, 61, .12); color: #ff8a9a; }
        .btn-danger:hover { background: #d8233d; color: #fff; }
    </style>
@endpush

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

    @if (session('new_account'))
        <section class="form-card" style="margin-bottom: 16px; border-color: rgba(156, 255, 0, .45)">
            <h2 style="font-size: 16px; font-weight: 900">Akun berhasil dibuat</h2>
            <p class="field-hint" style="margin: 4px 0 12px">
                Berikan data login ini ke customer. Password hanya ditampilkan <strong>sekali</strong>, setelah halaman ditutup tidak bisa dilihat lagi.
                Sarankan customer mengganti password setelah login pertama.
            </p>
            <div class="profile-list" style="max-width: 520px">
                <div><span>Email</span><span>{{ session('new_account')['email'] }}</span></div>
                <div><span>Password</span><span style="font-family: Consolas, Menlo, monospace; color: var(--lime)" id="newPassword">{{ session('new_account')['password'] }}</span></div>
            </div>
            <button type="button" class="btn btn-outline" style="margin-top: 12px" id="copyAccount"
                    data-text="Email: {{ session('new_account')['email'] }}&#10;Password: {{ session('new_account')['password'] }}&#10;Login: {{ route('login') }}">
                Salin data login
            </button>
        </section>
    @endif

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
                <div>
                    <span>Terakhir aktif</span>
                    <span>
                        {{ $user->last_active_at ? $user->last_active_at->locale('id')->diffForHumans() : '—' }}
                    </span>
                </div>
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

            <section class="form-card" style="margin-top: 16px; border-color: rgba(216, 35, 61, .35)">
                <h2 style="font-size: 16px; font-weight: 900; color: #ff9aa6">Hapus akun customer</h2>
                <p class="field-hint" style="margin: 4px 0 12px">
                    Akun dihapus permanen dan customer tidak bisa login lagi.
                    Riwayat booking & pembayaran <strong>tetap tersimpan</strong> atas nama {{ $user->name }}, sehingga dashboard dan laporan tetap lengkap.
                </p>

                @if ($deleteBlocked)
                    <div class="alert alert-warning" style="margin: 0">{{ $deleteBlocked }}</div>
                @else
                    <form method="POST" action="{{ route('admin.customers.destroy', $user) }}" id="deleteCustomerForm"
                          data-name="{{ $user->name }}" data-email="{{ $user->email }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Hapus akun {{ $user->name }}</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Hapus akun: konfirmasi dengan mengetik email customer
        const deleteForm = document.getElementById('deleteCustomerForm');
        if (deleteForm) {
            deleteForm.addEventListener('submit', (event) => {
                const typed = prompt(
                    'Hapus akun ' + deleteForm.dataset.name + ' secara permanen?\n\n'
                    + 'Ketik email customer untuk konfirmasi:\n' + deleteForm.dataset.email
                );

                if (typed === null || typed.trim().toLowerCase() !== deleteForm.dataset.email.toLowerCase()) {
                    event.preventDefault();
                    if (typed !== null) alert('Email tidak sama. Akun tidak dihapus.');
                }
            });
        }

        // Salin data login akun baru
        const copyBtn = document.getElementById('copyAccount');
        if (copyBtn) {
            copyBtn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(copyBtn.dataset.text);
                    copyBtn.textContent = '✓ Tersalin';
                } catch (e) {
                    alert(copyBtn.dataset.text);
                }
            });
        }
    </script>
@endpush
