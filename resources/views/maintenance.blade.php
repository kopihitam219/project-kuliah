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

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
            color: var(--d-text, #17261d); font-family: Inter, Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, rgba(var(--d-bg-rgb, 243, 241, 234), .55), var(--d-bg, #f3f1ea) 70%), url('{{ Brand::background('public') }}') center top / cover no-repeat;
        }
        .card { width: 100%; max-width: 480px; padding: 34px 28px; text-align: center; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .09); border-radius: 24px; background: var(--d-surface, #ffffff); box-shadow: 0 24px 60px rgba(var(--d-shadow-rgb, 23, 46, 33), .12); }
        .brand { display: inline-flex; align-items: center; gap: 10px; font-size: 20px; font-weight: 700; }
        .brand-icon { font-size: 28px; }
        .brand .accent { color: var(--d-ink-green-2, #3d7d57); }
        .icon { width: 64px; height: 64px; margin: 26px auto 16px; display: grid; place-items: center; border-radius: 50%; background: var(--d-orange-tint, #fdf1de); color: #e9a23b; font-size: 28px; }
        h1 { font-family: 'Playfair Display', Georgia, serif; font-size: 28px; font-weight: 700; }
        p { margin-top: 10px; color: var(--d-text-2, #3c4a42); font-size: 14.5px; line-height: 1.65; }
        small { display: block; margin-top: 22px; color: var(--d-muted, #77837b); font-size: 12px; }
    </style>
    <link rel="stylesheet" href="{{ asset('css/fw.css') }}?v={{ substr(md5((string) @filemtime(public_path('css/fw.css'))), 0, 8) }}">
    @include('partials.theme-script')
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
