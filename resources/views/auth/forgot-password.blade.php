@extends('layouts.fw-auth')

@section('title', 'Lupa Password')

@section('content')
    <h1>Lupa password?</h1>
    <p class="fw-sub">Masukkan email akun Anda, kami kirimkan link untuk membuat password baru.</p>

    @if (session('status'))<div class="fw-alert ok">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="fw-alert err"><div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="fw-field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required autofocus>
        </div>
        <button type="submit" class="fw-btn block">Kirim link reset</button>
    </form>

    <p class="fa-alt">Ingat password? <a href="{{ route('login') }}" class="fw-link">Kembali masuk</a></p>
@endsection
