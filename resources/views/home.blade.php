@extends('layouts.fw')

@section('main_class', 'fw-home-main')

@php
    use App\Support\Icons;
    use Illuminate\Support\Str;

    $hero  = \App\Support\Brand::hero();
    $nice  = fn ($s) => $s === mb_strtoupper($s) ? Str::title(mb_strtolower($s)) : $s;
    $photo = \App\Support\Brand::background('public');

    $programs  = class_exists(\App\Models\Program::class) ? \App\Models\Program::active()->ordered()->take(3)->get() : collect();
    $photos    = class_exists(\App\Models\Gallery::class) ? \App\Models\Gallery::active()->ordered()->take(6)->get() : collect();
    $events    = class_exists(\App\Models\Event::class) ? \App\Models\Event::active()->upcoming()->chronological()->take(2)->get() : collect();
    $locations = collect(\App\Support\BookingRules::activeLocations());
    $coach     = \App\Models\Coach::main();
    $price     = \App\Models\Payment::pricePerHour();

    $ctaUrl   = auth()->check() ? (auth()->user()->role === 'admin' ? route('admin.dashboard') : route('booking')) : route('login');
    $ctaLabel = auth()->check() && auth()->user()->role === 'admin' ? 'Buka Panel Admin' : 'Mulai Sekarang';
@endphp

