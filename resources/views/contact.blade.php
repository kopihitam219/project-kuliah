@php
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';

    $homeUrl    = $isCustomer ? route('dashboard') : route('home');
    $bookingUrl = $isCustomer ? route('booking') : route('login');

    // Data kontak dikelola admin di menu Contact
    $setting   = \App\Models\ContactSetting::current();
    $locations = \App\Models\ContactLocation::active()->ordered()->get();
    $first     = $locations->first();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - {{ \App\Support\Brand::name() }}</title>

    <style>
        :root {
            --lime:       #b8f34a;
            --lime-hover: #c7ff63;
            --ink-dark:   #07130f;
            --panel:      rgba(3, 15, 11, .74);
            --line:       rgba(255, 255, 255, .10);
            --text-muted: rgba(255, 255, 255, .56);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { width: 100%; height: 100%; }

        body {
            background: var(--ink-dark);
            color: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
        }

        a { color: inherit; text-decoration: none; }
        button, input, textarea { font-family: inherit; }

        /* ================= PAGE (1 layar) ================= */
        .contact-page {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background:
                linear-gradient(90deg, rgba(3, 12, 9, .95) 0%, rgba(3, 12, 9, .82) 55%, rgba(3, 12, 9, .68) 100%),
                url('{{ \App\Support\Brand::background('public') }}') center / cover no-repeat;
        }

        /* ================= NAVBAR ================= */
        .navbar {
            flex: 0 0 68px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            padding: 0 42px;
            background: rgba(4, 15, 9, .94);
            border-bottom: 1px solid rgba(184, 255, 0, .15);
        }

        .brand { justify-self: start; font-size: 21px; font-weight: 800; letter-spacing: -.5px; white-space: nowrap; }
        .brand span { color: #b8ff00; }

        .nav-menu { display: flex; align-items: center; gap: 24px; }

        .nav-menu > a {
            position: relative;
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: color .2s ease;
        }

        .nav-menu > a:hover,
        .nav-menu > a.active { color: #b8ff00; }

        .nav-menu > a.active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -9px;
            height: 2px;
            border-radius: 10px;
            background: #b8ff00;
        }

        .booking-btn {
            padding: 10px 18px;
            border: 1px solid #b8ff00;
            border-radius: 5px;
            color: #b8ff00 !important;
            font-weight: 800 !important;
        }

        .booking-btn:hover { background: #b8ff00; color: var(--ink-dark) !important; }

        .account-menu { justify-self: end; display: flex; align-items: center; gap: 20px; }
        .user-name { color: #b8ff00; font-size: 13px; font-weight: 600; white-space: nowrap; }
        .logout-form { margin: 0; }

        .logout-btn {
            border: 0;
            background: transparent;
            color: rgba(255, 255, 255, .72);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .logout-btn:hover { color: #ff7777; }

        /* ================= MAIN ================= */
        .contact-main {
            flex: 1;
            min-height: 0;
            width: min(1240px, 90%);
            margin: 0 auto;
            padding: 28px 0;
            display: grid;
            grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr);
            gap: 32px;
        }

        /* ---------- Kolom kiri ---------- */
        .intro {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 0;
        }

        .contact-label {
            margin-bottom: 8px;
            color: var(--lime);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .contact-title {
            font-size: clamp(38px, 4.4vw, 56px);
            line-height: .95;
            font-weight: 900;
            letter-spacing: -1.5px;
            text-transform: uppercase;
        }

        .contact-title span { color: var(--lime); }

        .contact-description {
            margin-top: 12px;
            color: rgba(255, 255, 255, .72);
            font-size: 13px;
            line-height: 1.6;
        }

        .section-label {
            margin: 22px 0 10px;
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* Pilihan lokasi */
        .location-tabs { display: grid; gap: 8px; }

        .location-tab {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: rgba(0, 0, 0, .22);
            color: inherit;
            text-align: left;
            cursor: pointer;
            transition: border-color .2s ease, background .2s ease;
        }

        .location-tab:hover { border-color: rgba(184, 243, 74, .35); }
        .location-tab.active { border-color: var(--lime); background: rgba(184, 243, 74, .08); }
        .location-tab:focus-visible { outline: 2px solid var(--lime); outline-offset: 2px; }

        .location-number {
            width: 28px;
            height: 28px;
            min-width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 1px solid rgba(184, 243, 74, .45);
            color: var(--lime);
            font-size: 12px;
            font-weight: 900;
        }

        .location-tab.active .location-number { background: var(--lime); color: var(--ink-dark); }
        .location-tab strong { display: block; font-size: 14px; }
        .location-tab small { display: block; margin-top: 2px; color: var(--text-muted); font-size: 11px; }

        /* Info kontak */
        .info-list { display: grid; gap: 9px; }

        .info-item { display: flex; align-items: center; gap: 11px; font-size: 13px; color: rgba(255, 255, 255, .88); }

        .info-icon {
            width: 30px;
            height: 30px;
            min-width: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: rgba(184, 243, 74, .10);
            border: 1px solid rgba(184, 243, 74, .22);
            color: var(--lime);
            font-size: 13px;
        }

        a.info-item:hover { color: var(--lime); }

        /* ---------- Kolom kanan ---------- */
        .side { display: flex; flex-direction: column; gap: 14px; min-height: 0; }

        .card {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--panel);
            box-shadow: 0 18px 45px rgba(0, 0, 0, .30);
        }

        .map-card { position: relative; flex: 1; min-height: 180px; overflow: hidden; }
        .side > .form-card:only-child { margin-top: auto; margin-bottom: auto; }

        .map-card iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            filter: grayscale(.35) contrast(1.05);
        }

        .map-route {
            position: absolute;
            right: 12px;
            bottom: 12px;
            padding: 9px 13px;
            border-radius: 7px;
            background: var(--lime);
            color: var(--ink-dark);
            font-size: 11px;
            font-weight: 800;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .35);
        }

        .map-route:hover { background: var(--lime-hover); }

        /* Form */
        .form-card { padding: 18px 20px; }

        .form-title { margin-bottom: 12px; font-size: 13px; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; }
        .form-title span { color: var(--lime); }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }

        .form-control {
            width: 100%;
            height: 40px;
            padding: 0 12px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 6px;
            outline: none;
            background: rgba(0, 0, 0, .25);
            color: #ffffff;
            font-size: 12px;
            transition: border-color .2s ease;
        }

        textarea.form-control { height: 64px; padding: 10px 12px; resize: none; line-height: 1.5; margin-bottom: 10px; }
        .form-control::placeholder { color: rgba(255, 255, 255, .35); }
        .form-control:focus { border-color: rgba(184, 243, 74, .65); }

        .send-button {
            width: 100%;
            height: 42px;
            border: 0;
            border-radius: 6px;
            background: var(--lime);
            color: var(--ink-dark);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1.2px;
            cursor: pointer;
            transition: background .2s ease;
        }

        .send-button:hover { background: var(--lime-hover); }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 1100px) {
            .navbar { grid-template-columns: auto 1fr auto; padding: 0 28px; }
            .nav-menu { justify-content: center; gap: 15px; }
            .user-name { display: none; }
        }

        /* Layar pendek atau sempit: halaman boleh di-scroll */
        @media (max-width: 900px), (max-height: 620px) {
            .contact-page { height: auto; min-height: 100vh; }
            .contact-main { grid-template-columns: 1fr; gap: 22px; }
            .map-card { flex: none; height: 260px; }
        }

        @media (max-width: 760px) {
            .navbar {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                padding: 14px 5%;
            }

            .nav-menu { order: 3; width: 100%; flex-wrap: wrap; gap: 10px 14px; }
            .nav-menu > a { font-size: 11px; }
            .booking-btn { padding: 7px 11px; }
            .account-menu { margin-left: auto; }
            .contact-main { width: 92%; padding: 24px 0 32px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
    @include('partials.brand-head')
</head>

<body>

<div class="contact-page">

    {{-- ================= NAVBAR ================= --}}
    @include('partials.site-navbar')

    {{-- ================= MAIN ================= --}}
    <main class="contact-main">

        {{-- Kolom kiri: judul, lokasi, kontak --}}
        <section class="intro">
            <div class="contact-label">Golf Booking Lesson</div>
            <h1 class="contact-title">Contact <span>Us</span></h1>
            @if ($setting->description)
                <p class="contact-description">{{ $setting->description }}</p>
            @endif

            @if ($locations->isNotEmpty())
                <div class="section-label">Lokasi latihan</div>
                <div class="location-tabs">
                    @foreach ($locations as $index => $location)
                        <button type="button"
                                class="location-tab {{ $index === 0 ? 'active' : '' }}"
                                data-name="{{ $location->name }}"
                                data-embed="{{ $location->map_embed_url }}"
                                data-link="{{ $location->map_link }}"
                                aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                            <span class="location-number">{{ $index + 1 }}</span>
                            <span>
                                <strong>{{ $location->name }}</strong>
                                @if ($location->area)
                                    <small>{{ $location->area }}</small>
                                @endif
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="section-label">Kontak</div>
            <div class="info-list">
                @if ($setting->whatsapp_url)
                    <a href="{{ $setting->whatsapp_url }}" target="_blank" rel="noopener" class="info-item">
                        <span class="info-icon">☎</span> {{ $setting->whatsapp }} (WhatsApp)
                    </a>
                @endif
                @if ($setting->email)
                    <a href="mailto:{{ $setting->email }}" class="info-item">
                        <span class="info-icon">✉</span> {{ $setting->email }}
                    </a>
                @endif
                @if ($setting->opening_hours)
                    <div class="info-item">
                        <span class="info-icon">◷</span> {{ $setting->opening_hours }}
                    </div>
                @endif
            </div>
        </section>

        {{-- Kolom kanan: peta + form --}}
        <section class="side">
            @if ($first)
                <div class="card map-card">
                    <iframe id="mapFrame"
                            src="{{ $first->map_embed_url }}"
                            title="Peta {{ $first->name }}"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen></iframe>

                    <a href="{{ $first->map_link }}" id="mapRoute"
                       target="_blank" rel="noopener" class="map-route">Buka rute →</a>
                </div>
            @endif

            <form class="card form-card" id="contactForm">
                <h2 class="form-title">Kirim <span>Pesan</span></h2>

                <div class="form-row">
                    <label>
                        <span class="sr-only">Nama</span>
                        <input type="text" id="contactName" class="form-control" placeholder="Nama"
                               autocomplete="name" required value="{{ auth()->user()->name ?? '' }}">
                    </label>
                    <label>
                        <span class="sr-only">Email</span>
                        <input type="email" id="contactEmail" class="form-control" placeholder="Email"
                               autocomplete="email" required value="{{ auth()->user()->email ?? '' }}">
                    </label>
                </div>

                <label>
                    <span class="sr-only">Pesan</span>
                    <textarea id="contactMessage" class="form-control" placeholder="Tulis pertanyaan Anda..." required></textarea>
                </label>

                <button type="submit" class="send-button">KIRIM VIA WHATSAPP →</button>
            </form>
        </section>

    </main>

</div>

<script>
    (() => {
        const phoneNumber = @json($setting->whatsapp_number);

        const tabs     = document.querySelectorAll('.location-tab');
        const mapFrame = document.getElementById('mapFrame');
        const mapRoute = document.getElementById('mapRoute');

        let activeLocation = tabs[0]?.dataset.name ?? '';

        // Pilih lokasi: ganti peta & link rute
        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                tabs.forEach((item) => {
                    item.classList.toggle('active', item === tab);
                    item.setAttribute('aria-pressed', String(item === tab));
                });

                if (mapFrame) {
                    mapFrame.src   = tab.dataset.embed;
                    mapFrame.title = 'Peta ' + tab.dataset.name;
                    mapRoute.href  = tab.dataset.link;
                }
                activeLocation = tab.dataset.name;
            });
        });

        // Kirim pesan ke WhatsApp (lokasi ikut dari pilihan aktif)
        document.getElementById('contactForm').addEventListener('submit', (event) => {
            event.preventDefault();

            const value = (id) => document.getElementById(id).value.trim();

            const text = [
                'Halo Golf Booking Lesson,',
                '',
                'Nama   : ' + value('contactName'),
                'Email  : ' + value('contactEmail'),
                'Lokasi : ' + (activeLocation || '-'),
                '',
                value('contactMessage'),
            ].join('\n');

            if (!phoneNumber) {
                alert('Nomor WhatsApp belum diatur.');
                return;
            }

            window.open(
                'https://wa.me/' + phoneNumber + '?text=' + encodeURIComponent(text),
                '_blank',
                'noopener,noreferrer'
            );
        });
    })();
</script>

</body>
</html>
