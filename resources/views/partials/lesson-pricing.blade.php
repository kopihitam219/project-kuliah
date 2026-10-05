{{--
    Harga & jenis lesson (halaman Program).
    Data dari Settings & menu Contact > Lokasi.
--}}
@php
    use App\Support\BookingRules;

    $lpRp        = fn ($n) => 'Rp' . number_format((int) $n, 0, ',', '.');
    $lpLocations = BookingRules::activeLocations();
    $lpBookUrl   = fn ($type) => auth()->check() && auth()->user()->role === 'customer'
        ? route('booking', ['type' => $type])
        : route('login');
@endphp

<style>
    .lp-section { width: 100%; max-width: 1180px; margin: 56px auto 0; padding: 0 24px; color: #fff; font-family: inherit; }
    .lp-head { text-align: center; margin-bottom: 26px; }
    .lp-kicker { color: #9cff38; font-size: 12px; font-weight: 900; letter-spacing: 4px; }
    .lp-head h2 { margin-top: 8px; font-size: clamp(28px, 4vw, 40px); font-weight: 900; letter-spacing: -1px; }
    .lp-head p { margin-top: 8px; color: rgba(255, 255, 255, .7); font-size: 15px; }
    .lp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    .lp-card {
        display: flex; flex-direction: column; gap: 14px; padding: 28px;
        border: 1px solid rgba(156, 255, 0, .2); border-radius: 22px;
        background: rgba(3, 18, 12, .88);
    }
    .lp-card.course { border-color: rgba(92, 168, 255, .35); }
    .lp-tag { align-self: flex-start; padding: 5px 12px; border-radius: 999px; background: rgba(156, 255, 0, .14); color: #9cff38; font-size: 11px; font-weight: 900; letter-spacing: 1.5px; }
    .lp-card.course .lp-tag { background: rgba(92, 168, 255, .16); color: #a9d0ff; }
    .lp-card h3 { font-size: 24px; font-weight: 900; }
    .lp-price { font-size: 34px; font-weight: 900; color: #9cff38; line-height: 1; }
    .lp-price small { color: rgba(255, 255, 255, .6); font-size: 14px; font-weight: 700; }
    .lp-list { display: grid; gap: 8px; margin: 4px 0 6px; padding: 0; list-style: none; }
    .lp-list li { display: flex; gap: 10px; color: rgba(255, 255, 255, .85); font-size: 14px; line-height: 1.45; }
    .lp-list li::before { content: "✓"; color: #9cff38; font-weight: 900; }
    .lp-note { color: #ffd45c; font-size: 13px; font-weight: 700; line-height: 1.45; }
    .lp-btn {
        margin-top: auto; display: inline-flex; justify-content: center; align-items: center;
        height: 48px; border-radius: 12px; background: #9cff38; color: #07120c;
        font-size: 14px; font-weight: 900; letter-spacing: .6px; text-decoration: none;
    }
    .lp-btn:hover { background: #c6ff7a; }
    .lp-card.course .lp-btn { background: transparent; border: 1px solid #9cff38; color: #9cff38; }
    .lp-card.course .lp-btn:hover { background: #9cff38; color: #07120c; }
</style>

<section class="lp-section" id="harga-lesson">
    <div class="lp-head">
        <div class="lp-kicker">HARGA LESSON</div>
        <h2>Pilih Jenis Lesson</h2>
        <p>Latihan di driving range dengan jam fleksibel, atau langsung praktik di lapangan golf.</p>
    </div>

    <div class="lp-grid">
        <article class="lp-card">
            <span class="lp-tag">DRIVING RANGE</span>
            <h3>Lesson Driving Range</h3>
            <div class="lp-price">{{ $lpRp(BookingRules::pricePerHour()) }} <small>/ jam</small></div>
            <ul class="lp-list">
                <li>Jam fleksibel {{ BookingRules::openTime() }} – {{ BookingRules::closeTime() }}, minimal {{ BookingRules::minMinutes() }} menit</li>
                @if ($lpLocations->isNotEmpty())
                    <li>Pilih lapangan: {{ $lpLocations->pluck('name')->join(', ', ' atau ') }}</li>
                @endif
                <li>Didampingi coach profesional</li>
            </ul>
            <a href="{{ $lpBookUrl('driving') }}" class="lp-btn">BOOKING LESSON</a>
        </article>

        @if (BookingRules::courseEnabled())
            <article class="lp-card course">
                <span class="lp-tag">ON COURSE</span>
                <h3>Course Lesson</h3>
                <div class="lp-price">{{ $lpRp(BookingRules::coursePrice()) }} <small>/ sesi</small></div>
                <ul class="lp-list">
                    <li>Sesi {{ BookingRules::courseStart() }} – {{ BookingRules::courseEnd() }} (pagi sampai siang)</li>
                    <li>Lapangan golf pilihan Anda sendiri</li>
                    <li>Praktik langsung di lapangan golf bersama coach</li>
                </ul>
                @if (BookingRules::courseNote())
                    <p class="lp-note">* {{ BookingRules::courseNote() }}</p>
                @endif
                <a href="{{ $lpBookUrl('course') }}" class="lp-btn">BOOKING COURSE LESSON</a>
            </article>
        @endif
    </div>
</section>
