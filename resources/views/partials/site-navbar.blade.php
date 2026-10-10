{{--
    Navbar bersama untuk halaman visitor & customer.
    Pakai: @include('partials.site-navbar')
--}}
@php
    $snUser       = auth()->user();
    $snIsCustomer = $snUser && $snUser->role === 'customer';
    $snIsAdmin    = $snUser && $snUser->role === 'admin';

    $snHomeUrl    = $snIsCustomer ? route('dashboard') : route('home');
    $snBookingUrl = $snIsCustomer ? route('booking') : route('login');

    $snLinks = [
        ['label' => 'Home',    'url' => $snHomeUrl,        'active' => request()->routeIs('home', 'dashboard')],
        ['label' => 'Program', 'url' => route('program'),  'active' => request()->routeIs('program')],
        ['label' => 'Galeri',  'url' => route('galeri'),   'active' => request()->routeIs('galeri')],
        ['label' => 'Event',   'url' => route('event'),    'active' => request()->routeIs('event')],
        ['label' => 'Contact', 'url' => route('contact'),  'active' => request()->routeIs('contact')],
    ];

    if ($snIsCustomer && \Illuminate\Support\Facades\Route::has('chat')) {
        $snLinks[] = ['label' => 'Chat', 'url' => route('chat'), 'active' => request()->routeIs('chat')];
    }

    $snBookingActive = request()->routeIs('booking', 'payment', 'payment.*');
    $snNow           = now();
@endphp

