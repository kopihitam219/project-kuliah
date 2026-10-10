@extends('layouts.fw-auth')

@section('title', 'Verifikasi Email')

@section('content')
    <h1>Cek email Anda</h1>
    <p class="fw-sub">Kami sudah mengirim link verifikasi ke <b>{{ auth()->user()->email ?? 'email Anda' }}</b>. Klik link tersebut untuk mengaktifkan akun.</p>

    @if (session('status') === 'verification-link-sent')
        <div class="fw-alert ok">Link verifikasi baru sudah dikirim ke email Anda.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="fw-btn block">Kirim ulang link verifikasi</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:12px">
        @csrf
        <button type="submit" class="fw-btn block ghost">Keluar</button>
    </form>
@endsection
