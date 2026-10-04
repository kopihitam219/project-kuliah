@php
    use App\Models\Payment;
    use Carbon\Carbon;

    $date      = Carbon::parse($booking->booking_date);
    $startTime = substr($booking->start_time, 0, 5);
    $endTime   = substr($booking->end_time, 0, 5);
    $hours     = $payment->duration_minutes / 60;
    $rate      = $payment->duration_minutes > 0 ? (int) round($payment->amount * 60 / $payment->duration_minutes) : Payment::pricePerHour();

    // Metode yang sedang dipakai & apakah perlu dicek admin (QRIS asli / transfer rekening)
    $isReal = (bool) ($activeMethod['real'] ?? false);
    $allDummy = collect($methods)->every(fn ($m) => ! ($m['real'] ?? false));

    $bookingActive = in_array($booking->status, ['pending', 'booked'], true);

    $bookingStatusLabel = [
        'pending'   => 'Menunggu approval admin',
        'booked'    => 'Disetujui admin',
        'cancelled' => 'Dibatalkan',
        'rejected'  => 'Ditolak',
    ][$booking->status] ?? ucfirst($booking->status);

    /*
    |--------------------------------------------------------------------------
    | QR DUMMY (pola acak dari nomor referensi, bukan QR sungguhan)
    |--------------------------------------------------------------------------
    */
    $qrSize  = 25;
    $qrBits  = '';
    $qrSeed  = $payment->reference . '|' . $payment->amount;

    for ($round = 0; strlen($qrBits) < $qrSize * $qrSize; $round++) {
        foreach (str_split(hash('sha256', $qrSeed . $round)) as $hex) {
            $qrBits .= str_pad(base_convert($hex, 16, 2), 4, '0', STR_PAD_LEFT);
        }
    }

    $inFinder = function (int $x, int $y) use ($qrSize) {
        foreach ([[0, 0], [$qrSize - 7, 0], [0, $qrSize - 7]] as [$fx, $fy]) {
            if ($x >= $fx && $x < $fx + 7 && $y >= $fy && $y < $fy + 7) {
                return true;
            }
        }
        return false;
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran | {{ \App\Support\Brand::name() }}</title>
    @include('partials.brand-head')

    <style>
        :root {
            --lime: #b8ff00;
            --yellow: #ffc400;
            --red: #ff5c5c;
            --blue: #5ca8ff;
            --text: #f4f7f4;
            --muted: #8a9690;
            --border: rgba(184, 255, 0, .16);
            --line: rgba(255, 255, 255, .08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100%; }

        body {
            color: var(--text);
            background:
                linear-gradient(rgba(1, 12, 9, .80), rgba(1, 12, 9, .92)),
                url('{{ \App\Support\Brand::background('public') }}') center / cover fixed no-repeat;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        a { color: inherit; text-decoration: none; }
        button { font: inherit; }

        /* NAVBAR */
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
        .brand span { color: var(--lime); }
        .nav-right { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .nav-link { padding: 8px 11px; border-radius: 8px; color: #cbd2cf; font-size: 13px; font-weight: 600; }
        .nav-link:hover { color: var(--lime); }
        .booking-nav { padding: 9px 18px; border-radius: 9px; background: var(--lime); color: #071000; font-size: 12px; font-weight: 800; }
        .user-name { padding: 8px 12px; color: #cbd2cf; background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .07); border-radius: 9px; font-size: 12px; font-weight: 600; }
        .logout-button { padding: 8px 10px; border: 0; background: transparent; color: #aab3af; font-size: 12px; font-weight: 600; cursor: pointer; }

        /* PAGE */
        .page { width: min(1180px, calc(100% - 40px)); margin: auto; padding: 30px 0 40px; }

        .steps { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; color: var(--muted); font-size: 12px; font-weight: 700; flex-wrap: wrap; }
        .step { display: flex; align-items: center; gap: 7px; }
        .step-number { width: 22px; height: 22px; display: grid; place-items: center; border-radius: 50%; border: 1px solid rgba(255, 255, 255, .2); font-size: 11px; }
        .step.done .step-number { background: var(--lime); border-color: var(--lime); color: #071000; }
        .step.current { color: #fff; }
        .step.current .step-number { border-color: var(--lime); color: var(--lime); }
        .step-line { width: 28px; height: 1px; background: rgba(255, 255, 255, .15); }

        .page-heading h1 { font-size: 32px; font-weight: 800; letter-spacing: -1px; }
        .page-heading p { margin-top: 6px; color: var(--muted); font-size: 14px; }

        .demo-banner {
            margin: 16px 0;
            padding: 10px 14px;
            border: 1px dashed rgba(255, 196, 0, .45);
            border-radius: 10px;
            background: rgba(255, 196, 0, .06);
            color: #ffd45c;
            font-size: 12px;
            line-height: 1.5;
        }

        .alert { margin-bottom: 14px; padding: 12px 15px; border-radius: 10px; font-size: 13px; }
        .alert-success { color: #d9ff79; background: rgba(67, 105, 12, .22); border: 1px solid rgba(184, 255, 0, .25); }
        .alert-error { color: #ffb4b4; background: rgba(80, 15, 15, .35); border: 1px solid rgba(255, 92, 92, .35); }

        .layout { display: grid; grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr); gap: 20px; align-items: start; }

        .panel {
            padding: 22px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(7, 30, 23, .95), rgba(2, 18, 13, .94));
            box-shadow: 0 15px 45px rgba(0, 0, 0, .22);
        }

        .panel-title { margin-bottom: 16px; font-size: 17px; font-weight: 800; }

        /* RINGKASAN */
        .summary-row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 13px; }
        .summary-row span:first-child { color: var(--muted); }
        .summary-row span:last-child { font-weight: 700; text-align: right; }
        .summary-total { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 16px; }
        .summary-total span { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .summary-total strong { color: var(--lime); font-size: 30px; font-weight: 900; line-height: 1; }
        .summary-note { margin-top: 8px; color: var(--muted); font-size: 11px; text-align: right; }

        .pill { display: inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; }
        .pill.unpaid    { background: rgba(255, 255, 255, .08); color: #d5ddd8; }
        .pill.pending   { background: rgba(255, 196, 0, .16); color: #ffd45c; border: 1px solid rgba(255, 196, 0, .3); }
        .pill.paid      { background: rgba(184, 255, 0, .14); color: var(--lime); }
        .pill.verifying { background: rgba(92, 168, 255, .16); color: #9ccdff; }
        .verify-box { padding: 22px; text-align: center; }
        .verify-icon { width: 64px; height: 64px; margin: 0 auto 14px; display: grid; place-items: center; border-radius: 50%; background: rgba(92, 168, 255, .16); color: #9ccdff; font-size: 28px; }
        .verify-box h2 { font-size: 20px; font-weight: 800; }
        .verify-box p { margin-top: 6px; color: var(--muted); font-size: 13px; line-height: 1.6; }
        .qr-real { width: 240px; max-width: 100%; height: auto; border-radius: 8px; }
        .real-note { margin-top: 14px; padding: 10px 12px; border-radius: 9px; background: rgba(92, 168, 255, .08); border: 1px solid rgba(92, 168, 255, .25); color: #b9dcff; font-size: 12px; line-height: 1.5; }

        /* Langkah */
        .step-head { display: flex; align-items: center; gap: 10px; margin: 6px 0 12px; }
        .step-badge {
            width: 26px; height: 26px; flex: 0 0 26px; display: grid; place-items: center;
            border-radius: 50%; background: var(--lime); color: #071000; font-size: 12px; font-weight: 900;
        }
        .step-head strong { display: block; font-size: 14px; font-weight: 800; }
        .step-head small { display: block; margin-top: 2px; color: var(--muted); font-size: 11px; }

        /* Upload bukti */
        .proof-card { margin-top: 18px; padding-top: 16px; border-top: 1px dashed rgba(255, 255, 255, .12); }

        .proof-drop {
            position: relative;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
            min-height: 150px; padding: 16px;
            border: 2px dashed rgba(184, 255, 0, .35); border-radius: 12px;
            background: rgba(184, 255, 0, .03);
            color: var(--muted); font-size: 12px; text-align: center;
            cursor: pointer; transition: border-color .15s ease, background .15s ease;
        }
        .proof-drop:hover, .proof-drop.dragging { border-color: var(--lime); background: rgba(184, 255, 0, .07); }
        .proof-drop strong { color: var(--lime); font-size: 13px; }
        .proof-drop input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .proof-drop img { max-width: 100%; max-height: 260px; border-radius: 8px; }
        .proof-icon { font-size: 26px; color: var(--lime); }
        .proof-name { margin-top: 6px; color: var(--muted); font-size: 11px; text-align: center; word-break: break-all; }

        .proof-thumb {
            display: block; margin: 14px auto 0; max-width: 240px;
            border: 1px solid var(--line); border-radius: 10px; overflow: hidden;
        }
        .proof-thumb img { display: block; width: 100%; height: auto; }
        .proof-link { display: inline-block; margin-top: 8px; color: var(--lime); font-size: 12px; font-weight: 700; }
        .pill.cancelled { background: rgba(255, 92, 92, .14); color: #ff9a9a; }

        /* METODE */
        .method-list { display: grid; gap: 10px; }
        .method-option { position: relative; display: block; cursor: pointer; }
        .method-option input { position: absolute; opacity: 0; pointer-events: none; }

        .method-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 15px 16px;
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 12px;
            background: rgba(255, 255, 255, .03);
            transition: border-color .15s ease, background .15s ease;
        }

        .method-option:hover .method-card { border-color: rgba(184, 255, 0, .35); }
        .method-option input:checked + .method-card { border-color: var(--lime); background: rgba(184, 255, 0, .07); }
        .method-option input:focus-visible + .method-card { outline: 2px solid var(--lime); outline-offset: 2px; }

        .method-logo {
            width: 64px;
            height: 40px;
            flex: 0 0 64px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: #ffffff;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: -.3px;
        }

        .method-logo.qris    { color: #d4145a; }
        .method-logo.mandiri { color: #003d79; }
        .method-logo.bca     { color: #0060af; }

        .method-text strong { display: block; font-size: 14px; }
        .method-text small { display: block; margin-top: 3px; color: var(--muted); font-size: 12px; }

        .method-check {
            margin-left: auto;
            width: 20px;
            height: 20px;
            flex: 0 0 20px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, .25);
        }

        .method-option input:checked + .method-card .method-check { border-color: var(--lime); background: radial-gradient(var(--lime) 45%, transparent 50%); }

        .btn {
            width: 100%;
            height: 50px;
            margin-top: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 10px;
            background: var(--lime);
            color: #071000;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: background .15s ease;
        }

        .btn:hover { background: #d0ff45; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-ghost { background: transparent; color: #aab3af; border: 1px solid rgba(255, 255, 255, .12); height: 42px; font-size: 12px; }
        .btn-ghost:hover { background: rgba(255, 255, 255, .05); color: #fff; }

        /* INSTRUKSI */
        .countdown {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 16px;
            padding: 11px 14px;
            border-radius: 10px;
            background: rgba(255, 196, 0, .08);
            border: 1px solid rgba(255, 196, 0, .22);
            color: #ffd45c;
            font-size: 12px;
            font-weight: 700;
        }

        .countdown strong { font-size: 16px; font-variant-numeric: tabular-nums; }

        .qr-box { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 18px; border-radius: 12px; background: #ffffff; color: #111; }
        .qr-box svg { width: 210px; height: 210px; }
        .qr-box strong { font-size: 13px; }
        .qr-box small { color: #666; font-size: 11px; }

        .va-box { padding: 16px; border-radius: 12px; border: 1px solid var(--line); background: rgba(0, 0, 0, .2); }
        .va-label { color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .va-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 6px; }
        .va-number { font-size: 24px; font-weight: 900; letter-spacing: 1.5px; font-variant-numeric: tabular-nums; }

        .copy-btn {
            height: 34px;
            padding: 0 14px;
            border: 1px solid rgba(184, 255, 0, .4);
            border-radius: 8px;
            background: transparent;
            color: var(--lime);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
        }

        .copy-btn:hover { background: rgba(184, 255, 0, .1); }

        .amount-line { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--line); }
        .amount-line span { color: var(--muted); font-size: 12px; }
        .amount-line strong { color: var(--lime); font-size: 20px; font-weight: 900; }

        .how-to { margin-top: 16px; padding-left: 18px; color: #c5cdc9; font-size: 12px; line-height: 1.8; }

        /* SUKSES */
        .success { text-align: center; padding: 10px 0; }
        .success-icon { width: 64px; height: 64px; margin: 0 auto 14px; display: grid; place-items: center; border-radius: 50%; background: var(--lime); color: #071000; font-size: 30px; font-weight: 900; }
        .success h2 { font-size: 22px; font-weight: 800; }
        .success p { margin-top: 6px; color: var(--muted); font-size: 13px; line-height: 1.6; }
        .receipt { margin-top: 18px; text-align: left; }

        .blocked { padding: 26px; text-align: center; color: var(--muted); font-size: 13px; line-height: 1.6; }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .nav-link { display: none; }
        }

        @media (max-width: 520px) {
            .page { width: calc(100% - 20px); }
            .panel { padding: 16px; }
            .va-number { font-size: 19px; }
            .logout-button { display: none; }
        }
    </style>
</head>
<body>

@include('partials.site-navbar')

{{-- ============================== PAGE ============================== --}}
<main class="page">

    <div class="steps">
        <span class="step done"><span class="step-number">✓</span> Pilih jadwal</span>
        <span class="step-line"></span>
        <span class="step {{ $payment->status === 'paid' ? 'done' : 'current' }}">
            <span class="step-number">{{ $payment->status === 'paid' ? '✓' : '2' }}</span> Pembayaran
        </span>
        <span class="step-line"></span>
        <span class="step {{ $booking->status === 'booked' ? 'done' : (in_array($payment->status, ['paid', 'verifying'], true) ? 'current' : '') }}">
            <span class="step-number">{{ $booking->status === 'booked' ? '✓' : '3' }}</span> Konfirmasi admin
        </span>
    </div>

    <div class="page-heading">
        <h1>Pembayaran Lesson</h1>
        <p>Selesaikan pembayaran untuk mengamankan jadwal lesson Anda.</p>
    </div>

    @if (\App\Support\BookingRules::isDemoPayment() && ! in_array($payment->status, ['paid', 'verifying'], true))
        <div class="demo-banner">
            <strong>Mode demo:</strong> QR dan nomor rekening di halaman ini hanya contoh.
            Jangan mentransfer uang sungguhan. Tekan "Saya sudah bayar (simulasi)" untuk mencoba alurnya.
        </div>
    @endif

    @if (session('booking_success'))
        <div class="alert alert-success">{{ session('booking_success') }}</div>
    @endif

    @if (session('payment_success'))
        <div class="alert alert-success">{{ session('payment_success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="layout">

        {{-- ==================== RINGKASAN BOOKING ==================== --}}
        <section class="panel">
            <div class="panel-title">Ringkasan booking</div>

            <div class="summary-row"><span>No. referensi</span><span>{{ $payment->reference }}</span></div>
            <div class="summary-row"><span>Tanggal</span><span>{{ $date->locale('id')->translatedFormat('l, d F Y') }}</span></div>
            <div class="summary-row"><span>Jam</span><span>{{ $startTime }} – {{ $endTime }}</span></div>
            <div class="summary-row"><span>Durasi</span><span>{{ $payment->duration_label }}</span></div>
            <div class="summary-row">
                <span>Harga per jam</span>
                <span>{{ Payment::formatRupiah($rate) }}</span>
            </div>
            <div class="summary-row">
                <span>Status booking</span>
                <span>{{ $bookingStatusLabel }}</span>
            </div>
            <div class="summary-row">
                <span>Status pembayaran</span>
                <span><span class="pill {{ $payment->status }}">{{ $payment->status_label }}</span></span>
            </div>

            <div class="summary-total">
                <span>Total</span>
                <strong>{{ $payment->amount_label }}</strong>
            </div>
            <div class="summary-note">
                {{ rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',') }} jam × {{ Payment::formatRupiah($rate) }}
            </div>
        </section>

        {{-- ==================== PEMBAYARAN ==================== --}}
        <section class="panel">

            @if ($payment->status === 'paid')

                {{-- ---------- SUDAH LUNAS ---------- --}}
                <div class="success">
                    <div class="success-icon">✓</div>
                    <h2>Pembayaran berhasil</h2>
                    <p>
                        Terima kasih! Pembayaran Anda sudah kami terima.
                        @if ($booking->status === 'pending')
                            Booking sedang menunggu konfirmasi admin, dan Anda akan mendapat notifikasi setelah disetujui.
                        @elseif ($booking->status === 'booked')
                            Booking Anda sudah dikonfirmasi. Sampai jumpa di lapangan!
                        @endif
                    </p>

                    <div class="receipt">
                        <div class="summary-row"><span>Metode</span><span>{{ $payment->method_label }}</span></div>
                        <div class="summary-row"><span>Dibayar pada</span><span>{{ $payment->paid_at?->locale('id')->translatedFormat('d M Y, H:i') }}</span></div>
                        <div class="summary-row"><span>Jumlah</span><span>{{ $payment->amount_label }}</span></div>
                        @if ($payment->hasProof())
                            <div class="summary-row">
                                <span>Bukti pembayaran</span>
                                <span><a href="{{ route('payment.booking.proof', $booking) }}" target="_blank" rel="noopener" class="proof-link" style="margin: 0">Lihat bukti</a></span>
                            </div>
                        @endif
                    </div>

                    <a href="{{ route('booking', ['date' => $date->format('Y-m-d')]) }}" class="btn">Kembali ke halaman booking</a>
                </div>

            @elseif ($payment->status === 'verifying')

                {{-- ---------- MENUNGGU VERIFIKASI ADMIN ---------- --}}
                <div class="verify-box">
                    <div class="verify-icon">◷</div>
                    <h2>Pembayaran sedang dicek</h2>
                    <p>
                        Anda sudah mengonfirmasi pembayaran {{ $payment->amount_label }} via {{ $payment->method_label }}.
                        Admin akan mengecek dana yang masuk dan mengonfirmasi secepatnya.
                        Anda akan mendapat notifikasi setelah pembayaran dikonfirmasi.
                    </p>

                    @if ($payment->hasProof())
                        <a href="{{ route('payment.booking.proof', $booking) }}" target="_blank" rel="noopener" class="proof-thumb">
                            <img src="{{ route('payment.booking.proof', $booking) }}" alt="Bukti pembayaran yang Anda kirim">
                        </a>
                        <span class="proof-link">Bukti pembayaran terkirim {{ $payment->proof_uploaded_at?->locale('id')->diffForHumans() }}</span>
                    @endif

                    <a href="{{ route('booking', ['date' => $date->format('Y-m-d')]) }}" class="btn">Kembali ke halaman booking</a>
                </div>

            @elseif (! $bookingActive)

                {{-- ---------- BOOKING TIDAK AKTIF ---------- --}}
                <div class="blocked">
                    Booking ini sudah <strong>{{ strtolower($bookingStatusLabel) }}</strong>, sehingga tidak perlu dibayar.
                    <a href="{{ route('booking') }}" class="btn">Buat booking baru</a>
                </div>

            @elseif ($payment->status === 'pending' && $activeMethod)

                {{-- ---------- INSTRUKSI PEMBAYARAN ---------- --}}
                <div class="panel-title">Bayar dengan {{ $payment->method_label }}</div>

                <div class="countdown">
                    <span>Selesaikan sebelum {{ $payment->expires_at->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                    <strong id="countdown" data-expires="{{ $payment->expires_at->toIso8601String() }}">--:--:--</strong>
                </div>

                <div class="step-head">
                    <span class="step-badge">1</span>
                    <div>
                        <strong>{{ $activeMethod['type'] === 'qris' ? 'Scan & bayar' : 'Transfer ke rekening' }}</strong>
                        <small>Bayar tepat {{ $payment->amount_label }}</small>
                    </div>
                </div>

                @if ($activeMethod['type'] === 'qris')
                    <div class="qr-box">
                        @if ($activeMethod['image'])
                            <img src="{{ $activeMethod['image'] }}" alt="QRIS {{ $activeMethod['merchant'] }}" class="qr-real">
                        @else
                            <svg viewBox="0 0 {{ $qrSize }} {{ $qrSize }}" shape-rendering="crispEdges" aria-label="QR code dummy">
                                <rect width="{{ $qrSize }}" height="{{ $qrSize }}" fill="#fff"/>
                                @for ($y = 0; $y < $qrSize; $y++)
                                    @for ($x = 0; $x < $qrSize; $x++)
                                        @if (! $inFinder($x, $y) && $qrBits[$y * $qrSize + $x] === '1')
                                            <rect x="{{ $x }}" y="{{ $y }}" width="1" height="1" fill="#111"/>
                                        @endif
                                    @endfor
                                @endfor
                                @foreach ([[0, 0], [$qrSize - 7, 0], [0, $qrSize - 7]] as $finder)
                                    <rect x="{{ $finder[0] }}" y="{{ $finder[1] }}" width="7" height="7" fill="#111"/>
                                    <rect x="{{ $finder[0] + 1 }}" y="{{ $finder[1] + 1 }}" width="5" height="5" fill="#fff"/>
                                    <rect x="{{ $finder[0] + 2 }}" y="{{ $finder[1] + 2 }}" width="3" height="3" fill="#111"/>
                                @endforeach
                            </svg>
                        @endif
                        <strong>{{ $activeMethod['merchant'] }}</strong>
                        <small>
                            @if ($activeMethod['nmid']) NMID: {{ $activeMethod['nmid'] }} @endif
                            {{ $activeMethod['real'] ? '' : '· contoh (demo)' }}
                        </small>
                    </div>

                    <div class="amount-line">
                        <span>Total yang harus dibayar</span>
                        <strong>{{ $payment->amount_label }}</strong>
                    </div>

                    <ol class="how-to">
                        <li>Buka aplikasi e-wallet atau m-banking yang mendukung QRIS.</li>
                        <li>Pilih menu <strong>Scan / Bayar</strong>, lalu arahkan kamera ke QR di atas.</li>
                        <li>Masukkan nominal <strong>{{ $payment->amount_label }}</strong> jika diminta, dan pastikan nama merchant sesuai.</li>
                        <li>Selesaikan pembayaran, simpan bukti (screenshot), lalu upload di langkah 2.</li>
                    </ol>

                @elseif ($activeMethod['type'] === 'transfer')
                    <div class="va-box">
                        <div class="va-label">Nomor rekening {{ $activeMethod['label'] }}</div>
                        <div class="va-row">
                            <span class="va-number">{{ trim(chunk_split((string) $activeMethod['account'], 4, ' ')) }}</span>
                            <button type="button" class="copy-btn" data-copy="{{ $activeMethod['account'] }}">Salin</button>
                        </div>
                        <div class="summary-row" style="border-bottom: 0; padding-bottom: 0">
                            <span>Atas nama</span><span>{{ $activeMethod['holder'] }}</span>
                        </div>

                        <div class="amount-line">
                            <span>Total yang harus ditransfer</span>
                            <strong>{{ $payment->amount_label }}</strong>
                        </div>
                    </div>

                    <ol class="how-to">
                        <li>Transfer tepat <strong>{{ $payment->amount_label }}</strong> ke rekening {{ $activeMethod['label'] }} di atas.</li>
                        <li>Tulis <strong>{{ $payment->reference }}</strong> di kolom berita / keterangan transfer.</li>
                        <li>Pastikan nama penerima <strong>{{ $activeMethod['holder'] }}</strong>.</li>
                        <li>Simpan bukti transfer (screenshot), lalu upload di langkah 2.</li>
                    </ol>

                @endif

                <div class="proof-card">
                    <div class="step-head">
                        <span class="step-badge">2</span>
                        <div>
                            <strong>Upload bukti pembayaran</strong>
                            <small>
                                Screenshot / foto bukti transfer bank atau pembayaran QRIS
                                {{ $isReal ? '(wajib)' : '(opsional di mode demo)' }}
                            </small>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('payment.booking.confirm', $booking) }}" enctype="multipart/form-data"
                          id="confirmForm" data-real="{{ $isReal ? '1' : '0' }}">
                        @csrf

                        <label class="proof-drop" id="proofDrop">
                            <span class="proof-icon" id="proofIcon">⇪</span>
                            <span id="proofText"><strong>Pilih gambar</strong> atau seret ke sini</span>
                            <small id="proofHint">JPG, PNG, atau WEBP · maks. 4 MB</small>
                            <img id="proofPreview" alt="Pratinjau bukti pembayaran" hidden>
                            <input type="file" name="proof" id="proofInput" accept="image/jpeg,image/png,image/webp" @if ($isReal) required @endif>
                        </label>
                        <div class="proof-name" id="proofName"></div>

                        @if ($isReal)
                            <div class="real-note">Admin akan mencocokkan bukti ini dengan dana yang masuk sebelum pembayaran dinyatakan lunas.</div>
                        @endif

                        <button type="submit" class="btn">{{ $isReal ? 'Kirim bukti & konfirmasi pembayaran' : 'Saya sudah bayar (simulasi)' }}</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('payment.booking.reset', $booking) }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost">Ganti metode pembayaran</button>
                </form>

            @elseif (empty($methods))

                <div class="blocked">
                    Belum ada metode pembayaran yang aktif. Silakan hubungi admin.
                </div>

            @else

                {{-- ---------- PILIH METODE ---------- --}}
                <div class="panel-title">Pilih metode pembayaran</div>

                <form method="POST" action="{{ route('payment.booking.method', $booking) }}" id="methodForm">
                    @csrf

                    <div class="method-list">
                        @foreach ($methods as $key => $method)
                            <label class="method-option">
                                <input type="radio" name="method" value="{{ $key }}" @checked(old('method') === $key)>
                                <span class="method-card">
                                    <span class="method-logo {{ $key }}">
                                        {{ $key === 'qris' ? 'QRIS' : ($key === 'mandiri' ? 'mandiri' : 'BCA') }}
                                    </span>
                                    <span class="method-text">
                                        <strong>{{ $method['label'] }}</strong>
                                        <small>{{ $method['desc'] }}</small>
                                    </span>
                                    <span class="method-check"></span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="btn" id="methodSubmit" disabled>Lanjutkan pembayaran {{ $payment->amount_label }}</button>
                </form>

            @endif

        </section>

    </div>

</main>

<script>
    (() => {
        /* Aktifkan tombol setelah memilih metode */
        const methodForm = document.getElementById('methodForm');
        if (methodForm) {
            const submit = document.getElementById('methodSubmit');
            const sync = () => { submit.disabled = !methodForm.querySelector('input[name="method"]:checked'); };
            methodForm.addEventListener('change', sync);
            sync();
        }

        /* Salin nomor VA */
        document.querySelectorAll('[data-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(button.dataset.copy);
                    button.textContent = 'Tersalin ✓';
                    setTimeout(() => { button.textContent = 'Salin'; }, 1800);
                } catch (e) {
                    window.prompt('Salin nomor ini:', button.dataset.copy);
                }
            });
        });

        /* Hitung mundur batas pembayaran */
        const countdown = document.getElementById('countdown');
        if (countdown) {
            const expires = new Date(countdown.dataset.expires).getTime();
            const pad = (n) => String(n).padStart(2, '0');

            let timer = null;

            const tick = () => {
                const left = Math.max(0, Math.floor((expires - Date.now()) / 1000));

                if (left === 0) {
                    countdown.textContent = 'Waktu habis, muat ulang halaman';
                    clearInterval(timer);
                    return;
                }

                countdown.textContent = pad(Math.floor(left / 3600)) + ':' + pad(Math.floor(left % 3600 / 60)) + ':' + pad(left % 60);
            };

            tick();
            timer = setInterval(tick, 1000);
        }

        /* Upload bukti: pratinjau, seret & lepas, cek ukuran */
        const proofInput = document.getElementById('proofInput');
        if (proofInput) {
            const drop = document.getElementById('proofDrop');
            const preview = document.getElementById('proofPreview');
            const nameEl = document.getElementById('proofName');
            const hideEls = ['proofIcon', 'proofText', 'proofHint'].map((id) => document.getElementById(id));

            const showFile = () => {
                const file = proofInput.files[0];
                if (!file) return;

                if (file.size > 4 * 1024 * 1024) {
                    alert('Ukuran gambar maksimal 4 MB.');
                    proofInput.value = '';
                    return;
                }

                preview.src = URL.createObjectURL(file);
                preview.hidden = false;
                hideEls.forEach((el) => { el.hidden = true; });
                nameEl.textContent = file.name + ' · klik gambar untuk mengganti';
            };

            proofInput.addEventListener('change', showFile);

            ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (event) => {
                event.preventDefault();
                drop.classList.add('dragging');
            }));

            ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, () => drop.classList.remove('dragging')));

            drop.addEventListener('drop', (event) => {
                event.preventDefault();
                if (event.dataTransfer.files.length) {
                    proofInput.files = event.dataTransfer.files;
                    showFile();
                }
            });
        }

        /* Konfirmasi pembayaran */
        const confirmForm = document.getElementById('confirmForm');
        if (confirmForm) {
            confirmForm.addEventListener('submit', (event) => {
                const message = confirmForm.dataset.real === '1'
                    ? 'Kirim bukti pembayaran ini ke admin?\n\nPastikan nominal transfer sesuai.'
                    : 'Tandai pembayaran ini sebagai sudah dibayar?\n\n(Mode demo: tidak ada uang yang ditransfer.)';
                if (!confirm(message)) {
                    event.preventDefault();
                    return;
                }
                confirmForm.querySelector('button[type="submit"]').disabled = true;
            });
        }
    })();
</script>

</body>
</html>