@push('head')
<style>
    .fw-home-main { width: 100% !important; padding: 0 !important; }
    .hm-wrap { width: min(1180px, 100% - 48px); margin: 0 auto; }

    /* HERO */
    .hm-hero { position: relative; padding: 46px 0 64px; overflow: hidden; }
    .hm-hero-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 48px; align-items: center; }
    .hm-label { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 99px; background: var(--fw-surface); border: 1px solid var(--fw-line); color: var(--fw-green); font-size: 12px; font-weight: 600; }
    .hm-label i { width: 7px; height: 7px; border-radius: 50%; background: var(--fw-green-3); }
    .hm-title { margin-top: 18px; font-family: var(--fw-serif); font-weight: 600; font-size: clamp(38px, 5.2vw, 64px); line-height: 1.06; letter-spacing: -1px; color: var(--fw-text); }
    .hm-title em { font-style: italic; color: var(--fw-green-3); }
    .hm-text { margin-top: 18px; max-width: 46ch; color: var(--fw-text-2); font-size: 16px; line-height: 1.7; }
    .hm-cta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
    .hm-trust { display: flex; flex-wrap: wrap; gap: 26px; margin-top: 34px; }
    .hm-trust div strong { display: block; font-family: var(--fw-serif); font-size: 28px; font-weight: 600; color: var(--fw-green); line-height: 1; }
    .hm-trust div span { color: var(--fw-muted); font-size: 12.5px; }

    .hm-visual { position: relative; }
    .hm-photo { position: relative; aspect-ratio: 4 / 4.4; border-radius: 32px; overflow: hidden; background: #cfd8c9 center / cover no-repeat; box-shadow: var(--fw-shadow-lg); filter: brightness(1.3) saturate(1.1); }
    .hm-photo::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(255, 252, 240, .12), rgba(23, 46, 33, .25)); }
    .hm-float { position: absolute; z-index: 2; display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 18px; background: rgba(255, 255, 255, .95); box-shadow: var(--fw-shadow-lg); }
    .hm-float.a { left: -26px; bottom: 54px; }
    .hm-float.b { right: -18px; top: 34px; }
    .hm-float .ic { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; background: var(--fw-tint); color: var(--fw-green); }
    .hm-float .ic svg { width: 20px; height: 20px; }
    .hm-float strong { display: block; font-size: 14px; }
    .hm-float span { color: var(--fw-muted); font-size: 12px; }

    /* Section umum */
    .hm-sec { padding: 64px 0; }
    .hm-sec.alt { background: var(--fw-surface); border-top: 1px solid var(--fw-line); border-bottom: 1px solid var(--fw-line); }
    .hm-sec-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 26px; }
    .hm-sec-head p { max-width: 52ch; margin-top: 8px; color: var(--fw-muted); font-size: 15px; line-height: 1.65; }

    /* Fitur */
    .hm-feat { padding: 22px; border-radius: 20px; background: var(--fw-surface-2); border: 1px solid var(--fw-line); }
    .hm-sec.alt .hm-feat { background: var(--fw-bg); }
    .hm-feat .ic { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 14px; background: var(--fw-green); color: #fff; }
    .hm-feat .ic svg { width: 22px; height: 22px; }
    .hm-feat h3 { margin-top: 14px; font-size: 16px; font-weight: 700; }
    .hm-feat p { margin-top: 6px; color: var(--fw-muted); font-size: 14px; line-height: 1.6; }

    /* Program */
    .hm-prog { display: flex; flex-direction: column; overflow: hidden; }
    .hm-prog-img { aspect-ratio: 16 / 10; background: #cfd8c9 center / cover no-repeat; }
    .hm-prog-body { padding: 18px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .hm-prog-body h3 { font-family: var(--fw-serif); font-size: 21px; font-weight: 600; }
    .hm-prog-body p { color: var(--fw-muted); font-size: 14px; line-height: 1.6; }
    .hm-prog-meta { display: flex; flex-wrap: wrap; gap: 14px; margin-top: auto; padding-top: 6px; color: var(--fw-muted); font-size: 12.5px; }
    .hm-prog-meta span { display: inline-flex; align-items: center; gap: 5px; }
    .hm-prog-meta svg { width: 15px; height: 15px; }

    /* Galeri */
    .hm-gal { display: grid; grid-template-columns: repeat(3, 1fr); grid-auto-rows: 200px; gap: 12px; }
    .hm-gal a { position: relative; border-radius: 18px; overflow: hidden; background: #cfd8c9 center / cover no-repeat; }
    .hm-gal a:first-child { grid-row: span 2; }
    .hm-gal .play { position: absolute; inset: 0; display: grid; place-items: center; }
    .hm-gal .play span { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 50%; background: rgba(255, 255, 255, .92); color: var(--fw-green); }
    .hm-gal .play svg { width: 20px; height: 20px; margin-left: 2px; }

    /* Event */
    .hm-ev { display: flex; gap: 16px; padding: 14px; }
    .hm-ev-img { width: 120px; flex: 0 0 120px; aspect-ratio: 4 / 5; border-radius: 14px; background: #cfd8c9 center / cover no-repeat; }
    .hm-ev h3 { font-size: 17px; font-weight: 700; }
    .hm-ev p { margin-top: 6px; color: var(--fw-muted); font-size: 13.5px; display: flex; align-items: center; gap: 6px; }
    .hm-ev p svg { width: 15px; height: 15px; }

    /* CTA */
    .hm-band { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 40px 44px; border-radius: 28px; background: var(--fw-green); color: #fff; }
    .hm-band::before { content: ""; position: absolute; right: -60px; bottom: -120px; width: 380px; height: 380px; border-radius: 50%; background: rgba(205, 232, 163, .12); }
    .hm-band h2 { position: relative; font-family: var(--fw-serif); font-size: clamp(26px, 3vw, 36px); font-weight: 600; line-height: 1.15; }
    .hm-band p { position: relative; margin-top: 8px; color: rgba(255, 255, 255, .75); }
    .hm-band .fw-btn { position: relative; }

    @media (max-width: 960px) {
        .hm-hero-grid { grid-template-columns: 1fr; gap: 30px; }
        .hm-float.a { left: 12px; } .hm-float.b { right: 12px; }
    }
    @media (max-width: 820px) {
        .hm-wrap { width: calc(100% - 32px); }
        /* Hero HP seperti splash screen: teks di atas, foto lapangan di bawah */
        .hm-hero { padding: 0 0 46vh; min-height: calc(100svh - 62px - 78px); }
        .hm-hero::before { content: ""; position: absolute; inset: 0; background: var(--hm-photo) center bottom / cover no-repeat; filter: brightness(1.45) saturate(1.15); }
        .hm-hero::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, #f5f2e8 0%, #f5f2e8 46%, rgba(245, 242, 232, .7) 60%, rgba(245, 242, 232, 0) 82%); pointer-events: none; }
        .hm-trust { display: none; }
        .hm-hero-grid { position: relative; z-index: 1; padding-top: 34px; }
        .hm-visual { display: none; }
        .hm-title { font-size: 36px; }
        .hm-text { font-size: 14.5px; }
        .hm-trust { gap: 20px; margin-top: 26px; }
        .hm-trust div strong { font-size: 24px; }
        .hm-sec { padding: 40px 0; }
        .hm-sec-head { flex-direction: column; align-items: flex-start; }
        .hm-gal { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 130px; gap: 10px; }
        .hm-gal a:first-child { grid-row: span 1; }
        .hm-band { flex-direction: column; align-items: flex-start; padding: 28px 24px; }
        .hm-ev-img { width: 92px; flex-basis: 92px; }
    }
</style>
@endpush

@section('content')
    {{-- ================= HERO ================= --}}
    <section class="hm-hero" style="--hm-photo: url('{{ $photo }}')">
        <div class="hm-wrap hm-hero-grid">
            <div>
                <span class="hm-label"><i></i>{{ $nice($hero['hero_label']) }}</span>
                <h1 class="hm-title">{{ $nice($hero['hero_title_1']) }} {{ $nice($hero['hero_title_2']) }} <em>{{ $nice($hero['hero_highlight']) }}</em></h1>
                <p class="hm-text">{{ $hero['hero_text'] }}</p>
                <div class="hm-cta">
                    <a href="{{ $ctaUrl }}" class="fw-btn">{{ $ctaLabel }} {!! Icons::svg('arrow') !!}</a>
                    <a href="{{ route('program') }}" class="fw-btn ghost">Lihat Program</a>
                </div>
                <div class="hm-trust">
                    @if ($coach?->years_experience)<div><strong>{{ $coach->years_experience }}+</strong><span>Tahun pengalaman coach</span></div>@endif
                    @if ($locations->isNotEmpty())<div><strong>{{ $locations->count() }}</strong><span>Lokasi latihan</span></div>@endif
                    @if ($programs->isNotEmpty())<div><strong>{{ \App\Models\Program::active()->count() }}</strong><span>Program latihan</span></div>@endif
                </div>
            </div>

            <div class="hm-visual">
                <div class="hm-photo" style="background-image:url('{{ $photo }}')"></div>
                <div class="hm-float b">
                    <span class="ic">{!! Icons::svg('calendar') !!}</span>
                    <div><strong>Booking online</strong><span>Pilih jam, bayar, selesai</span></div>
                </div>
                @if ($coach)
                    <div class="hm-float a">
                        @if ($coach->photo_url)
                            <span class="fw-av" style="background-image:url('{{ $coach->photo_url }}')"></span>
                        @else
                            <span class="fw-av">{{ Icons::initials($coach->name) }}</span>
                        @endif
                        <div><strong>{{ $coach->name }}</strong><span>{{ $coach->role ?: 'Coach profesional' }}</span></div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ================= KEUNGGULAN ================= --}}
    <section class="hm-sec alt">
        <div class="hm-wrap">
            <div class="hm-sec-head">
                <div>
                    <span class="fw-eyebrow">Kenapa kami</span>
                    <h2 class="fw-h2" style="margin-top:6px">Belajar golf jadi lebih mudah</h2>
                </div>
            </div>
            <div class="fw-grid c3">
                <div class="hm-feat"><span class="ic">{!! Icons::svg('calendar') !!}</span><h3>Atur jadwal sendiri</h3><p>Lihat jam kosong secara langsung dan booking lesson per jam dari HP.</p></div>
                <div class="hm-feat"><span class="ic">{!! Icons::svg('coach') !!}</span><h3>Coach berpengalaman</h3><p>Dilatih langsung oleh coach profesional dengan metode yang terstruktur.</p></div>
                <div class="hm-feat"><span class="ic">{!! Icons::svg('pin') !!}</span><h3>Pilih lokasi latihan</h3><p>Driving range atau course lesson di lapangan golf pilihan Anda.</p></div>
            </div>
        </div>
    </section>

    {{-- ================= PROGRAM ================= --}}
    @if ($programs->isNotEmpty())
        <section class="hm-sec">
            <div class="hm-wrap">
                <div class="hm-sec-head">
                    <div>
                        <span class="fw-eyebrow">Program</span>
                        <h2 class="fw-h2" style="margin-top:6px">Pilih program sesuai level Anda</h2>
                    </div>
                    <a href="{{ route('program') }}" class="fw-link">Semua program →</a>
                </div>
                <div class="fw-grid c3">
                    @foreach ($programs as $p)
                        <a href="{{ route('program') }}#program-{{ $p->id }}" class="fw-card hm-prog">
                            <div class="hm-prog-img" style="background-image:url('{{ $p->image_url }}');background-position:{{ $p->image_css_position ?? 'center' }}"></div>
                            <div class="hm-prog-body">
                                <span class="fw-badge green" style="align-self:flex-start">{{ ucfirst($p->level) }}</span>
                                <h3>{{ $p->name }}</h3>
                                <p>{{ Str::limit($p->description, 110) }}</p>
                                <div class="hm-prog-meta">
                                    <span>{!! Icons::svg('clock') !!} 60 menit</span>
                                    <span>{!! Icons::svg('user') !!} Private</span>
                                    <span>{!! Icons::svg('tag') !!} {{ \App\Models\Payment::formatRupiah($price) }}/jam</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= ABOUT COACH ================= --}}
    @include('partials.home-coaches')

    {{-- ================= GALERI ================= --}}
    @if ($photos->isNotEmpty())
        <section class="hm-sec">
            <div class="hm-wrap">
                <div class="hm-sec-head">
                    <div>
                        <span class="fw-eyebrow">Galeri</span>
                        <h2 class="fw-h2" style="margin-top:6px">Suasana latihan kami</h2>
                    </div>
                    <a href="{{ route('galeri') }}" class="fw-link">Lihat galeri →</a>
                </div>
                <div class="hm-gal">
                    @foreach ($photos as $g)
                        <a href="{{ route('galeri') }}" style="background-image:url('{{ $g->thumbnail_url ?? $g->image_url }}')" aria-label="{{ $g->title }}">
                            @if ($g->type === 'video')<span class="play"><span>{!! Icons::svg('play') !!}</span></span>@endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= EVENT ================= --}}
    @if ($events->isNotEmpty())
        <section class="hm-sec alt">
            <div class="hm-wrap">
                <div class="hm-sec-head">
                    <div>
                        <span class="fw-eyebrow">Event</span>
                        <h2 class="fw-h2" style="margin-top:6px">Event mendatang</h2>
                    </div>
                    <a href="{{ route('event') }}" class="fw-link">Semua event →</a>
                </div>
                <div class="fw-grid c2">
                    @foreach ($events as $ev)
                        <a href="{{ route('event') }}" class="fw-card hm-ev">
                            <div class="hm-ev-img" style="background-image:url('{{ $ev->poster_url }}')"></div>
                            <div>
                                <span class="fw-badge orange-soft">{{ $ev->status_label }}</span>
                                <h3 style="margin-top:8px">{{ $ev->title }}</h3>
                                <p>{!! Icons::svg('calendar') !!} {{ $ev->date_label }}</p>
                                @if ($ev->time_range)<p>{!! Icons::svg('clock') !!} {{ $ev->time_range }}</p>@endif
                                @if ($ev->location)<p>{!! Icons::svg('pin') !!} {{ $ev->location }}</p>@endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= CTA ================= --}}
    <section class="hm-sec">
        <div class="hm-wrap">
            <div class="hm-band">
                <div>
                    <h2>Latihan hari ini,<br>prestasi esok hari.</h2>
                    <p>Booking lesson pertama Anda sekarang, hanya butuh 1 menit.</p>
                </div>
                <a href="{{ $ctaUrl }}" class="fw-btn lime">{{ $ctaLabel }} {!! Icons::svg('arrow') !!}</a>
            </div>
        </div>
    </section>
@endsection
