{{-- Layout halaman masuk/daftar tema Fairway: foto lapangan + form. --}}
@php
    $faPhoto = \App\Support\Brand::background('auth');
@endphp
<!DOCTYPE html>
<html lang="id" class="fw-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ \App\Support\Brand::name() }}</title>
    @include('partials.fw-head')
    <style>
        .fa { min-height: 100svh; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .fa-art { position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; padding: 36px 44px; color: #fff; background: var(--fw-green); }
        .fa-art::before { content: ""; position: absolute; inset: 0; background: var(--fa-photo) center / cover no-repeat; filter: brightness(1.15) saturate(1.05); }
        .fa-art::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(23, 56, 37, .35) 0%, rgba(23, 56, 37, .1) 40%, rgba(23, 56, 37, .88) 100%); }
        .fa-art > * { position: relative; z-index: 1; }
        .fa-art .fw-brand { color: #fff; }
        .fa-art .fw-acc { color: var(--fw-lime); }
        .fa-quote h2 { font-family: var(--fw-serif); font-size: clamp(30px, 3.4vw, 44px); font-weight: 600; line-height: 1.12; }
        .fa-quote p { margin-top: 10px; max-width: 40ch; color: rgba(255, 255, 255, .82); font-size: 15px; line-height: 1.6; }
        .fa-form { display: flex; align-items: center; justify-content: center; padding: 40px 24px; }
        .fa-card { width: min(420px, 100%); }
        .fa-card h1 { font-family: var(--fw-serif); font-size: 34px; font-weight: 600; letter-spacing: -.4px; }
        .fa-card .fw-sub { margin-bottom: 24px; }
        .fa-card form { display: grid; gap: 16px; }
        .fa-pw { position: relative; }
        .fa-pw input { padding-right: 52px !important; }
        .fa-pw button { position: absolute; right: 6px; top: 50%; width: 40px; height: 40px; transform: translateY(-50%); display: grid; place-items: center; border: 0; border-radius: 50%; background: transparent; color: var(--fw-muted); cursor: pointer; }
        .fa-pw button svg { width: 19px; height: 19px; }
        .fa-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 13.5px; }
        .fa-row label { display: inline-flex; align-items: center; gap: 8px; color: var(--fw-text-2); cursor: pointer; }
        .fa-row input[type=checkbox] { width: 17px; height: 17px; accent-color: var(--fw-green); }
        .fa-alt { margin-top: 22px; text-align: center; color: var(--fw-muted); font-size: 14px; }
        .fa-home { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 26px; color: var(--fw-muted); font-size: 13.5px; }
        .fa-home svg { width: 16px; height: 16px; }
        @media (max-width: 900px) {
            .fa { grid-template-columns: minmax(0, 1fr); grid-template-rows: auto 1fr; }
            .fa-art { min-height: 220px; padding: 20px 20px 22px; border-radius: 0 0 28px 28px; }
            .fa-quote h2 { font-size: 26px; }
            .fa-quote p { display: none; }
            .fa-form { align-items: flex-start; padding: 26px 20px 40px; }
            .fa-home { display: none; }
            .fa-card h1 { font-size: 28px; }
        }
    </style>
</head>
<body class="fw">
    <div class="fa">
        <aside class="fa-art" style="--fa-photo:url('{{ $faPhoto }}')">
            <a href="{{ route('home') }}" class="fw-brand">
                @include('partials.brand-logo', ['iconClass' => 'fw-brand-coin', 'textClass' => 'fw-brand-text', 'accentClass' => 'fw-acc'])
            </a>
            <div class="fa-quote">
                <h2>Latihan hari ini,<br>prestasi esok hari.</h2>
                <p>{{ \App\Support\Brand::tagline() }}</p>
            </div>
        </aside>
        <main class="fa-form">
            <div class="fa-card">
                <a href="{{ route('home') }}" class="fa-home">{!! \App\Support\Icons::svg('back') !!} Kembali ke beranda</a>
                @yield('content')
            </div>
        </main>
    </div>
    <script>
        document.querySelectorAll('[data-toggle-pw]').forEach(function (b) {
            b.addEventListener('click', function () {
                var i = b.parentNode.querySelector('input');
                i.type = i.type === 'password' ? 'text' : 'password';
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
