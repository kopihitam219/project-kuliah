{{-- Layout sederhana untuk halaman notifikasi customer --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Notifikasi') | {{ \App\Support\Brand::name() }}</title>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100%; }

        body {
            color: #f4f7f4;
            background:
                linear-gradient(rgba(1, 12, 9, .82), rgba(1, 12, 9, .93)),
                url('{{ \App\Support\Brand::background('public') }}') center / cover fixed no-repeat;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        a { color: inherit; text-decoration: none; }
        button { font: inherit; }

        .navbar {
            min-height: 64px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            background: rgba(2, 15, 11, .92);
            border-bottom: 1px solid rgba(184, 255, 0, .10);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand { font-size: 20px; font-weight: 800; letter-spacing: -.6px; white-space: nowrap; }
        .brand span { color: #b8ff00; }
        .nav-right { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .nav-link { padding: 8px 11px; border-radius: 8px; color: #cbd2cf; font-size: 13px; font-weight: 600; }
        .nav-link:hover { color: #b8ff00; }
        .booking-nav { padding: 9px 18px; border-radius: 9px; background: #b8ff00; color: #071000; font-size: 12px; font-weight: 800; }
        .user-name { padding: 8px 12px; color: #cbd2cf; background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .07); border-radius: 9px; font-size: 12px; font-weight: 600; }
        .logout-button { padding: 8px 10px; border: 0; background: transparent; color: #aab3af; font-size: 12px; font-weight: 600; cursor: pointer; }

        .page { width: min(900px, calc(100% - 40px)); margin: auto; padding: 32px 0 44px; }

        @media (max-width: 900px) { .nav-link { display: none; } }
        @media (max-width: 520px) { .page { width: calc(100% - 20px); } .logout-button { display: none; } }
    </style>
    @include('partials.brand-head')
</head>
<body>

@include('partials.site-navbar')

<main class="page">
    @yield('content')
</main>

@stack('scripts')

</body>
</html>
