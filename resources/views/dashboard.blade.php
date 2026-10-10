{{-- Beranda customer (setelah login). --}}
@extends('layouts.fw')

@section('title', 'Beranda')
@section('no_footer', true)

@php
    use App\Support\Icons;
    use Carbon\Carbon;

    $user  = auth()->user();
    $today = now()->toDateString();
    $mine  = \App\Models\Booking::where('user_id', $user->id);

    $next = (clone $mine)->whereIn('status', ['pending', 'booked'])
        ->whereDate('booking_date', '>=', $today)
        ->orderBy('booking_date')->orderBy('start_time')->first();

    $stats = [
        ['Total Booking', (clone $mine)->count(), 'calendar'],
        ['Lesson Selesai', (clone $mine)->where('status', 'booked')->whereDate('booking_date', '<', $today)->count(), 'check-c'],
        ['Akan Datang', (clone $mine)->whereIn('status', ['pending', 'booked'])->whereDate('booking_date', '>=', $today)->count(), 'clock'],
        ['Coach Tersedia', max(1, \App\Models\Coach::active()->count()), 'coach'],
    ];

    $pendingIds = (clone $mine)->where('status', 'pending')->whereDate('booking_date', '>=', $today)->pluck('id');
    $paidIds    = $pendingIds->isEmpty() ? collect() : \App\Models\Payment::whereIn('booking_id', $pendingIds)->where('status', 'paid')->pluck('booking_id');
    $unpaid     = $pendingIds->diff($paidIds)->count();

    $coach   = \App\Models\Coach::main();
    $photo   = \App\Support\Brand::background('public');
    $hour    = (int) now()->format('H');
    $greet   = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
    $chatNew = class_exists(\App\Models\ChatMessage::class) ? \App\Models\ChatMessage::unreadForMember($user->id) : 0;
    $events  = \App\Models\Event::active()->upcoming()->chronological()->take(1)->get();
    $programs = \App\Models\Program::active()->ordered()->take(4)->get();
@endphp