@once
    <style>
        .sn-nav {
            position: sticky;
            top: 0;
            z-index: 200;
            flex: 0 0 auto;
            width: 100%;
            min-height: 68px;
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 0 32px;
            background: rgba(3, 13, 9, .96);
            border-bottom: 1px solid rgba(156, 255, 0, .14);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            font-family: Arial, Helvetica, sans-serif;
            color: #ffffff;
        }

        .sn-nav *, .sn-nav *::before, .sn-nav *::after { box-sizing: border-box; }
        .sn-nav a { text-decoration: none; }

        /* Logo */
        .sn-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
            font-size: 21px;
            font-weight: 900;
            letter-spacing: -.6px;
            white-space: nowrap;
        }

        .sn-brand-icon { font-size: 28px; line-height: 1; }
        .sn-brand-text span { color: #9cff38; }

        /* Menu */
        .sn-links {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .sn-link {
            position: relative;
            padding: 9px 12px;
            border-radius: 8px;
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            font-weight: 700;
            transition: color .2s ease, background .2s ease;
        }

        .sn-link:hover { color: #9cff38; background: rgba(156, 255, 0, .06); }
        .sn-link.active { color: #9cff38; }

        .sn-link.active::after {
            content: "";
            position: absolute;
            left: 12px;
            right: 12px;
            bottom: 2px;
            height: 2px;
            border-radius: 2px;
            background: #9cff38;
        }

        .sn-booking {
            margin-left: 8px;
            padding: 9px 18px;
            border: 1px solid #9cff38;
            border-radius: 8px;
            color: #9cff38;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .6px;
            transition: background .2s ease, color .2s ease;
        }

        .sn-booking:hover,
        .sn-booking.active { background: #9cff38; color: #07120c; }

        /* Kanan */
        .sn-right { display: flex; align-items: center; gap: 12px; }

        .sn-clock {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            padding: 6px 12px;
            border: 1px solid rgba(156, 255, 0, .14);
            border-radius: 9px;
            background: rgba(255, 255, 255, .03);
            line-height: 1.25;
            white-space: nowrap;
        }

        .sn-clock-time { color: #9cff38; font-size: 14px; font-weight: 900; font-variant-numeric: tabular-nums; }
        .sn-clock-date { color: rgba(255, 255, 255, .6); font-size: 10px; font-weight: 700; }

        .sn-user {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            max-width: 160px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 9px;
            background: rgba(255, 255, 255, .04);
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sn-user-dot { width: 9px; height: 9px; flex: 0 0 9px; border-radius: 50%; background: #9cff38; }

        .sn-btn {
            height: 36px;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(156, 255, 0, .55);
            border-radius: 9px;
            background: transparent;
            color: #ffffff;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: background .2s ease, color .2s ease;
        }

        .sn-btn:hover { background: #9cff38; color: #07120c; }
        .sn-btn.lime { color: #9cff38; }
        .sn-btn.lime:hover { color: #07120c; }
        .sn-logout-form { margin: 0; }

        .sn-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 9px;
            background: transparent;
            color: #ffffff;
            font-size: 20px;
            cursor: pointer;
        }

        /* Responsif */
        @media (max-width: 1360px) {
            .sn-clock-date { display: none; }
        }

        @media (max-width: 1180px) {
            .sn-nav { padding: 0 20px; gap: 12px; }
            .sn-link { padding: 9px 9px; }
            .sn-user { max-width: 120px; }
        }

        @media (max-width: 1024px) {
            .sn-nav { flex-wrap: wrap; padding: 10px 16px; }
            .sn-toggle { display: inline-flex; align-items: center; justify-content: center; order: 3; }
            .sn-right { margin-left: auto; order: 2; }

            .sn-links {
                order: 4;
                flex-basis: 100%;
                display: none;
                flex-direction: column;
                align-items: stretch;
                gap: 2px;
                padding: 8px 0 6px;
                border-top: 1px solid rgba(255, 255, 255, .08);
            }

            .sn-nav.open .sn-links { display: flex; }
            .sn-link.active::after { display: none; }
            .sn-link.active { background: rgba(156, 255, 0, .08); }
            .sn-booking { margin: 6px 0 0; text-align: center; }
        }

        @media (max-width: 640px) {
            .sn-brand { font-size: 17px; }
            .sn-brand-icon { font-size: 22px; }
            .sn-user, .sn-clock { display: none; }
            .sn-btn { padding: 0 12px; }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /* Jam & tanggal real-time */
            const pad = (n) => String(n).padStart(2, '0');
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

            function tick() {
                const now = new Date();
                document.querySelectorAll('[data-sn-time]').forEach(function (el) {
                    el.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds()) + ' WIB';
                });
                document.querySelectorAll('[data-sn-date]').forEach(function (el) {
                    el.textContent = days[now.getDay()] + ', ' + pad(now.getDate()) + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
                });
            }

            tick();
            setInterval(tick, 1000);

            /* Menu HP */
            document.querySelectorAll('.sn-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    const nav = button.closest('.sn-nav');
                    const open = nav.classList.toggle('open');
                    button.setAttribute('aria-expanded', String(open));
                    button.textContent = open ? '✕' : '☰';
                });
            });
        });
    </script>
@endonce

<nav class="sn-nav">

    <a href="{{ $snHomeUrl }}" class="sn-brand" aria-label="{{ \App\Support\Brand::name() }}">
        @include('partials.brand-logo', ['iconClass' => 'sn-brand-icon', 'textClass' => 'sn-brand-text'])
    </a>

    <div class="sn-links">
        @foreach ($snLinks as $link)
            <a href="{{ $link['url'] }}" class="sn-link {{ $link['active'] ? 'active' : '' }}">{{ $link['label'] }}</a>
        @endforeach

        <a href="{{ $snBookingUrl }}" class="sn-booking {{ $snBookingActive ? 'active' : '' }}">BOOKING</a>
    </div>

    <div class="sn-right">
        <div class="sn-clock" title="Waktu sekarang">
            <span class="sn-clock-time" data-sn-time>{{ $snNow->format('H:i:s') }} WIB</span>
            <span class="sn-clock-date" data-sn-date>{{ $snNow->locale('id')->translatedFormat('l, d M Y') }}</span>
        </div>

        @auth
            @includeIf('partials.notification-bell')

            @if ($snIsAdmin)
                <a href="{{ route('admin.dashboard') }}" class="sn-btn lime">ADMIN</a>
            @else
                <span class="sn-user"><span class="sn-user-dot"></span>Hi, {{ $snUser->name }}</span>

                <form method="POST" action="{{ route('logout') }}" class="sn-logout-form">
                    @csrf
                    <button type="submit" class="sn-btn">Logout</button>
                </form>
            @endif
        @else
            <a href="{{ route('login') }}" class="sn-btn lime">LOGIN</a>
        @endauth
    </div>

    <button type="button" class="sn-toggle" aria-label="Buka menu" aria-expanded="false">☰</button>

</nav>
