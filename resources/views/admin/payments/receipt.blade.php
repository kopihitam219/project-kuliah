@php
    $booking  = $payment->booking;
    $customer = $booking?->user;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran {{ $payment->reference }}</title>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #f3f1ea;
            color: #18211c;
            font-family: Arial, Helvetica, sans-serif;
            padding: 30px 16px;
        }

        .receipt {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
            overflow: hidden;
        }

        .receipt-head {
            padding: 24px 28px;
            background: #1f4d33;
            color: #ffffff;
        }

        .brand { font-size: 18px; font-weight: 900; }
        .brand span { color: #cde8a3; }
        .receipt-head p { margin-top: 4px; color: rgba(255, 255, 255, .6); font-size: 12px; }

        .status {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 22px 28px;
            border-bottom: 1px dashed #d5dcd8;
        }

        .status-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #2e9e3a;
            color: #ffffff;
            font-size: 22px;
            font-weight: 900;
        }

        .status strong { display: block; font-size: 17px; }
        .status small { color: #66706b; font-size: 12px; }

        .amount { padding: 18px 28px; border-bottom: 1px dashed #d5dcd8; }
        .amount span { color: #66706b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .amount strong { display: block; margin-top: 4px; font-size: 30px; font-weight: 900; }

        .rows { padding: 18px 28px; }
        .row { display: flex; justify-content: space-between; gap: 16px; padding: 7px 0; font-size: 13px; }
        .row span:first-child { color: #66706b; }
        .row span:last-child { font-weight: 700; text-align: right; }

        .section { padding: 0 28px; color: #2e9e3a; font-size: 11px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; }

        .note {
            margin: 6px 28px 22px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #fff7e0;
            color: #8a6400;
            font-size: 11px;
            line-height: 1.5;
        }

        .actions { max-width: 560px; margin: 16px auto 0; display: flex; gap: 8px; justify-content: center; }

        .actions button,
        .actions a {
            height: 40px;
            padding: 0 18px;
            display: inline-flex;
            align-items: center;
            border: 0;
            border-radius: 8px;
            background: #1f4d33;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .actions a { background: #ffffff; color: #07130f; border: 1px solid #cfd6d2; }

        @media print {
            body { background: #ffffff; padding: 0; }
            .receipt { box-shadow: none; }
            .actions { display: none; }
        }
    </style>
    @include('partials.brand-head')
</head>
<body>

<div class="receipt">
    <div class="receipt-head">
        <div class="brand">Golf <span>Booking</span> Lesson</div>
        <p>Bukti Pembayaran Lesson</p>
    </div>

    <div class="status">
        <span class="status-icon">✓</span>
        <div>
            <strong>Pembayaran berhasil</strong>
            <small>{{ $payment->paid_at?->locale('id')->translatedFormat('l, d F Y · H:i') }} WIB</small>
        </div>
    </div>

    <div class="amount">
        <span>Total dibayar</span>
        <strong>{{ $payment->amount_label }}</strong>
    </div>

    <div class="rows">
        <div class="row"><span>No. referensi</span><span>{{ $payment->reference }}</span></div>
        <div class="row"><span>Metode</span><span>{{ $payment->method_label }}</span></div>
        @if ($payment->va_number)
            <div class="row"><span>No. Virtual Account</span><span>{{ trim(chunk_split($payment->va_number, 4, ' ')) }}</span></div>
        @endif
        <div class="row"><span>Status</span><span>{{ $payment->status_label }}</span></div>
    </div>

    <div class="section">Detail booking</div>

    <div class="rows">
        <div class="row"><span>Customer</span><span>{{ $customer->name ?? '-' }}</span></div>
        <div class="row"><span>Email</span><span>{{ $customer->email ?? '-' }}</span></div>
        @if ($booking)
            <div class="row"><span>Tanggal</span><span>{{ $booking->booking_date->locale('id')->translatedFormat('l, d F Y') }}</span></div>
            <div class="row"><span>Jam</span><span>{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</span></div>
        @endif
        <div class="row"><span>Durasi</span><span>{{ $payment->duration_label }}</span></div>
        @if ($booking)
            <div class="row"><span>Jenis lesson</span><span>{{ $booking->lesson_label }}</span></div>
            @if ($booking->place_label)
                <div class="row"><span>Lapangan</span><span>{{ $booking->place_label }}</span></div>
            @endif
            <div class="row"><span>Booking dibuat</span><span>{{ $booking->created_at?->locale('id')->translatedFormat('d M Y, H:i') }} WIB</span></div>
        @endif
        @if ($payment->submitted_at)
            <div class="row"><span>Dikirim customer</span><span>{{ $payment->submitted_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</span></div>
        @endif
        @if ($payment->received_by)
            <div class="row"><span>Diterima oleh</span><span>{{ $payment->received_by }}</span></div>
        @endif
        @if (method_exists($payment, 'hasProof') && $payment->hasProof())
            <div class="row" style="display: block">
                <span style="display: block; margin-bottom: 6px">Bukti transfer dari customer</span>
                <img src="{{ route('admin.payments.proof', $payment) }}" alt="Bukti transfer dari customer"
                     style="display: block; max-width: 100%; max-height: 420px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px">
            </div>
        @endif
    </div>

    @if (\App\Support\BookingRules::isDemoPayment())
    <div class="note">Mode demo: bukti ini berasal dari pembayaran simulasi dan bukan transaksi sungguhan.</div>
    @endif
</div>

<div class="actions">
    <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    <a href="{{ route('admin.dashboard') }}">Kembali ke dashboard</a>
</div>

</body>
</html>