@push('head')
<style>
    .bd-hello { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .bd-hello small { color: var(--fw-muted); font-size: 14px; }
    .bd-hello h1 { margin-top: 2px; font-family: var(--fw-serif); font-size: clamp(26px, 3.4vw, 36px); font-weight: 600; letter-spacing: -.4px; }
    .bd-hello p { margin-top: 4px; color: var(--fw-muted); font-size: 14px; }

    .bd-top { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 18px; margin-top: 22px; }
    .bd-hero { position: relative; min-height: 200px; padding: 22px; border-radius: 24px; overflow: hidden; color: #fff; background: linear-gradient(100deg, rgba(23, 56, 37, .97) 0%, rgba(var(--d-green-rgb, 31, 77, 51), .9) 45%, rgba(var(--d-green-rgb, 31, 77, 51), .25) 100%), var(--bd-photo) center / cover no-repeat; box-shadow: var(--fw-shadow-lg); display: flex; flex-direction: column; justify-content: space-between; }
    .bd-hero .tag { align-self: flex-start; padding: 5px 11px; border-radius: 99px; background: rgba(var(--d-glass-rgb, 255, 255, 255), .14); font-size: 11.5px; font-weight: 600; }
    .bd-hero h2 { margin-top: 12px; font-family: var(--fw-serif); font-size: clamp(24px, 3vw, 30px); font-weight: 600; line-height: 1.15; max-width: 14ch; }
    .bd-hero .info { margin-top: 10px; display: grid; gap: 4px; font-size: 13.5px; color: rgba(255, 255, 255, .85); }
    .bd-hero .info span { display: inline-flex; align-items: center; gap: 7px; }
    .bd-hero .info svg { width: 15px; height: 15px; }
    .bd-hero .fw-btn { align-self: flex-start; margin-top: 16px; }

    .bd-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }

    .bd-quick { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; }
    .bd-quick a { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 14px 6px; border-radius: 18px; background: var(--fw-surface); border: 1px solid var(--fw-line); box-shadow: var(--fw-shadow); color: var(--fw-text-2); font-size: 12.5px; font-weight: 500; text-align: center; }
    .bd-quick a:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .35); color: var(--d-ink-green, var(--fw-green)); }
    .bd-quick .ic { position: relative; width: 46px; height: 46px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); }
    .bd-quick .ic svg { width: 21px; height: 21px; }
    .bd-quick .ic b { position: absolute; top: -3px; right: -3px; min-width: 18px; height: 18px; padding: 0 4px; display: grid; place-items: center; border-radius: 99px; background: var(--fw-red); color: #fff; font-size: 10px; box-shadow: 0 0 0 2px var(--d-surface, #fff); }

    .bd-cols { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 18px; }
    .bd-coach { display: flex; gap: 16px; align-items: center; padding: 18px; }
    .bd-coach .fw-av { width: 72px; height: 72px; flex-basis: 72px; border-radius: 20px; font-size: 24px; }
    .bd-coach strong { display: block; font-size: 16px; }
    .bd-coach small { color: var(--fw-muted); font-size: 13px; }
    .bd-coach p { margin-top: 6px; color: var(--fw-text-2); font-size: 13.5px; line-height: 1.55; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .bd-prog { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .bd-prog a { position: relative; height: 120px; border-radius: 18px; overflow: hidden; background: var(--d-bg-2, #cfd8c9) center / cover no-repeat; }
    .bd-prog a::after { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, transparent 30%, rgba(var(--d-ink-rgb, 23, 46, 33), .82)); }
    .bd-prog span { position: absolute; z-index: 1; left: 12px; right: 12px; bottom: 10px; color: #fff; font-size: 14px; font-weight: 600; }
    .bd-prog small { display: block; opacity: .8; font-size: 11px; font-weight: 500; }

    @media (max-width: 960px) { .bd-top, .bd-cols { grid-template-columns: minmax(0, 1fr); } }
    @media (max-width: 820px) {
        .bd-hello h1 { font-size: 24px; }
        .bd-hello .fw-av { width: 52px; height: 52px; flex-basis: 52px; }
        .bd-top { margin-top: 16px; gap: 12px; }
        .bd-hero { min-height: 186px; padding: 18px; border-radius: 22px; }
        .bd-quick { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
        .bd-quick a { padding: 10px 2px; border: 0; box-shadow: none; background: transparent; font-size: 11.5px; }
        .bd-quick a:nth-child(n+5) { display: none; }
    }
</style>
@endpush

@section('content')
    <div class="bd-hello">
        <div>
            <small>Halo,</small>
            <h1>{{ $user->name }}</h1>
            <p>{{ $greet }}! Saatnya tingkatkan permainan golf Anda.</p>
        </div>
        <a href="{{ Route::has('menu') ? route('menu') : route('profile.edit') }}" class="fw-av lg" aria-label="Menu akun">{{ Icons::initials($user->name) }}</a>
    </div>

    @if (session('status') || session('success'))
        <div class="fw-alert ok" style="margin-top:16px">{{ session('status') ?? session('success') }}</div>
    @endif

    <div class="bd-top">
        @if ($next)
            @php
                $nDate = Carbon::parse($next->booking_date);
                $isToday = $nDate->isToday();
            @endphp
            <div class="bd-hero" style="--bd-photo:url('{{ $photo }}')">
                <div>
                    <span class="tag">{{ $isToday ? 'Lesson Hari Ini' : 'Lesson Berikutnya' }}</span>
                    <h2>{{ $next->lesson_label }}</h2>
                    <div class="info">
                        <span>{!! Icons::svg('calendar') !!} {{ $nDate->locale('id')->translatedFormat('l, d M Y') }}</span>
                        <span>{!! Icons::svg('clock') !!} {{ substr($next->start_time, 0, 5) }} – {{ substr($next->end_time, 0, 5) }}</span>
                        @if ($next->place_label)<span>{!! Icons::svg('pin') !!} {{ $next->place_label }}</span>@endif
                    </div>
                </div>
                @if ($next->status === 'pending' && Route::has('payment.booking'))
                    <a href="{{ route('payment.booking', $next) }}" class="fw-btn lime md">Selesaikan Pembayaran {!! Icons::svg('arrow') !!}</a>
                @else
                    <a href="{{ Route::has('jadwal') ? route('jadwal') : route('booking') }}" class="fw-btn lime md">Lihat Jadwal {!! Icons::svg('arrow') !!}</a>
                @endif
            </div>
        @else
            <div class="bd-hero" style="--bd-photo:url('{{ $photo }}')">
                <div>
                    <span class="tag">Lesson Today</span>
                    <h2>Better Swing<br>Better You</h2>
                    <div class="info"><span>Pilih jam kosong dan booking lesson per jam.</span></div>
                </div>
                <a href="{{ route('booking') }}" class="fw-btn lime md">Booking Sekarang {!! Icons::svg('arrow') !!}</a>
            </div>
        @endif

        <div class="bd-stats">
            @foreach ($stats as [$label, $value, $icon])
                <div class="fw-stat"><span>{{ $label }}</span><strong>{{ $value }}</strong><i>{!! Icons::svg($icon) !!}</i></div>
            @endforeach
        </div>
    </div>

    @if ($unpaid > 0)
        <a href="{{ Route::has('jadwal') ? route('jadwal') : route('booking') }}" class="fw-alert warn" style="margin:16px 0 0;align-items:center">
            {!! Icons::svg('wallet') !!}
            <span style="flex:1">Ada <b>{{ $unpaid }}</b> booking menunggu pembayaran. Selesaikan sebelum batas waktu agar jadwal tidak hilang.</span>
            {!! Icons::svg('chev', 'fw-chev') !!}
        </a>
    @endif

    <section class="fw-section">
        <div class="fw-section-head"><h2>Quick Action</h2></div>
        <div class="bd-quick">
            <a href="{{ Route::has('jadwal') ? route('jadwal') : route('booking') }}"><span class="ic">{!! Icons::svg('cal-check') !!}</span>Jadwal</a>
            <a href="{{ route('booking') }}"><span class="ic">{!! Icons::svg('plus-c') !!}</span>Booking</a>
            <a href="{{ route('chat') }}"><span class="ic">{!! Icons::svg('chat') !!}@if ($chatNew)<b>{{ $chatNew }}</b>@endif</span>Chat Coach</a>
            <a href="{{ Route::has('menu') ? route('menu') : route('profile.edit') }}"><span class="ic">{!! Icons::svg('menu') !!}</span>Menu</a>
            <a href="{{ route('program') }}"><span class="ic">{!! Icons::svg('star') !!}</span>Program</a>
            <a href="{{ route('galeri') }}"><span class="ic">{!! Icons::svg('image') !!}</span>Galeri</a>
        </div>
    </section>

    <div class="bd-cols fw-section">
        <section>
            <div class="fw-section-head"><h2>Coach Anda</h2><a href="{{ route('home') }}#coach" class="fw-link">Profil lengkap</a></div>
            @if ($coach)
                <a href="{{ route('home') }}#coach" class="fw-card bd-coach">
                    @if ($coach->photo_url)
                        <span class="fw-av" style="background-image:url('{{ $coach->photo_url }}')"></span>
                    @else
                        <span class="fw-av">{{ Icons::initials($coach->name) }}</span>
                    @endif
                    <div style="min-width:0">
                        <strong>{{ $coach->name }}</strong>
                        <small>{{ $coach->role ?: 'Coach profesional' }}@if ($coach->years_experience) · {{ $coach->years_experience }}+ tahun @endif</small>
                        @if ($coach->bio)<p>{{ $coach->bio }}</p>@endif
                    </div>
                    {!! Icons::svg('chev', 'fw-chev') !!}
                </a>
            @endif

            @if ($events->isNotEmpty())
                @php $ev = $events->first(); @endphp
                <div class="fw-section-head" style="margin-top:22px"><h2>Event Terdekat</h2><a href="{{ route('event') }}" class="fw-link">Semua</a></div>
                <a href="{{ route('event') }}" class="fw-row">
                    <span class="fw-av sq" style="background-image:url('{{ $ev->poster_url }}')"></span>
                    <span class="fw-row-body">
                        <span class="fw-row-title">{{ $ev->title }}</span>
                        <span class="fw-row-sub">{{ $ev->date_label }} · {{ $ev->time_range }}</span>
                    </span>
                    <span class="fw-badge orange-soft">{{ $ev->status_label }}</span>
                </a>
            @endif
        </section>

        @if ($programs->isNotEmpty())
            <section>
                <div class="fw-section-head"><h2>Program Latihan</h2><a href="{{ route('program') }}" class="fw-link">Lihat semua</a></div>
                <div class="bd-prog">
                    @foreach ($programs as $p)
                        <a href="{{ route('program') }}#program-{{ $p->id }}" style="background-image:url('{{ $p->image_url }}');background-position:{{ $p->image_css_position }}">
                            <span><small>{{ ucfirst($p->level) }}</small>{{ $p->name }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
