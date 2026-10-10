{{-- Jadwal Saya: booking mendatang, selesai, dibatalkan. --}}
@extends('layouts.fw')

@section('title', 'Jadwal Saya')
@section('no_footer', true)
@section('main_class', 'narrow')

@php
    use App\Support\Icons;
    use App\Support\BookingRules;
    use Carbon\Carbon;

    $coach     = \App\Models\Coach::main();
    $coachName = $coach?->name ?? 'Coach';
    $contact   = class_exists(\App\Models\ContactSetting::class) ? \App\Models\ContactSetting::query()->first() : null;
    $waNumber  = $contact?->whatsapp_number ?? null;
@endphp

@push('head')
<style>
    .jd-tabs { margin-bottom: 18px; }
    .jd-card { padding: 16px; }
    .jd-top { display: flex; gap: 12px; align-items: flex-start; }
    .jd-top .fw-av { width: 44px; height: 44px; flex-basis: 44px; }
    .jd-name { flex: 1; min-width: 0; }
    .jd-name strong { display: block; font-size: 15.5px; font-weight: 600; }
    .jd-name small { color: var(--fw-muted); font-size: 12.5px; }
    .jd-when { display: flex; flex-wrap: wrap; gap: 6px 16px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--fw-line-2); color: var(--fw-text-2); font-size: 13px; }
    .jd-when span { display: inline-flex; align-items: center; gap: 6px; }
    .jd-when svg { width: 15px; height: 15px; color: var(--fw-green-3); }
    .jd-pay { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 12px; padding: 10px 12px; border-radius: 12px; background: var(--fw-surface-2); font-size: 13px; }
    .jd-pay b { font-weight: 600; }
    .jd-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .jd-actions form { margin: 0; }
    .jd-note { margin-top: 10px; color: var(--fw-muted); font-size: 12px; line-height: 1.5; }
    .jd-res { display: none; margin-top: 12px; padding: 14px; border-radius: 14px; background: var(--fw-tint-2); border: 1px solid var(--fw-line); }
    .jd-res.open { display: block; }
    .jd-res .fw-form-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
    .jd-res .fw-field input { height: 44px; font-size: 14px; padding: 0 12px; }
    @media (max-width: 560px) { .jd-res .fw-form-grid { grid-template-columns: 1fr 1fr; } .jd-res .fw-form-grid > :first-child { grid-column: 1 / -1; } }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ route('dashboard') }}" class="fw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">Jadwal Saya</h1>
                <p class="fw-sub">Semua lesson yang sudah Anda booking.</p>
            </div>
        </div>
        <a href="{{ route('booking') }}" class="fw-btn md fw-nav-hide-m">{!! Icons::svg('plus') !!} Booking Baru</a>
    </div>

    @if (session('booking_success'))
        <div class="fw-alert ok">{!! Icons::svg('check-c') !!} {{ session('booking_success') }}</div>
    @endif
    @if ($errors->any())
        <div class="fw-alert err"><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
    @endif

    <div class="fw-pills jd-tabs" role="tablist">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('jadwal', ['tab' => $key]) }}" class="fw-pill {{ $tab === $key ? 'on' : '' }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                {{ $label }} @if ($counts[$key] > 0)<small>{{ $counts[$key] }}</small>@endif
            </a>
        @endforeach
    </div>

    <div class="fw-list">
        @forelse ($bookings as $b)
            @php
                $date    = Carbon::parse($b->booking_date);
                $start   = substr((string) $b->start_time, 0, 5);
                $end     = substr((string) $b->end_time, 0, 5);
                $pay     = $payments->get($b->id);
                $isPaid  = $pay && $pay->status === 'paid';
                $offline = ($b->source ?? null) === 'offline';
                $canMod  = $tab === 'mendatang' && BookingRules::canModifyDate($b->booking_date) && in_array($b->status, ['pending', 'booked'], true);

                if ($tab === 'mendatang') {
                    [$badge, $badgeClass] = $b->status === 'booked' ? ['Disetujui', 'green'] : ['Mendatang', 'orange'];
                } elseif ($tab === 'selesai') {
                    [$badge, $badgeClass] = ['Selesai', 'green'];
                } else {
                    [$badge, $badgeClass] = [$b->status === 'rejected' ? 'Ditolak' : 'Dibatalkan', 'red'];
                }
            @endphp

            <article class="fw-card jd-card">
                <div class="jd-top">
                    @if ($coach?->photo_url)
                        <span class="fw-av" style="background-image:url('{{ $coach->photo_url }}')"></span>
                    @else
                        <span class="fw-av">{{ Icons::initials($coachName) }}</span>
                    @endif
                    <div class="jd-name">
                        <strong>{{ $b->lesson_label }}</strong>
                        <small>{{ $coachName }}</small>
                    </div>
                    <span class="fw-badge {{ $badgeClass }}">{{ $badge }}</span>
                </div>

                <div class="jd-when">
                    <span>{!! Icons::svg('calendar') !!} {{ $date->locale('id')->translatedFormat('D, d M Y') }}</span>
                    <span>{!! Icons::svg('clock') !!} {{ $start }} – {{ $end }}</span>
                    @if ($b->place_label)<span>{!! Icons::svg('pin') !!} {{ $b->place_label }}</span>@endif
                </div>

                @if ($tab === 'mendatang')
                    <div class="jd-pay">
                        @if ($offline)
                            <span>Pembayaran: <b>Lunas · dibayar di tempat</b></span>
                        @elseif ($isPaid)
                            <span>Pembayaran: <b style="color:var(--d-ink-green, var(--fw-green))">Lunas</b> @if ($pay?->amount_label) · {{ $pay->amount_label }} @endif</span>
                        @else
                            <span>Pembayaran: <b style="color:var(--d-orange-ink, #a2650c)">{{ $pay?->status_label ?? 'Belum dibayar' }}</b></span>
                            @if (Route::has('payment.booking'))
                                <a href="{{ route('payment.booking', $b) }}" class="fw-btn sm">Bayar</a>
                            @endif
                        @endif
                    </div>

                    <div class="jd-actions">
                        @if (Route::has('payment.booking') && ! $offline)
                            <a href="{{ route('payment.booking', $b) }}" class="fw-btn sm ghost">Detail</a>
                        @endif
                        @if ($canMod)
                            <button type="button" class="fw-btn sm soft" data-res="res-{{ $b->id }}">{!! Icons::svg('refresh') !!} Ubah Jadwal</button>
                            <form method="POST" action="{{ route('booking.cancel', $b) }}" data-confirm="Yakin ingin membatalkan booking ini? Slot akan tersedia kembali.">
                                @csrf
                                <button type="submit" class="fw-btn sm danger">Batalkan</button>
                            </form>
                        @elseif ($waNumber)
                            <a href="https://wa.me/{{ $waNumber }}?text={{ rawurlencode('Halo, saya ' . auth()->user()->name . ' ingin mengubah booking ' . $b->lesson_label . ' tanggal ' . $date->locale('id')->translatedFormat('d M Y') . ' jam ' . $start . '–' . $end . '.') }}" target="_blank" rel="noopener" class="fw-btn sm ghost">{!! Icons::svg('whatsapp') !!} Hubungi Admin</a>
                        @endif
                        <a href="{{ route('chat') }}" class="fw-btn sm ghost">{!! Icons::svg('chat') !!} Chat Coach</a>
                    </div>

                    @if ($canMod)
                        <div class="jd-res" id="res-{{ $b->id }}">
                            <form method="POST" action="{{ route('booking.reschedule', $b) }}" data-confirm="Ubah jadwal? Booking akan kembali PENDING sampai disetujui admin.">
                                @csrf
                                <input type="hidden" name="from" value="jadwal">
                                <div class="fw-form-grid">
                                    <div class="fw-field">
                                        <label>Tanggal baru</label>
                                        <input type="date" name="booking_date" min="{{ BookingRules::minRescheduleDate()->format('Y-m-d') }}" value="{{ $date->format('Y-m-d') }}" required>
                                    </div>
                                    @if ($b->isCourse())
                                        <div class="fw-field" style="grid-column: span 2"><label>Jam</label><div class="fw-hint" style="padding-top:12px">{{ BookingRules::courseStart() }} – {{ BookingRules::courseEnd() }} (Course Lesson)</div></div>
                                    @else
                                        <div class="fw-field">
                                            <label>Mulai</label>
                                            <input type="time" name="start_time" value="{{ $start }}" min="{{ BookingRules::openTime() }}" max="{{ BookingRules::closeTime() }}" step="{{ BookingRules::slotMinutes() * 60 }}" required>
                                        </div>
                                        <div class="fw-field">
                                            <label>Selesai</label>
                                            <input type="time" name="end_time" value="{{ $end }}" min="{{ BookingRules::openTime() }}" max="{{ BookingRules::closeTime() }}" step="{{ BookingRules::slotMinutes() * 60 }}" required>
                                        </div>
                                    @endif
                                </div>
                                <div class="jd-actions">
                                    <button type="submit" class="fw-btn sm">Ajukan Jadwal Baru</button>
                                    <button type="button" class="fw-btn sm ghost" data-res="res-{{ $b->id }}">Tutup</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <p class="jd-note">{{ $canMod ? BookingRules::cancelRuleText() : 'Pembatalan & perubahan jadwal sudah ditutup. ' . BookingRules::cancelRuleText() }}</p>
                @endif
            </article>
        @empty
            <div class="fw-empty">
                <b>
                    @if ($tab === 'mendatang') Belum ada jadwal mendatang
                    @elseif ($tab === 'selesai') Belum ada lesson yang selesai
                    @else Tidak ada booking yang dibatalkan @endif
                </b>
                @if ($tab === 'mendatang')
                    Yuk booking lesson pertama Anda.
                    <div style="margin-top:14px"><a href="{{ route('booking') }}" class="fw-btn md">Booking Sekarang</a></div>
                @endif
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-res]').forEach(function (b) {
        b.addEventListener('click', function () { document.getElementById(b.dataset.res)?.classList.toggle('open'); });
    });
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
    });
</script>
@endpush
