@extends('layouts.fw')

@section('title', 'Event')

@php
    use App\Support\Icons;

    // Event aktif yang belum lewat, urut dari tanggal terdekat
    $events = \App\Models\Event::active()->upcoming()->chronological()->get();

    $user       = auth()->user();
    $isCustomer = $user && $user->role === 'customer';
    $isAdmin    = $user && $user->role === 'admin';
@endphp

@push('head')
<style>
    .ev-list { display: grid; gap: 22px; }
    .ev { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 0; overflow: hidden; }
    .ev-poster { background: var(--fw-bg-2); display: grid; place-items: center; }
    .ev-poster img { width: 100%; height: 100%; max-height: 640px; object-fit: contain; }
    .ev-ph { width: 100%; min-height: 280px; display: grid; place-items: center; background: linear-gradient(140deg, var(--d-btn, #1f4d33), var(--d-btn-2, #3d7d57)); color: rgba(255, 255, 255, .7); }
    .ev-ph svg { width: 54px; height: 54px; }
    .ev-body { padding: 26px; display: flex; flex-direction: column; gap: 16px; }
    .ev-body h2 { font-family: var(--fw-serif); font-size: clamp(26px, 3vw, 34px); font-weight: 600; line-height: 1.15; }
    .ev-body > p { color: var(--fw-text-2); font-size: 14.5px; line-height: 1.7; white-space: pre-line; }
    .ev-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .ev-info div { display: flex; gap: 10px; align-items: flex-start; padding: 12px; border-radius: 14px; background: var(--fw-surface-2); }
    .ev-info i { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; border-radius: 10px; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); }
    .ev-info i svg { width: 17px; height: 17px; }
    .ev-info small { display: block; color: var(--fw-muted); font-size: 11.5px; }
    .ev-info strong { font-size: 13.5px; font-weight: 600; }
    .ev-pay { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; padding: 16px 18px; border-radius: 18px; background: var(--fw-green); color: #fff; }
    .ev-pay small { display: block; color: rgba(255, 255, 255, .7); font-size: 12px; }
    .ev-pay strong { font-family: var(--fw-serif); font-size: 28px; font-weight: 600; }
    .ev-pay strong span { font-family: var(--fw-sans); font-size: 13px; font-weight: 500; color: rgba(255, 255, 255, .7); }
    .ev-trust { display: flex; flex-wrap: wrap; gap: 8px 18px; color: var(--fw-muted); font-size: 12.5px; }
    .ev-trust span { display: inline-flex; align-items: center; gap: 6px; }
    .ev-trust svg { width: 15px; height: 15px; color: var(--fw-green-3); }
    @media (max-width: 900px) { .ev { grid-template-columns: minmax(0, 1fr); } .ev-poster img { max-height: 520px; } }
    @media (max-width: 560px) { .ev-body { padding: 18px; } .ev-info { grid-template-columns: minmax(0, 1fr); } }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ $isCustomer ? route('dashboard') : route('home') }}" class="fw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">Event</h1>
                <p class="fw-sub">Ikuti event & coaching clinic, amankan tempat Anda.</p>
            </div>
        </div>
    </div>

    @if ($events->isEmpty())
        <div class="fw-empty"><b>Belum ada event terdekat</b>Nantikan event berikutnya.</div>
    @else
        <div class="ev-list">
            @foreach ($events as $event)
                <article class="fw-card ev" id="event-{{ $event->id }}">
                    <div class="ev-poster">
                        @if ($event->poster_url)
                            <img src="{{ $event->poster_url }}" alt="Poster {{ $event->title }}">
                        @else
                            <div class="ev-ph">{!! Icons::svg('flag') !!}</div>
                        @endif
                    </div>

                    <div class="ev-body">
                        <div>
                            <span class="fw-badge {{ $event->canRegister() ? 'green' : 'grey' }}">{{ $event->status_label }}</span>
                            <h2 style="margin-top:10px">{{ $event->title }}</h2>
                        </div>
                        @if ($event->description)<p>{{ $event->description }}</p>@endif

                        <div class="ev-info">
                            <div><i>{!! Icons::svg('calendar') !!}</i><span><small>Tanggal</small><strong>{{ $event->date_label }}</strong></span></div>
                            <div><i>{!! Icons::svg('clock') !!}</i><span><small>Waktu</small><strong>{{ $event->time_range }}</strong></span></div>
                            <div><i>{!! Icons::svg('pin') !!}</i><span><small>Lokasi</small><strong>{{ $event->location ?: '-' }}</strong></span></div>
                            <div><i>{!! Icons::svg('users') !!}</i><span><small>Status</small><strong>{{ $event->status_label }}</strong></span></div>
                        </div>

                        <div class="ev-pay">
                            <div>
                                <small>Harga tiket</small>
                                <strong>{{ $event->price_label }} @if ($event->price > 0)<span>/ {{ $event->price_unit }}</span>@endif</strong>
                            </div>
                            @if (! $event->canRegister())
                                <span class="fw-btn white" aria-disabled="true">{{ $event->status_label }}</span>
                            @elseif ($isCustomer)
                                <a href="{{ route('payment', ['event' => $event->id]) }}" class="fw-btn lime">Daftar & Bayar {!! Icons::svg('arrow') !!}</a>
                            @elseif ($isAdmin)
                                <a href="{{ route('admin.events.edit', $event) }}" class="fw-btn lime">{!! Icons::svg('edit') !!} Kelola Event</a>
                            @else
                                <a href="{{ route('login') }}" class="fw-btn lime">Masuk untuk daftar {!! Icons::svg('arrow') !!}</a>
                            @endif
                        </div>

                        <div class="ev-trust">
                            <span>{!! Icons::svg('shield') !!} Pembayaran aman</span>
                            <span>{!! Icons::svg('spark') !!} Proses cepat</span>
                            <span>{!! Icons::svg('chat') !!} Tim siap membantu</span>
                        </div>

                        @if ($event->note)
                            <div class="fw-alert warn" style="margin:0"><span><b>Catatan:</b> {{ $event->note }}</span></div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
