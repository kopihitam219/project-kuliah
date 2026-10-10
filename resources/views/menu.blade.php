{{-- Menu akun (HP & laptop). Visitor melihat menu halaman + Masuk/Daftar. --}}
@extends('layouts.fw')

@section('title', 'Menu')
@section('no_footer', true)
@section('main_class', 'narrow')

@php
    use App\Support\Icons;

    $unread  = $user ? $user->unreadNotifications()->count() : 0;
    $chatNew = $user && class_exists(\App\Models\ChatMessage::class) ? \App\Models\ChatMessage::unreadForMember($user->id) : 0;
    $item = function ($url, $icon, $label, $count = 0, $class = '') {
        return '<a href="' . e($url) . '" class="' . $class . '"><span class="ic">' . Icons::svg($icon) . '</span><span class="lbl">' . e($label) . '</span>'
            . ($count > 0 ? '<span class="count">' . ($count > 99 ? '99+' : $count) . '</span>' : '')
            . Icons::svg('chev', 'fw-chev') . '</a>';
    };
@endphp

@push('head')
<style>
    .mn-profile { display: flex; align-items: center; gap: 14px; padding: 18px; margin-bottom: 18px; }
    .mn-profile strong { display: block; font-size: 17px; font-weight: 600; }
    .mn-profile small { color: var(--fw-muted); font-size: 13px; }
    .mn-group { margin-bottom: 18px; }
    .mn-group h2 { margin: 0 4px 8px; color: var(--fw-muted); font-size: 12px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
    .mn-deco { height: 90px; margin-top: 8px; border-radius: 0 0 24px 24px; background: radial-gradient(120% 90% at 50% 120%, rgba(61, 125, 87, .25), transparent 60%); }
    .mn-foot { margin-top: 10px; text-align: center; color: var(--fw-muted); font-size: 12px; }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <h1 class="fw-h1">Menu</h1>
        </div>
        @if ($user)<a href="{{ route('profile.edit') }}" class="fw-nav-icon" aria-label="Pengaturan akun">{!! Icons::svg('settings') !!}</a>@endif
    </div>

    @if ($user)
        <a href="{{ route('profile.edit') }}" class="fw-card mn-profile">
            <span class="fw-av lg">{{ Icons::initials($user->name) }}</span>
            <span style="flex:1;min-width:0">
                <strong>{{ $user->name }}</strong>
                <span class="fw-badge green" style="margin-top:4px">{{ $user->role === 'admin' ? 'Admin / Coach' : 'Member' }}</span>
            </span>
            {!! Icons::svg('chev', 'fw-chev') !!}
        </a>

        <div class="mn-group">
            <h2>Akun</h2>
            <div class="fw-menu">
                {!! $item(route('profile.edit'), 'user', 'Profil Saya') !!}
                @if ($user->role === 'customer')
                    {!! $item(route('jadwal', ['tab' => 'selesai']), 'history', 'Riwayat Booking') !!}
                    {!! $item(route('jadwal'), 'cal-check', 'Jadwal Saya') !!}
                @endif
                {!! $item(route('notifications.index'), 'bell', 'Notifikasi', $unread) !!}
                @if ($user->role === 'customer' && Route::has('chat'))
                    {!! $item(route('chat'), 'chat', 'Chat Coach', $chatNew) !!}
                @endif
                {!! $item(route('profile.edit') . '#password', 'lock', 'Ganti Password') !!}
            </div>
        </div>
    @else
        <div class="fw-card mn-profile">
            <span class="fw-av lg">{!! Icons::svg('user') !!}</span>
            <span style="flex:1">
                <strong>Selamat datang</strong>
                <small>Masuk untuk booking lesson & melihat jadwal Anda.</small>
            </span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px">
            <a href="{{ route('login') }}" class="fw-btn">Masuk</a>
            <a href="{{ route('register') }}" class="fw-btn ghost">Daftar</a>
        </div>
    @endif

    <div class="mn-group">
        <h2>Jelajahi</h2>
        <div class="fw-menu">
            {!! $item(route('home') . '#coach', 'coach', 'Tentang Coach') !!}
            {!! $item(route('program'), 'star', 'Program Latihan') !!}
            {!! $item(route('galeri'), 'image', 'Galeri') !!}
            {!! $item(route('event'), 'flag', 'Event') !!}
        </div>
    </div>

    <div class="mn-group">
        <h2>Bantuan</h2>
        <div class="fw-menu">
            <button type="button" data-theme-toggle aria-label="Ganti mode terang/gelap"><span class="ic">{!! Icons::svg('moon') !!}</span><span class="lbl">Mode gelap</span><span class="fw-theme-switch" aria-hidden="true"></span></button>
            {!! $item(route('contact'), 'help', 'Bantuan & Kontak') !!}
            {!! $item(route('home'), 'info', 'Tentang ' . \App\Support\Brand::name()) !!}
            @if ($user)
                <form method="POST" action="{{ route('logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="danger"><span class="ic">{!! Icons::svg('logout') !!}</span><span class="lbl">Keluar</span></button>
                </form>
            @endif
        </div>
    </div>

    <div class="mn-deco" aria-hidden="true"></div>
    <p class="mn-foot">{{ \App\Support\Brand::name() }} · {{ \App\Support\Brand::tagline() }}</p>
@endsection
