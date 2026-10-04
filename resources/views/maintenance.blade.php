@php
    use App\Support\Brand;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedang perbaikan | {{ Brand::name() }}</title>
    @include('partials.brand-head')

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
            background:
                linear-gradient(rgba(2, 13, 9, .88), rgba(2, 13, 9, .94)),
                url('{{ Brand::background('public') }}') center / cover no-repeat;
        }

        .card {
            width: 100%;
            max-width: 520px;
            padding: 36px 30px;
            text-align: center;
            border: 1px solid rgba(156, 255, 0, .25);
            border-radius: 16px;
            background: rgba(3, 15, 11, .78);
        }

        .brand { display: inline-flex; align-items: center; gap: 10px; font-size: 22px; font-weight: 900; }
        .brand-icon { font-size: 30px; }
        .brand .accent { color: #9cff38; }

        .icon {
            width: 64px;
            height: 64px;
            margin: 28px auto 18px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(245, 196, 0, .14);
            color: #ffc62d;
            font-size: 28px;
        }

        h1 { font-size: 26px; font-weight: 900; }
        p { margin-top: 10px; color: rgba(255, 255, 255, .75); font-size: 14px; line-height: 1.6; }
        small { display: block; margin-top: 22px; color: rgba(255, 255, 255, .5); font-size: 12px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">
            @include('partials.brand-logo', ['iconClass' => 'brand-icon', 'accentClass' => 'accent'])
        </div>

        <div class="icon" aria-hidden="true">⚙</div>

        <h1>Sedang dalam perbaikan</h1>
        <p>{{ Brand::maintenanceMessage() }}</p>

        <small>{{ Brand::tagline() }}</small>
    </main>
</body>
</html>
