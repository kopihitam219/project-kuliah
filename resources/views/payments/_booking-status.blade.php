{{--
    Status pembayaran di kartu "Booking Anda" (halaman booking customer).
    Variabel: $booking
--}}
@php
    // Kolom "source" kadang tidak ikut di-select oleh controller, ambil langsung bila perlu
    $cardSource  = $booking->getAttributes()['source']
        ?? \App\Models\Booking::whereKey($booking->id)->value('source');
    $cardOffline = $cardSource === 'offline';

    $cardPayment = $cardOffline ? null : \App\Models\Payment::where('booking_id', $booking->id)->first();
    $cardPaid    = $cardOffline || ($cardPayment && $cardPayment->status === 'paid');
@endphp

@once
    <style>
        .pay-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
        }

        .pay-status.unpaid { background: rgba(255, 196, 0, .07); border: 1px solid rgba(255, 196, 0, .25); color: #ffd45c; }
        .pay-status.paid   { background: rgba(184, 255, 0, .06); border: 1px solid rgba(184, 255, 0, .22); color: #b8ff00; }

        .pay-status a {
            flex-shrink: 0;
            padding: 7px 12px;
            border-radius: 7px;
            background: #b8ff00;
            color: #071000;
            font-size: 11px;
            font-weight: 800;
        }

        .pay-status.paid a { background: transparent; border: 1px solid rgba(184, 255, 0, .35); color: #b8ff00; }
    </style>
@endonce

<div class="pay-status {{ $cardPaid ? 'paid' : 'unpaid' }}">
    <span>
        @if ($cardOffline)
            ✓ Lunas · Dibayar di tempat
        @elseif ($cardPaid)
            ✓ Lunas · {{ $cardPayment->amount_label }}
        @elseif ($cardPayment && $cardPayment->status === 'verifying')
            ◷ Menunggu verifikasi admin · {{ $cardPayment->amount_label }}
        @elseif ($cardPayment && $cardPayment->status === 'pending')
            ◷ Menunggu pembayaran · {{ $cardPayment->amount_label }}
        @else
            ! Belum dibayar
        @endif
    </span>

    @unless ($cardOffline)
        <a href="{{ route('payment.booking', $booking) }}">{{ $cardPaid ? 'Lihat bukti' : ($cardPayment && $cardPayment->status === 'verifying' ? 'Lihat status' : 'Bayar sekarang') }}</a>
    @endunless
</div>
