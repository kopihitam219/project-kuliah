{{-- Pembayaran event (demo). --}}
@extends('layouts.fw')

@section('title', 'Pembayaran Event')
@section('no_footer', true)

@php
    use App\Support\Icons;

    $event = request('event') ? \App\Models\Event::find(request('event')) : \App\Models\Event::active()->upcoming()->chronological()->first();
@endphp

@push('head')
<style>
    .ep { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 18px; align-items: start; }
    .ep-poster { aspect-ratio: 4 / 5; border-radius: 18px 18px 0 0; background: var(--fw-bg-2) center / contain no-repeat; }
    .ep-sum { padding: 18px 20px 20px; display: grid; gap: 10px; }
    .ep-sum h2 { font-family: var(--fw-serif); font-size: 24px; font-weight: 600; }
    .ep-row { display: flex; gap: 10px; align-items: center; color: var(--fw-text-2); font-size: 14px; }
    .ep-row svg { width: 17px; height: 17px; color: var(--fw-green-3); }
    .ep-total { display: flex; justify-content: space-between; align-items: baseline; margin-top: 6px; padding: 14px 16px; border-radius: 16px; background: var(--fw-green); color: #fff; }
    .ep-total strong { font-family: var(--fw-serif); font-size: 26px; font-weight: 600; }
    .ep-pay { padding: 22px; }
    .ep-pay h2 { font-family: var(--fw-serif); font-size: 22px; font-weight: 600; }
    .ep-qr { display: grid; justify-items: center; gap: 8px; margin: 16px 0; padding: 18px; border-radius: 18px; background: var(--fw-surface-2); border: 1px solid var(--fw-line); }
    .ep-qr svg { width: 200px; height: 200px; padding: 8px; border-radius: 14px; background: var(--d-surface, #fff); }
    .ep-method { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px; border: 1px solid var(--fw-line); background: var(--fw-surface); }
    .ep-method b { width: 62px; height: 38px; flex: 0 0 62px; display: grid; place-items: center; border-radius: 10px; background: var(--fw-blue-tint); color: var(--fw-blue); font-size: 11.5px; }
    .ep-method span { flex: 1; font-size: 14px; font-weight: 500; }
    .ep-method code { font-size: 14px; font-weight: 700; font-family: inherit; letter-spacing: .5px; }
    .ep-toast { position: fixed; left: 50%; bottom: 100px; z-index: 400; transform: translate(-50%, 20px); padding: 12px 18px; border-radius: 14px; background: var(--fw-green); color: #fff; font-size: 14px; opacity: 0; pointer-events: none; transition: .25s; max-width: calc(100% - 32px); }
    .ep-toast.show { opacity: 1; transform: translate(-50%, 0); }
    @media (max-width: 900px) { .ep { grid-template-columns: minmax(0, 1fr); } .ep-poster { aspect-ratio: 16 / 12; } }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ route('event') }}" class="fw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">Pembayaran Event</h1>
                <p class="fw-sub">Selesaikan pembayaran untuk mengamankan tempat Anda.</p>
            </div>
        </div>
    </div>

    <div class="fw-alert warn">{!! Icons::svg('info') !!} <span><b>Mode demo:</b> QR & nomor rekening di bawah hanya contoh, jangan transfer uang sungguhan.</span></div>

    <div class="ep">
        <section class="fw-card">
            <div class="ep-poster" style="background-image:url('{{ $event?->poster_url ?? asset('images/event-poster-1.png') }}')"></div>
            <div class="ep-sum">
                <span class="fw-eyebrow">Ringkasan pesanan</span>
                <h2>{{ $event?->title ?? 'Golf Coaching Clinic' }}</h2>
                <div class="ep-row">{!! Icons::svg('calendar') !!} {{ $event?->date_label ?? '17 Oktober 2026' }}</div>
                <div class="ep-row">{!! Icons::svg('clock') !!} {{ $event?->time_range ?? '09:00 – 12:00' }}</div>
                <div class="ep-row">{!! Icons::svg('pin') !!} {{ $event?->location ?? 'Padang Golf Modernland, Tangerang' }}</div>
                <div class="ep-row">{!! Icons::svg('user') !!} 1 {{ $event?->price_unit ?? 'orang' }}</div>
                <div class="ep-total"><span>Total</span><strong>{{ $event?->price_label ?? 'Rp500.000' }}</strong></div>
            </div>
        </section>

        <section class="fw-card ep-pay">
            <h2>Pilih metode pembayaran</h2>
            <p class="fw-sub">Scan QRIS atau transfer ke salah satu rekening.</p>

            <div class="ep-qr">
                <svg viewBox="0 0 210 210" aria-label="QR code contoh">
                    <rect width="210" height="210" fill="#fff"/>
                    @foreach ([[10, 10], [145, 10], [10, 145]] as [$fx, $fy])
                        <rect x="{{ $fx }}" y="{{ $fy }}" width="55" height="55" fill="#17261d"/>
                        <rect x="{{ $fx + 8 }}" y="{{ $fy + 8 }}" width="39" height="39" fill="#fff"/>
                        <rect x="{{ $fx + 17 }}" y="{{ $fy + 17 }}" width="21" height="21" fill="#17261d"/>
                    @endforeach
                    <path fill="#17261d" d="M75 10h10v10H75zM95 10h10v10H95zM115 10h10v10H115zM75 30h10v10H75zM105 30h20v10H105zM75 50h20v10H75zM110 50h10v10H110zM75 75h10v10H75zM95 75h20v10H95zM125 75h10v10H125zM145 75h20v10H145zM175 75h20v10H175zM75 95h20v10H75zM105 95h10v10H105zM125 95h20v10H125zM155 95h10v10H155zM175 95h20v10H175zM75 115h10v10H75zM95 115h20v10H95zM125 115h10v10H125zM145 115h20v10H145zM175 115h10v10H175zM75 135h20v10H75zM105 135h20v10H105zM135 135h10v10H135zM155 135h20v10H155zM75 155h10v10H75zM95 155h20v10H95zM125 155h20v10H125zM155 155h10v10H155zM175 155h20v10H175zM75 175h20v10H75zM105 175h10v10H105zM125 175h20v10H125zM155 175h20v10H155zM185 175h10v10H185z"/>
                </svg>
                <span class="fw-badge orange-soft">QR contoh – belum aktif</span>
            </div>

            <div class="fw-list">
                <div class="ep-method"><b>BCA</b><span>BCA Virtual Account</span><code>1234567890</code></div>
                <div class="ep-method"><b>MANDIRI</b><span>Mandiri Virtual Account</span><code>880012345678</code></div>
                <div class="ep-method"><b>DANA</b><span>DANA</span><code>0812-0000-1234</code></div>
            </div>

            <button type="button" class="fw-btn block" id="paidButton" style="margin-top:18px">{!! Icons::svg('check') !!} Saya sudah bayar</button>
            <a href="{{ route('event') }}" class="fw-btn block ghost" style="margin-top:10px">Kembali ke Event</a>
        </section>
    </div>

    <div class="ep-toast" id="toast"></div>
@endsection

@push('scripts')
<script>
    document.getElementById('paidButton')?.addEventListener('click', function () {
        var t = document.getElementById('toast');
        t.textContent = 'Pembayaran dicatat sebagai DEMO. Sistem pembayaran event asli belum aktif.';
        t.classList.add('show'); setTimeout(function () { t.classList.remove('show'); }, 4000);
    });
</script>
@endpush
