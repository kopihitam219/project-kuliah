@extends('notifications.customer-layout')

@section('title', 'Profil saya')

@section('content')
<style>
    html { background: #04100b; scroll-padding-bottom: 110px; }
    .pf { display: grid; gap: 16px; }
    .pf h1 { font-size: clamp(26px, 5vw, 34px); font-weight: 900; letter-spacing: -1px; }
    .pf h1 span { color: #9cff38; }
    .pf-sub { margin-top: 4px; color: rgba(244, 247, 244, .6); font-size: 13.5px; }
    .pf-alert { padding: 12px 14px; border-radius: 14px; font-size: 13.5px; font-weight: 600; }
    .pf-alert.ok { background: rgba(156, 255, 56, .12); border: 1px solid rgba(156, 255, 56, .35); color: #c9ff94; }
    .pf-alert.err { background: rgba(255, 90, 90, .1); border: 1px solid rgba(255, 90, 90, .35); color: #ffb3b3; }
    .pf-card { padding: 18px; border: 1px solid rgba(156, 255, 56, .14); border-radius: 20px; background: rgba(8, 26, 18, .82); backdrop-filter: blur(8px); }
    .pf-hero { display: flex; align-items: center; gap: 14px; }
    .pf-av { flex: 0 0 64px; height: 64px; display: grid; place-items: center; border-radius: 20px; background: linear-gradient(135deg, #b8ff3a, #5fc21b); color: #07120c; font-size: 24px; font-weight: 900; box-shadow: 0 10px 26px rgba(156, 255, 56, .25); }
    .pf-who { min-width: 0; }
    .pf-who strong { display: block; font-size: 19px; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pf-who span { display: block; color: rgba(244, 247, 244, .6); font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pf-tag { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; padding: 3px 9px; border-radius: 99px; background: rgba(156, 255, 56, .12); color: #9cff38; font-size: 11px; font-weight: 700; }
    .pf-tag.warn { background: rgba(255, 196, 0, .12); color: #ffd25a; }
    .pf-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 16px; }
    .pf-stats div { padding: 10px; border-radius: 14px; background: rgba(255, 255, 255, .04); text-align: center; }
    .pf-stats strong { display: block; font-size: 20px; font-weight: 900; color: #9cff38; }
    .pf-stats span { color: rgba(244, 247, 244, .55); font-size: 11.5px; }
    .pf-links { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 12px; }
    .pf-links a { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 6px; border-radius: 14px; border: 1px solid rgba(255, 255, 255, .07); color: rgba(244, 247, 244, .85); font-size: 12px; font-weight: 700; text-align: center; }
    .pf-links a:hover { border-color: rgba(156, 255, 56, .4); color: #9cff38; }
    .pf-links i { font-style: normal; font-size: 18px; }
    .pf-card h2 { font-size: 16px; font-weight: 800; }
    .pf-card h2 + p { margin: 3px 0 14px; color: rgba(244, 247, 244, .55); font-size: 12.5px; }
    .pf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pf-field { display: grid; gap: 6px; }
    .pf-field label { color: rgba(244, 247, 244, .75); font-size: 12.5px; font-weight: 700; }
    .pf-field input { width: 100%; height: 46px; padding: 0 14px; border: 1px solid rgba(255, 255, 255, .12); border-radius: 12px; background: rgba(255, 255, 255, .04); color: #f4f7f4; font-size: 16px; outline: none; }
    .pf-field input:focus { border-color: #9cff38; box-shadow: 0 0 0 3px rgba(156, 255, 56, .15); }
    .pf-field small { color: #ff9b9b; font-size: 12px; }
    .pf-actions { display: flex; justify-content: flex-end; margin-top: 14px; }
    .pf-btn { height: 46px; padding: 0 20px; border: 0; border-radius: 12px; background: #9cff38; color: #07120c; font-size: 14px; font-weight: 800; cursor: pointer; }
    .pf-btn:hover { background: #b4ff66; }
    .pf-out { width: 100%; height: 48px; border: 1px solid rgba(255, 90, 90, .4); border-radius: 14px; background: transparent; color: #ff8a8a; font-size: 14px; font-weight: 800; cursor: pointer; }
    .pf-out:hover { background: rgba(255, 90, 90, .08); }
    @media (max-width: 600px) {
        .pf-card { padding: 16px; border-radius: 18px; }
        .pf-grid { grid-template-columns: 1fr; }
        .pf-btn { width: 100%; }
        .pf { padding-bottom: 90px; }
    }
</style>

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'U';
@endphp

<div class="pf">
    <div>
        <h1>Profil <span>saya</span></h1>
        <p class="pf-sub">Kelola data akun dan password Anda.</p>
    </div>

    @if (session('success'))
        <div class="pf-alert ok">✓ {{ session('success') }}</div>
    @endif

    <section class="pf-card">
        <div class="pf-hero">
            <div class="pf-av">{{ $initials }}</div>
            <div class="pf-who">
                <strong>{{ $user->name }}</strong>
                <span>{{ $user->email }}</span>
                @if ($user->email_verified_at)
                    <span class="pf-tag">✓ Email terverifikasi</span>
                @else
                    <span class="pf-tag warn">! Email belum diverifikasi</span>
                @endif
            </div>
        </div>

        <div class="pf-stats">
            <div><strong>{{ $stats['total'] }}</strong><span>Total booking</span></div>
            <div><strong>{{ $stats['upcoming'] }}</strong><span>Akan datang</span></div>
            <div><strong>{{ $stats['done'] }}</strong><span>Selesai</span></div>
        </div>

        <div class="pf-links">
            @if (Route::has('booking'))<a href="{{ route('booking') }}"><i>📅</i>Booking</a>@endif
            @if (Route::has('chat'))<a href="{{ route('chat') }}"><i>💬</i>Chat admin</a>@endif
            @if (Route::has('notifications.index'))<a href="{{ route('notifications.index') }}"><i>🔔</i>Notifikasi</a>@endif
        </div>
    </section>

    <section class="pf-card">
        <h2>Data akun</h2>
        <p>Nama dan email yang dipakai untuk booking dan login.</p>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            <div class="pf-grid">
                <div class="pf-field">
                    <label for="pfName">Nama lengkap</label>
                    <input id="pfName" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                    @error('name')<small>{{ $message }}</small>@enderror
                </div>
                <div class="pf-field">
                    <label for="pfEmail">Email</label>
                    <input id="pfEmail" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                    @error('email')<small>{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="pf-actions"><button class="pf-btn" type="submit">Simpan profil</button></div>
        </form>
    </section>

    <section class="pf-card" id="password">
        <h2>Ganti password</h2>
        <p>Minimal 8 karakter. Perangkat lain akan otomatis keluar.</p>

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')
            <div class="pf-grid">
                <div class="pf-field" style="grid-column: 1 / -1;">
                    <label for="pfCur">Password saat ini</label>
                    <input id="pfCur" type="password" name="current_password" required autocomplete="current-password">
                    @error('current_password', 'password')<small>{{ $message }}</small>@enderror
                </div>
                <div class="pf-field">
                    <label for="pfNew">Password baru</label>
                    <input id="pfNew" type="password" name="password" required autocomplete="new-password">
                    @error('password', 'password')<small>{{ $message }}</small>@enderror
                </div>
                <div class="pf-field">
                    <label for="pfNew2">Ulangi password baru</label>
                    <input id="pfNew2" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>
            <div class="pf-actions"><button class="pf-btn" type="submit">Ganti password</button></div>
        </form>
    </section>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="pf-out" type="submit">Keluar dari akun</button>
    </form>
</div>
@endsection
