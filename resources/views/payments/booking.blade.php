@php
    use App\Models\Payment;
    use Carbon\Carbon;

    $date      = Carbon::parse($booking->booking_date);
    $startTime = substr($booking->start_time, 0, 5);
    $endTime   = substr($booking->end_time, 0, 5);
    $hours     = $payment->duration_minutes / 60;
    $rate      = $payment->duration_minutes > 0 ? (int) round($payment->amount * 60 / $payment->duration_minutes) : Payment::pricePerHour();
    $isCourse  = $booking->isCourse();
    $place     = $booking->place_label;

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
@extends('layouts.fw')

@section('title', 'Pembayaran')
@section('no_footer', true)

@push('head')
<style>
    .pb .steps { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; color: var(--fw-muted); font-size: 13px; overflow-x: auto; scrollbar-width: none; }
    .pb .step { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
    .pb .step-number { width: 26px; height: 26px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-bg-2); color: var(--fw-muted); font-size: 12px; font-weight: 700; }
    .pb .step.done .step-number { background: var(--fw-green); color: #fff; }
    .pb .step.current { color: var(--fw-text); font-weight: 600; }
    @media (max-width: 480px) { .pb .steps { gap: 6px; font-size: 12px; } .pb .steps .step-line { min-width: 10px; } }
    .pb .step.current .step-number { background: var(--fw-lime); color: var(--fw-green); }
    .pb .step-line { flex: 1; min-width: 20px; height: 2px; background: var(--fw-line-2); border-radius: 2px; }
    .pb .page-heading h1 { font-family: var(--fw-serif); font-size: clamp(26px, 3.6vw, 36px); font-weight: 600; letter-spacing: -.4px; }
    .pb .page-heading p { margin: 4px 0 18px; color: var(--fw-muted); font-size: 14px; }
    .pb .demo-banner { margin-bottom: 14px; padding: 12px 16px; border-radius: 14px; background: var(--fw-orange-tint); border: 1px solid rgba(233, 162, 59, .35); color: #8a5608; font-size: 13.5px; line-height: 1.55; }
    .pb .alert { margin-bottom: 14px; padding: 12px 16px; border-radius: 14px; font-size: 14px; }
    .pb .alert-success { background: var(--fw-tint); border: 1px solid rgba(31, 77, 51, .2); color: var(--fw-green); }
    .pb .alert-error { background: var(--fw-red-tint); border: 1px solid rgba(201, 65, 58, .3); color: #8f2a24; }
    .pb .layout { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 18px; align-items: start; }
    .pb .panel { padding: 22px; border: 1px solid var(--fw-line); border-radius: 22px; background: var(--fw-surface); box-shadow: var(--fw-shadow); }
    .pb .panel-title { margin-bottom: 14px; font-family: var(--fw-serif); font-size: 21px; font-weight: 600; }
    .pb .summary-row { display: flex; justify-content: space-between; gap: 14px; padding: 9px 0; border-bottom: 1px dashed var(--fw-line-2); font-size: 13.5px; }
    .pb .summary-row span:first-child { color: var(--fw-muted); }
    .pb .summary-row span:last-child { font-weight: 600; text-align: right; }
    .pb .pill { display: inline-flex; padding: 3px 10px; border-radius: 99px; background: var(--fw-bg-2); color: var(--fw-text-2); font-size: 11.5px; font-weight: 600; }
    .pb .pill.paid { background: var(--fw-green); color: #fff; }
    .pb .pill.verifying, .pb .pill.pending, .pb .pill.unpaid { background: var(--fw-orange-tint); color: #a2650c; }
    .pb .pill.cash { background: var(--fw-blue-tint); color: var(--fw-blue); }
    .pb .pill.cancelled { background: var(--fw-red-tint); color: var(--fw-red); }
    .pb .summary-total { display: flex; justify-content: space-between; align-items: baseline; margin-top: 14px; padding: 14px 16px; border-radius: 16px; background: var(--fw-green); color: #fff; }
    .pb .summary-total strong { font-family: var(--fw-serif); font-size: 26px; font-weight: 600; }
    .pb .summary-note { margin-top: 8px; color: var(--fw-muted); font-size: 12.5px; }
    .pb .btn { width: 100%; height: 48px; margin-top: 14px; display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 99px; background: var(--fw-green); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
    .pb .btn:hover { background: var(--fw-green-2); }
    .pb .btn:disabled { opacity: .45; cursor: not-allowed; }
    .pb .btn-ghost { background: var(--fw-surface); border: 1px solid var(--fw-line-2); color: var(--fw-text); }
    .pb .btn-ghost:hover { background: var(--fw-surface-2); }
    .pb .success, .pb .verify-box, .pb .cash-box { text-align: center; }
    .pb .success-icon, .pb .verify-icon, .pb .cash-icon { width: 64px; height: 64px; margin: 4px auto 12px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-green); color: #fff; font-size: 26px; font-weight: 700; }
    .pb .verify-icon { background: var(--fw-orange-tint); color: #a2650c; }
    .pb .cash-icon { background: var(--fw-blue-tint); color: var(--fw-blue); font-size: 18px; }
    .pb .success h2, .pb .verify-box h2, .pb .cash-box h2 { font-family: var(--fw-serif); font-size: 24px; font-weight: 600; }
    .pb .success p, .pb .verify-box p, .pb .cash-box p { margin-top: 8px; color: var(--fw-text-2); font-size: 14px; line-height: 1.65; }
    .pb .receipt { margin-top: 16px; padding: 4px 16px; border-radius: 16px; background: var(--fw-surface-2); text-align: left; }
    .pb .cash-amount { margin-top: 12px; font-family: var(--fw-serif); font-size: 32px; font-weight: 600; color: var(--fw-green); }
    .pb .cash-steps, .pb .how-to { margin: 14px 0 0; padding-left: 20px; display: grid; gap: 8px; text-align: left; color: var(--fw-text-2); font-size: 13.5px; line-height: 1.55; }
    .pb .proof-thumb { display: block; width: 160px; margin: 14px auto 6px; border-radius: 14px; overflow: hidden; border: 1px solid var(--fw-line); }
    .pb .proof-link { display: inline-block; margin-top: 6px; color: var(--fw-green); font-size: 13px; font-weight: 600; }
    .pb .blocked { padding: 16px; border-radius: 16px; background: var(--fw-red-tint); color: #8f2a24; font-size: 14px; line-height: 1.6; }
    .pb .countdown { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; padding: 12px 16px; border-radius: 14px; background: var(--fw-orange-tint); color: #8a5608; font-size: 13px; }
    .pb .countdown strong { font-size: 20px; font-variant-numeric: tabular-nums; }
    .pb .countdown.urgent { background: var(--fw-red-tint); color: var(--fw-red); }
    .pb .step-head { display: flex; gap: 12px; align-items: center; margin: 4px 0 12px; }
    .pb .step-head strong { display: block; font-size: 15px; }
    .pb .step-head small { color: var(--fw-muted); font-size: 12.5px; }
    .pb .step-badge { width: 30px; height: 30px; flex: 0 0 30px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-green); color: #fff; font-size: 13px; font-weight: 700; }
    .pb .qr-box { display: grid; justify-items: center; gap: 6px; padding: 18px; border-radius: 18px; background: var(--fw-surface-2); border: 1px solid var(--fw-line); }
    .pb .qr-box svg, .pb .qr-real { width: 220px; height: 220px; padding: 10px; border-radius: 14px; background: #fff; }
    .pb .qr-box small { color: var(--fw-muted); font-size: 12px; }
    .pb .amount-line { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; margin-top: 12px; padding: 12px 14px; border-radius: 14px; background: var(--fw-tint-2); font-size: 13.5px; }
    .pb .amount-line strong { font-size: 20px; color: var(--fw-green); }
    .pb .va-box { padding: 16px; border-radius: 18px; background: var(--fw-surface-2); border: 1px solid var(--fw-line); }
    .pb .va-label { color: var(--fw-muted); font-size: 12.5px; }
    .pb .va-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin: 6px 0 8px; }
    .pb .va-number { font-size: 22px; font-weight: 700; letter-spacing: 1px; font-variant-numeric: tabular-nums; }
    .pb .copy-btn { height: 34px; padding: 0 14px; border: 1px solid var(--fw-line-2); border-radius: 99px; background: #fff; color: var(--fw-green); font-size: 12.5px; font-weight: 600; cursor: pointer; }
    .pb .proof-card { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--fw-line); }
    .pb .proof-drop { position: relative; display: grid; justify-items: center; gap: 4px; padding: 22px 16px; border: 2px dashed rgba(31, 77, 51, .3); border-radius: 18px; background: var(--fw-tint-2); text-align: center; cursor: pointer; font-size: 13.5px; }
    .pb .proof-drop.dragging { border-color: var(--fw-green); background: var(--fw-tint); }
    .pb .proof-drop small { color: var(--fw-muted); font-size: 12px; }
    .pb .proof-drop input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .pb .proof-drop img { max-width: 100%; max-height: 220px; margin-top: 8px; border-radius: 12px; }
    .pb .proof-icon { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-green); color: #fff; font-size: 18px; }
    .pb .proof-name { margin-top: 6px; color: var(--fw-muted); font-size: 12.5px; }
    .pb .real-note { margin-top: 10px; color: var(--fw-muted); font-size: 12.5px; }
    .pb .method-list { display: grid; gap: 10px; }
    .pb .method-option input { position: absolute; opacity: 0; pointer-events: none; }
    .pb .method-card { display: flex; align-items: center; gap: 14px; padding: 14px; border: 1.5px solid var(--fw-line); border-radius: 16px; background: var(--fw-surface); cursor: pointer; }
    .pb .method-option input:checked + .method-card { border-color: var(--fw-green); background: var(--fw-tint-2); }
    .pb .method-logo { width: 62px; height: 40px; flex: 0 0 62px; display: grid; place-items: center; border-radius: 10px; background: var(--fw-bg-2); color: var(--fw-text); font-size: 12px; font-weight: 800; letter-spacing: .3px; }
    .pb .method-logo.qris { background: #fde8ec; color: #c3162e; }
    .pb .method-logo.mandiri { background: #e6eef9; color: #0b3d91; text-transform: lowercase; }
    .pb .method-logo.bca { background: #e6eef9; color: #1a4fa0; }
    .pb .method-logo.cash { background: var(--fw-tint); color: var(--fw-green); }
    .pb .method-text { flex: 1; min-width: 0; }
    .pb .method-text strong { display: block; font-size: 14.5px; }
    .pb .method-text small { color: var(--fw-muted); font-size: 12.5px; }
    .pb .method-check { width: 22px; height: 22px; flex: 0 0 22px; border-radius: 50%; border: 2px solid var(--fw-line-2); }
    .pb .method-option input:checked + .method-card .method-check { border: 6px solid var(--fw-green); }
    @media (max-width: 900px) { .pb .layout { grid-template-columns: minmax(0, 1fr); } .pb .layout > .panel:last-child { order: -1; } }
    @media (max-width: 560px) { .pb .panel { padding: 16px; } }
</style>
@endpush

@section('content')
<div class="pb">
    <div class="fw-pagehead-title" style="margin-bottom:12px"><a href="{{ Route::has('jadwal') ? route('jadwal') : route('booking') }}" class="fw-back" aria-label="Kembali">{!! \App\Support\Icons::svg('back') !!}</a></div>


    <div class="steps">
        <span class="step done"><span class="step-number">✓</span> Pilih jadwal</span>
        <span class="step-line"></span>
        <span class="step {{ $payment->status === 'paid' ? 'done' : 'current' }}">
            <span class="step-number">{{ $payment->status === 'paid' ? '✓' : '2' }}</span> Pembayaran
        </span>
        <span class="step-line"></span>
        <span class="step {{ $booking->status === 'booked' ? 'done' : (in_array($payment->status, ['paid', 'verifying'], true) ? 'current' : '') }}">
            <span class="step-number">{{ $booking->status === 'booked' ? '✓' : '3' }}</span> Konfirmasi
        </span>
    </div>

    <div class="page-heading">
        <h1>Pembayaran Lesson</h1>
        <p>Selesaikan pembayaran untuk mengamankan jadwal lesson Anda.</p>
    </div>

    @if (\App\Support\BookingRules::isDemoPayment() && ! in_array($payment->status, ['paid', 'verifying', 'cash'], true))
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
            <div class="summary-row"><span>Jenis lesson</span><span>{{ $booking->lesson_label }}</span></div>
            <div class="summary-row"><span>Booking dibuat</span><span>{{ $booking->created_at?->locale('id')->translatedFormat('d M Y, H:i') }} WIB</span></div>
            @if ($place)
                <div class="summary-row"><span>Lapangan</span><span>{{ $place }}</span></div>
            @endif
            <div class="summary-row"><span>Tanggal</span><span>{{ $date->locale('id')->translatedFormat('l, d F Y') }}</span></div>
            <div class="summary-row"><span>Jam</span><span>{{ $startTime }} – {{ $endTime }}</span></div>
            <div class="summary-row"><span>Durasi</span><span>{{ $payment->duration_label }}</span></div>
            <div class="summary-row">
                <span>{{ $isCourse ? 'Harga paket' : 'Harga per jam' }}</span>
                <span>{{ $isCourse ? Payment::formatRupiah($payment->amount) . ' / sesi' : Payment::formatRupiah($rate) }}</span>
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
                @if ($isCourse)
                    1 sesi Course Lesson ({{ rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',') }} jam)
                    @if (\App\Support\BookingRules::courseNote())
                        <br><span style="color: #a2650c; font-weight: 600">* {{ \App\Support\BookingRules::courseNote() }}</span>
                    @endif
                @else
                    {{ rtrim(rtrim(number_format($hours, 2, ',', '.'), '0'), ',') }} jam × {{ Payment::formatRupiah($rate) }}
                @endif
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
                        @if ($payment->submitted_at)
                            <div class="summary-row"><span>Dikirim pada</span><span>{{ $payment->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</span></div>
                        @endif
                        <div class="summary-row"><span>Dibayar pada</span><span>{{ $payment->paid_at?->locale('id')->translatedFormat('d M Y, H:i') }} WIB</span></div>
                        @if ($payment->received_by)
                            <div class="summary-row"><span>Diterima oleh</span><span>{{ $payment->received_by }}</span></div>
                        @endif
                        <div class="summary-row"><span>Jumlah</span><span>{{ $payment->amount_label }}</span></div>
                        @if ($payment->hasProof())
                            <div class="summary-row">
                                <span>Bukti pembayaran</span>
                                <span><a href="{{ route('payment.booking.proof', $booking) }}" target="_blank" rel="noopener" class="proof-link" style="margin: 0">Lihat bukti</a></span>
                            </div>
                        @endif
                    </div>

                    <a href="{{ (Route::has('jadwal') ? route('jadwal') : route('booking', ['date' => $date->format('Y-m-d')])) }}" class="btn">Lihat Jadwal Saya</a>
                </div>

            @elseif ($payment->status === 'verifying')

                {{-- ---------- MENUNGGU VERIFIKASI ADMIN ---------- --}}
                <div class="verify-box">
                    <div class="verify-icon">◷</div>
                    <h2>Pembayaran sedang dicek</h2>
                    <p>
                        Anda sudah mengonfirmasi pembayaran {{ $payment->amount_label }} via {{ $payment->method_label }}
                        @if ($payment->submitted_at)
                            pada <strong>{{ $payment->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</strong>
                        @endif.
                        Admin akan mengecek dana yang masuk dan mengonfirmasi secepatnya.
                        Anda akan mendapat notifikasi setelah pembayaran dikonfirmasi.
                    </p>

                    @if ($payment->hasProof())
                        <a href="{{ route('payment.booking.proof', $booking) }}" target="_blank" rel="noopener" class="proof-thumb">
                            <img src="{{ route('payment.booking.proof', $booking) }}" alt="Bukti pembayaran yang Anda kirim">
                        </a>
                        <span class="proof-link">Bukti pembayaran terkirim {{ $payment->proof_uploaded_at?->locale('id')->diffForHumans() }}</span>
                    @endif

                    <a href="{{ (Route::has('jadwal') ? route('jadwal') : route('booking', ['date' => $date->format('Y-m-d')])) }}" class="btn">Lihat Jadwal Saya</a>
                </div>

            @elseif (! $bookingActive)

                {{-- ---------- BOOKING TIDAK AKTIF ---------- --}}
                <div class="blocked">
                    @if (($booking->getAttributes()['expired_at'] ?? null))
                        <strong>Booking gagal.</strong>
                        Booking ini dibatalkan otomatis karena belum dibayar dalam
                        {{ \App\Support\BookingRules::paymentDeadlineMinutes() }} menit setelah booking dibuat.
                        Jadwalnya sudah dibuka kembali, silakan booking ulang jika masih tersedia.
                    @else
                        Booking ini sudah <strong>{{ strtolower($bookingStatusLabel) }}</strong>, sehingga tidak perlu dibayar.
                    @endif
                    <a href="{{ route('booking') }}" class="btn">Buat booking baru</a>
                </div>

            @elseif ($payment->status === 'cash')

                {{-- ---------- BAYAR CASH ---------- --}}
                <div class="cash-box">
                    <div class="cash-icon">Rp</div>
                    <h2>Bayar cash saat lesson</h2>
                    <p>Anda memilih membayar tunai langsung ke admin.</p>

                    <div class="cash-amount">{{ $payment->amount_label }}</div>
                    <p>No. referensi {{ $payment->reference }}</p>

                    <ol class="cash-steps">
                        <li>Siapkan uang tunai <strong>{{ $payment->amount_label }}</strong> sesuai total di atas.</li>
                        <li>Bayarkan langsung ke admin saat datang untuk lesson.</li>
                        <li>Admin akan menandai pembayaran Anda lunas, dan Anda akan menerima notifikasi.</li>
                    </ol>

                    <a href="{{ (Route::has('jadwal') ? route('jadwal') : route('booking', ['date' => $date->format('Y-m-d')])) }}" class="btn">Lihat Jadwal Saya</a>

                    <form method="POST" action="{{ route('payment.booking.reset', $booking) }}" style="margin-top: 10px">
                        @csrf
                        <button type="submit" class="btn btn-ghost">Ganti ke QRIS / transfer</button>
                    </form>
                </div>

            @elseif ($payment->status === 'pending' && $activeMethod)

                {{-- ---------- INSTRUKSI PEMBAYARAN ---------- --}}
                <div class="panel-title">Bayar dengan {{ $payment->method_label }}</div>

                @if ($deadline && $awaiting)
                    <div class="countdown">
                        <span>Upload bukti & konfirmasi sebelum {{ $deadline->locale('id')->translatedFormat('d M Y, H:i') }} WIB</span>
                        <strong id="countdown" data-expires="{{ $deadline->toIso8601String() }}">--:--</strong>
                    </div>
                @endif

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
                                (wajib)
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
                            <input type="file" name="proof" id="proofInput" accept="image/jpeg,image/png,image/webp" required>
                        </label>
                        <div class="proof-name" id="proofName"></div>

                        @if ($isReal)
                            <div class="real-note">Admin akan mencocokkan bukti ini dengan dana yang masuk sebelum pembayaran dinyatakan lunas.</div>
                        @endif

                        <button type="submit" class="btn" id="confirmButton" disabled>Upload bukti dulu</button>
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

                @if ($deadline && $awaiting)
                    <div class="countdown">
                        <span>
                            Bayar dalam {{ \App\Support\BookingRules::paymentDeadlineMinutes() }} menit sejak booking dibuat,
                            sebelum {{ $deadline->locale('id')->translatedFormat('H:i') }} WIB. Jika lewat, booking gagal otomatis.
                        </span>
                        <strong id="countdown" data-expires="{{ $deadline->toIso8601String() }}">--:--</strong>
                    </div>
                @endif

                <form method="POST" action="{{ route('payment.booking.method', $booking) }}" id="methodForm">
                    @csrf

                    <div class="method-list">
                        @foreach ($methods as $key => $method)
                            <label class="method-option">
                                <input type="radio" name="method" value="{{ $key }}" @checked(old('method') === $key)>
                                <span class="method-card">
                                    <span class="method-logo {{ $key }}">
                                        {{ ['qris' => 'QRIS', 'mandiri' => 'mandiri', 'bca' => 'BCA', 'cash' => 'CASH'][$key] ?? strtoupper($key) }}
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


</div>
@endsection

@push('scripts')
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
                    countdown.textContent = 'Waktu habis';
                    clearInterval(timer);
                    // Muat ulang: server menandai booking gagal
                    setTimeout(() => window.location.reload(), 1500);
                    return;
                }

                const hours = Math.floor(left / 3600);
                countdown.textContent = (hours ? pad(hours) + ':' : '') + pad(Math.floor(left % 3600 / 60)) + ':' + pad(left % 60);
                countdown.closest('.countdown')?.classList.toggle('urgent', left <= 300);
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

                const confirmButton = document.getElementById('confirmButton');
                if (confirmButton) {
                    confirmButton.disabled = false;
                    confirmButton.textContent = confirmForm && confirmForm.dataset.real === '1'
                        ? 'Kirim bukti & konfirmasi pembayaran'
                        : 'Saya sudah bayar';
                }
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
@endpush
