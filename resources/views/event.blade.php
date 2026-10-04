@php
    // Event aktif yang belum lewat, urut dari tanggal terdekat
    $events = \App\Models\Event::active()->upcoming()->chronological()->get();

    $user       = auth()->user();
    $isCustomer = $user && $user->role === 'customer';
    $isAdmin    = $user && $user->role === 'admin';

    $homeUrl    = $isCustomer ? route('dashboard') : route('home');
    $bookingUrl = $isCustomer ? route('booking') : route('login');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Event | {{ \App\Support\Brand::name() }}</title>

    <style>
        /* ================= RESET ================= */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
            color: #f3f6f4;
            background:
                radial-gradient(circle at 20% 10%, rgba(77, 120, 37, .12), transparent 35%),
                radial-gradient(circle at 90% 70%, rgba(60, 100, 40, .08), transparent 35%),
                #060a08;
        }

        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; }

        .page {
            min-height: 100vh;
            background: linear-gradient(180deg, rgba(4, 8, 6, .08), rgba(4, 8, 6, .72));
        }

        /* ================= NAVBAR ================= */
        .navbar {
            width: 100%;
            min-height: 68px;
            padding: 0 4%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
            background: rgba(5, 9, 7, .94);
            backdrop-filter: blur(12px);
            position: relative;
            z-index: 20;
        }

        .brand { font-size: 19px; font-weight: 800; letter-spacing: -.5px; white-space: nowrap; }
        .brand span { color: #9dff00; }

        .nav-menu { display: flex; align-items: center; gap: 21px; color: #aeb8b2; font-size: 12px; }
        .nav-menu a { transition: color .2s ease; }
        .nav-menu a:hover { color: #ffffff; }
        .nav-menu .active { color: #9dff00; }

        .booking-btn {
            padding: 8px 15px;
            border: 1px solid #9dff00;
            border-radius: 5px;
            color: #9dff00;
            font-weight: 800;
            letter-spacing: .5px;
        }

        .booking-btn:hover { background: #9dff00; color: #071006 !important; }

        .user-area { display: flex; align-items: center; gap: 12px; white-space: nowrap; }
        .user-name { color: #d5ddd8; font-size: 12px; }
        .logout-form { margin: 0; }

        .logout-btn {
            border: 0;
            padding: 4px;
            background: transparent;
            color: #8d9992;
            cursor: pointer;
            font-size: 12px;
        }

        .logout-btn:hover { color: #ffffff; }

        /* ================= MAIN ================= */
        .event-main { width: min(1180px, 92%); margin: 0 auto; padding: 28px 0 42px; }

        .page-heading { text-align: center; margin-bottom: 24px; }

        .eyebrow {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            color: #9dff00;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2.5px;
            margin-bottom: 7px;
        }

        .eyebrow::before,
        .eyebrow::after { content: ""; width: 28px; height: 1px; background: #557d1d; }

        .page-heading h1 { font-size: 38px; line-height: 1; letter-spacing: -1.4px; margin-bottom: 9px; }
        .page-heading h1 span { color: #9dff00; }
        .page-heading p { color: #8e9992; font-size: 11px; line-height: 1.5; }

        /* ================= EVENT LIST ================= */
        .event-list { display: grid; gap: 28px; }

        .event-grid {
            display: grid;
            grid-template-columns: minmax(0, .78fr) minmax(0, 1.22fr);
            gap: 20px;
            align-items: stretch;
        }

        .event-card {
            border: 1px solid rgba(151, 255, 0, .12);
            border-radius: 12px;
            background: rgba(9, 17, 12, .90);
            overflow: hidden;
            box-shadow: 0 18px 50px rgba(0, 0, 0, .28);
        }

        .empty-event {
            padding: 60px 20px;
            text-align: center;
            color: #8e9992;
            font-size: 13px;
            line-height: 1.7;
        }

        .empty-event strong { display: block; color: #dce3df; font-size: 17px; margin-bottom: 6px; }

        /* ================= POSTER ================= */
        .poster-card {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 13px;
            background: linear-gradient(145deg, #07100b, #0b1510);
        }

        .poster-card img {
            display: block;
            width: 100%;
            max-width: 400px;
            height: auto;
            max-height: 610px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .50);
        }

        .poster-placeholder {
            width: 100%;
            max-width: 400px;
            aspect-ratio: 4 / 5;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed rgba(157, 255, 0, .25);
            border-radius: 8px;
            color: #557d1d;
            font-size: 48px;
        }

        /* ================= DETAIL ================= */
        .detail-card { padding: 26px; }
        .detail-heading { margin-bottom: 18px; }

        .detail-label {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #9dff00;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 9px;
        }

        .detail-label::before { content: ""; width: 34px; height: 2px; background: #9dff00; border-radius: 20px; }

        .detail-heading h2 {
            font-size: 38px;
            line-height: .98;
            letter-spacing: -1.6px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .detail-heading h2 span { color: #9dff00; }
        .detail-heading p { color: #88948d; font-size: 11px; line-height: 1.55; max-width: 620px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }

        .info-box {
            min-height: 72px;
            padding: 13px 14px;
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 8px;
            background: rgba(255, 255, 255, .025);
        }

        .info-label {
            color: #69756e;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.3px;
            margin-bottom: 7px;
        }

        .info-value { color: #dce3df; font-size: 12px; font-weight: 800; line-height: 1.35; }
        .info-value.green { color: #9dff00; }
        .info-value.muted { color: #ffc62d; }

        /* ================= PAYMENT ================= */
        .payment-info {
            margin-top: 10px;
            padding: 16px;
            border: 1px solid rgba(151, 255, 0, .10);
            border-radius: 9px;
            background: rgba(151, 255, 0, .035);
        }

        .payment-title {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e5ebe7;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .6px;
            margin-bottom: 5px;
        }

        .payment-icon {
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            background: rgba(157, 255, 0, .12);
            color: #9dff00;
            font-size: 13px;
        }

        .payment-info p { color: #7f8b84; font-size: 9px; line-height: 1.5; margin-bottom: 11px; }

        .price-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 11px;
        }

        .price-caption {
            color: #66726b;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.3px;
        }

        .price { color: #9dff00; font-size: 30px; font-weight: 900; line-height: 1; }

        .price-person {
            color: #69756e;
            font-size: 8px;
            font-weight: 700;
            padding-bottom: 3px;
            text-transform: uppercase;
        }

        .pay-button {
            width: 100%;
            min-height: 47px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 7px;
            background: #9dff00;
            color: #071006;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .5px;
            cursor: pointer;
            transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
        }

        .pay-button:hover {
            background: #b3ff4d;
            transform: translateY(-1px);
            box-shadow: 0 9px 22px rgba(157, 255, 0, .12);
        }

        .pay-button.disabled,
        .pay-button.disabled:hover {
            background: rgba(255, 255, 255, .08);
            color: #8d9992;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .trust-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7px; margin-top: 8px; }

        .trust-item {
            padding: 9px 7px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, .055);
            border-radius: 6px;
            background: rgba(255, 255, 255, .018);
        }

        .trust-icon { color: #9dff00; font-size: 12px; margin-bottom: 3px; }
        .trust-item strong { display: block; color: #b8c1bc; font-size: 7px; margin-bottom: 2px; }
        .trust-item span { display: block; color: #68736d; font-size: 7px; line-height: 1.3; }

        .event-note {
            margin-top: 8px;
            padding: 8px 10px;
            border-radius: 6px;
            color: #68746d;
            font-size: 7px;
            line-height: 1.45;
            background: rgba(255, 255, 255, .018);
            border: 1px solid rgba(255, 255, 255, .045);
        }

        .event-note strong { color: #929e97; }

        /* ================= FOOTER ================= */
        .footer { text-align: center; color: #4f5b54; font-size: 8px; padding: 18px 0 25px; }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 1000px) {
            .navbar { padding: 0 24px; }
            .nav-menu { gap: 14px; }
            .event-grid { grid-template-columns: 1fr; }
            .poster-card img { max-width: 430px; max-height: 620px; }
        }

        @media (max-width: 760px) {
            .navbar { min-height: 70px; padding: 14px 18px; flex-wrap: wrap; gap: 13px; }
            .nav-menu { width: 100%; overflow-x: auto; padding-bottom: 2px; justify-content: flex-start; }
            .nav-menu a, .user-name, .logout-btn { font-size: 11px; }
            .event-main { width: 94%; padding-bottom: 30px; }
            .page-heading h1 { font-size: 32px; }
            .page-heading p { font-size: 10px; }
            .detail-card { padding: 20px; }
            .detail-heading h2 { font-size: 32px; }
        }

        @media (max-width: 480px) {
            .info-grid, .trust-grid { grid-template-columns: 1fr; }
            .detail-card { padding: 17px; }
            .poster-card { padding: 9px; }
            .price-row { align-items: flex-start; flex-direction: column; }
        }
    </style>
    @include('partials.brand-head')
</head>

<body>

<div class="page">

    {{-- ================= NAVBAR ================= --}}
    @include('partials.site-navbar')

    {{-- ================= MAIN ================= --}}
    <main class="event-main">

        <header class="page-heading">
            <div class="eyebrow">GOLF EVENT</div>
            <h1>EVENT <span>GOLF BOOKING</span></h1>
            <p>Ikuti event Golf Booking Lesson dan amankan tempat Anda.</p>
        </header>

        @if ($events->isEmpty())
            <div class="event-card empty-event">
                <strong>Belum ada event terdekat</strong>
                Nantikan event Golf Booking Lesson berikutnya.
            </div>
        @else
            <div class="event-list">
                @foreach ($events as $event)
                    <section class="event-grid" id="event-{{ $event->id }}">

                        {{-- POSTER --}}
                        <div class="event-card poster-card">
                            @if ($event->poster_url)
                                <img src="{{ $event->poster_url }}" alt="Poster {{ $event->title }}">
                            @else
                                <div class="poster-placeholder">⛳</div>
                            @endif
                        </div>

                        {{-- DETAIL --}}
                        <div class="event-card detail-card">

                            <div class="detail-heading">
                                <div class="detail-label">DETAIL EVENT</div>

                                <h2>
                                    {{ $event->title_first }}
                                    @if ($event->title_rest)
                                        <span>{{ $event->title_rest }}</span>
                                    @endif
                                </h2>

                                @if ($event->description)
                                    <p>{{ $event->description }}</p>
                                @endif
                            </div>

                            <div class="info-grid">
                                <div class="info-box">
                                    <div class="info-label">Tanggal</div>
                                    <div class="info-value">{{ $event->date_label }}</div>
                                </div>

                                <div class="info-box">
                                    <div class="info-label">Waktu</div>
                                    <div class="info-value">{{ $event->time_range }}</div>
                                </div>

                                <div class="info-box">
                                    <div class="info-label">Lokasi</div>
                                    <div class="info-value">{{ $event->location ?: '-' }}</div>
                                </div>

                                <div class="info-box">
                                    <div class="info-label">Status</div>
                                    <div class="info-value {{ $event->canRegister() ? 'green' : 'muted' }}">
                                        {{ $event->status_label }}
                                    </div>
                                </div>
                            </div>

                            {{-- PAYMENT --}}
                            <div class="payment-info">

                                <div class="payment-title">
                                    <div class="payment-icon">$</div>
                                    <span>INFORMASI PEMBAYARAN</span>
                                </div>

                                <p>Lakukan pembayaran untuk mengamankan tempat Anda di event ini.</p>

                                <div class="price-row">
                                    <div>
                                        <div class="price-caption">Harga Tiket</div>
                                        <div class="price">{{ $event->price_label }}</div>
                                    </div>

                                    @if ($event->price > 0)
                                        <div class="price-person">/ {{ $event->price_unit }}</div>
                                    @endif
                                </div>

                                @if (! $event->canRegister())
                                    <span class="pay-button disabled">{{ mb_strtoupper($event->status_label) }}</span>
                                @elseif ($isCustomer)
                                    <a href="{{ route('payment', ['event' => $event->id]) }}" class="pay-button">
                                        <span>▣</span>
                                        <span>BAYAR SEKARANG</span>
                                        <span>→</span>
                                    </a>
                                @elseif ($isAdmin)
                                    <a href="{{ route('admin.events.edit', $event) }}" class="pay-button">
                                        <span>✎</span>
                                        <span>KELOLA EVENT INI</span>
                                        <span>→</span>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="pay-button">
                                        <span>▣</span>
                                        <span>BAYAR SEKARANG</span>
                                        <span>→</span>
                                    </a>
                                @endif

                                <div class="trust-grid">
                                    <div class="trust-item">
                                        <div class="trust-icon">✓</div>
                                        <strong>Pembayaran Aman</strong>
                                        <span>& Terpercaya</span>
                                    </div>

                                    <div class="trust-item">
                                        <div class="trust-icon">⚡</div>
                                        <strong>Proses Cepat</strong>
                                        <span>dan Mudah</span>
                                    </div>

                                    <div class="trust-item">
                                        <div class="trust-icon">☎</div>
                                        <strong>Dukungan Tim</strong>
                                        <span>Siap Membantu</span>
                                    </div>
                                </div>

                                @if ($event->note)
                                    <div class="event-note">
                                        <strong>* Catatan:</strong>
                                        {{ $event->note }}
                                    </div>
                                @endif

                            </div>
                        </div>

                    </section>
                @endforeach
            </div>
        @endif

    </main>

    <footer class="footer">
        &copy; {{ date('Y') }} Golf Booking Lesson. All rights reserved.
    </footer>

</div>

</body>

</html>
