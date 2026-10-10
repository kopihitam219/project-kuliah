@php
    use Carbon\Carbon;

    /*
    |--------------------------------------------------------------------------
    | Tanggal terpilih
    |--------------------------------------------------------------------------
    */

    $today = Carbon::today();

    try {
        $selected = Carbon::parse($selectedDate);
    } catch (\Throwable $e) {
        $selected = $today->copy();
    }

    if ($selected->lt($today)) {
        $selected = $today->copy();
    }

    $selectedDateString = $selected->format('Y-m-d');
    $selectedIsToday    = $selected->isSameDay($today);

    $monthStart   = $selected->copy()->startOfMonth();
    $monthEnd     = $selected->copy()->endOfMonth();
    $startWeekday = $monthStart->dayOfWeek;

    $previousMonth   = $selected->copy()->subMonth()->startOfMonth();
    $nextMonth       = $selected->copy()->addMonth()->startOfMonth();
    $previousAllowed = $previousMonth->copy()->endOfMonth()->gte($today);


    /*
    |--------------------------------------------------------------------------
    | Daftar waktu terpakai (pending, booked, milik sendiri)
    |--------------------------------------------------------------------------
    */

    $toRange = fn ($booking) => [
        'start' => Carbon::parse($booking->start_time),
        'end'   => Carbon::parse($booking->end_time),
    ];

    $pendingList = collect($pendingBookings)->map($toRange);
    $bookedList  = collect($bookedBookings)->map($toRange);

    $blockedList = class_exists(\App\Models\ScheduleBlock::class)
        ? \App\Models\ScheduleBlock::whereDate('date', $selected->toDateString())->get()->map(fn ($b) => [
            'start' => Carbon::parse($b->start_time ?? '00:00'),
            'end'   => Carbon::parse($b->end_time ?? '23:59:59'),
        ])
        : collect();

    $mineList = collect($yourBookings)
        ->filter(fn ($b) =>
            in_array($b->status, ['pending', 'booked'], true)
            && Carbon::parse($b->booking_date)->isSameDay($selected)
        )
        ->map($toRange);

    $overlaps = fn ($list, $s, $e) => $list->contains(
        fn ($r) => $r['start']->lt($e) && $r['end']->gt($s)
    );


    /*
    |--------------------------------------------------------------------------
    | Booking pending yang belum dibayar: ditahan sampai batas waktu bayar
    |--------------------------------------------------------------------------
    */

    $pendingIds     = collect($pendingBookings)->pluck('id');
    $paymentStatus  = $pendingIds->isNotEmpty()
        ? \App\Models\Payment::whereIn('booking_id', $pendingIds)->pluck('status', 'booking_id')
        : collect();
    $deadlineMinutes = \App\Support\BookingRules::paymentDeadlineMinutes();

    $holdList = collect($pendingBookings)
        ->filter(fn ($b) =>
            $b->user_id
            && ($b->getAttributes()['source'] ?? null) !== 'offline'
            && ($b->getAttributes()['created_at'] ?? null)
            && in_array($paymentStatus[$b->id] ?? 'unpaid', ['unpaid', 'pending'], true)
        )
        ->map(fn ($b) => $toRange($b) + [
            'deadline' => Carbon::parse($b->getAttributes()['created_at'])->addMinutes($deadlineMinutes),
        ]);

    $holdFor = fn ($s, $e) => optional($holdList->first(
        fn ($r) => $r['start']->lt($e) && $r['end']->gt($s)
    ))['deadline'];


    /*
    |--------------------------------------------------------------------------
    | Semua slot 30 menit (07:00 - 20:00) beserta statusnya
    |--------------------------------------------------------------------------
    | Status: available | pending | booked | mine | past
    */

    $slotMap = [];
    $counts  = ['available' => 0, 'pending' => 0, 'booked' => 0, 'mine' => 0, 'past' => 0, 'blocked' => 0];

    $cursor  = Carbon::createFromFormat('H:i', \App\Support\BookingRules::openTime());
    $closing = Carbon::createFromFormat('H:i', \App\Support\BookingRules::closeTime());

    while ($cursor->lt($closing)) {

        $slotStart = $cursor->copy();
        $slotEnd   = $cursor->copy()->addMinutes(\App\Support\BookingRules::slotMinutes());

        if ($slotEnd->gt($closing)) {
            break;
        }

        if ($overlaps($mineList, $slotStart, $slotEnd)) {
            $status = 'mine';
        } elseif ($overlaps($blockedList, $slotStart, $slotEnd)) {
            $status = 'blocked';
        } elseif ($overlaps($bookedList, $slotStart, $slotEnd)) {
            $status = 'booked';
        } elseif ($overlaps($pendingList, $slotStart, $slotEnd)) {
            $status = 'pending';
        } elseif (
            $selectedIsToday
            && $selected->copy()->setTimeFromTimeString($slotStart->format('H:i'))->lte(Carbon::now())
        ) {
            $status = 'past';
        } else {
            $status = 'available';
        }

        $counts[$status]++;

        $slotHold = in_array($status, ['mine', 'pending'], true) ? $holdFor($slotStart, $slotEnd) : null;

        $slotMap[] = [
            'hold'   => $slotHold?->toIso8601String(),
            'start'  => $slotStart->format('H:i'),
            'end'    => $slotEnd->format('H:i'),
            'status' => $status,
        ];

        $cursor->addMinutes(\App\Support\BookingRules::slotMinutes());
    }

    $statusLabels = [
        'pending' => 'Pending',
        'booked'  => 'Booked',
        'mine'    => 'Booking Anda',
        'past'    => 'Lewat',
        'blocked' => 'Ditutup',
    ];


    /*
    |--------------------------------------------------------------------------
    | Cancel / reschedule hanya sebelum hari lesson (H-1)
    |--------------------------------------------------------------------------
    */

    $canModifyBooking = function ($booking) use ($today) {
        return \App\Support\BookingRules::canModifyDate($booking->booking_date)
            && in_array($booking->status, ['pending', 'booked'], true);
    };

    $oldStart = old('start_time');
    $oldEnd   = old('end_time');


    /*
    |--------------------------------------------------------------------------
    | Jenis lesson & lapangan
    |--------------------------------------------------------------------------
    */

    $locations    = collect($locations ?? []);
    $lessonTypes  = \App\Support\BookingRules::lessonTypes();
    $lessonType   = old('lesson_type', request('type', 'driving'));
    $lessonType   = array_key_exists($lessonType, $lessonTypes) ? $lessonType : 'driving';
    $locationId   = (string) old('location_id', request('location', ''));

    $pricePerHour = \App\Models\Payment::pricePerHour();
    $coursePrice  = \App\Support\BookingRules::coursePrice();
    $courseStart  = \App\Support\BookingRules::courseStart();
    $courseEnd    = \App\Support\BookingRules::courseEnd();
    $minMinutes   = \App\Support\BookingRules::minMinutes();

    // Course Lesson tersedia jika semua slot di jam course masih kosong
    $courseSlots     = collect($slotMap)->filter(fn ($s) => $s['start'] >= $courseStart && $s['end'] <= $courseEnd);
    $courseBlocking  = $courseSlots->first(fn ($s) => $s['status'] !== 'available');
    $courseAvailable = isset($lessonTypes['course']) && $courseSlots->isNotEmpty() && ! $courseBlocking;
    $courseReason    = $courseBlocking
        ? 'Jam ' . $courseBlocking['start'] . ' berstatus ' . ($statusLabels[$courseBlocking['status']] ?? 'terisi') . '.'
        : ($courseSlots->isEmpty() ? 'Jam course di luar jam operasional.' : '');

    $rp = fn ($n) => 'Rp' . number_format((int) $n, 0, ',', '.');

    // Link kalender tetap membawa pilihan jenis lesson & lapangan
    $linkFor = fn ($date) => array_filter([
        'date'     => $date,
        'type'     => $lessonType,
        'location' => $locationId,
    ]);
@endphp

@php
    $Icons = \App\Support\Icons::class;
    $stripDays = collect(range(0, 20))->map(fn ($i) => $today->copy()->addDays($i));
    if (! $stripDays->contains(fn ($d) => $d->isSameDay($selected))) {
        $stripDays->push($selected->copy());
    }
    $upcomingCount = collect($yourBookings)->count();
    $slotHint = \App\Support\BookingRules::slotMinutes() >= 60 ? 'per jam' : 'per ' . \App\Support\BookingRules::slotMinutes() . ' menit';
@endphp
@extends('layouts.fw')

@section('title', 'Booking Lesson')
@section('no_footer', true)

@push('head')
<style>
    .bk-layout { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 22px; align-items: start; }
    .bk-step { margin-bottom: 22px; }
    .bk-step-title { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
    .bk-step-title h2 { display: flex; align-items: center; gap: 8px; font-size: 15.5px; font-weight: 700; }
    .bk-step-title h2 b { width: 22px; height: 22px; display: grid; place-items: center; border-radius: 50%; background: var(--fw-green); color: #fff; font-size: 11px; }
    .bk-step-title small { color: var(--fw-muted); font-size: 12.5px; }

    /* Strip tanggal */
    .bk-days { display: flex; gap: 8px; overflow-x: auto; scroll-snap-type: x proximity; padding: 2px 2px 6px; margin: 0 -2px; scrollbar-width: thin; }
    .bk-day { flex: 0 0 64px; scroll-snap-align: start; display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 10px 4px; border-radius: 16px; background: var(--fw-surface); border: 1px solid var(--fw-line); color: var(--fw-text-2); font-size: 12px; text-align: center; }
    .bk-day strong { font-size: 13px; font-weight: 600; color: var(--fw-text); }
    .bk-day:hover { border-color: var(--fw-line-2); }
    .bk-day.selected { background: var(--fw-green); border-color: var(--fw-green); color: rgba(255, 255, 255, .8); box-shadow: 0 8px 18px rgba(var(--d-green-rgb, 31, 77, 51), .25); }
    .bk-day.selected strong { color: #fff; }
    .bk-day.today:not(.selected) { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .4); }
    .bk-more { position: relative; flex: 0 0 64px; display: grid; place-items: center; gap: 2px; padding: 10px 4px; border-radius: 16px; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); font-size: 11.5px; font-weight: 600; cursor: pointer; }
    .bk-more svg { width: 18px; height: 18px; }
    .bk-more input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .bk-month { color: var(--fw-muted); font-size: 12.5px; }

    /* Jenis lesson & lokasi */
    .bk-opts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .bk-opt input { position: absolute; opacity: 0; pointer-events: none; }
    .bk-opt-card { height: 100%; display: flex; gap: 12px; padding: 14px; border-radius: 16px; background: var(--fw-surface); border: 1.5px solid var(--fw-line); cursor: pointer; transition: border-color .15s, background .15s; }
    .bk-opt-card:hover { border-color: var(--fw-line-2); }
    .bk-opt input:checked + .bk-opt-card { border-color: var(--fw-green); background: var(--fw-tint-2); box-shadow: 0 0 0 3px rgba(var(--d-green-rgb, 31, 77, 51), .08); }
    .bk-opt input:focus-visible + .bk-opt-card { outline: 2px solid var(--fw-green); outline-offset: 2px; }
    .bk-opt-ic { width: 40px; height: 40px; flex: 0 0 40px; display: grid; place-items: center; border-radius: 12px; background: var(--fw-tint); color: var(--d-ink-green, var(--fw-green)); }
    .bk-opt input:checked + .bk-opt-card .bk-opt-ic { background: var(--fw-green); color: #fff; }
    .bk-opt-ic svg { width: 20px; height: 20px; }
    .bk-opt-card strong { display: block; font-size: 14.5px; font-weight: 600; }
    .bk-opt-card .price { display: block; margin-top: 2px; color: var(--d-ink-green, var(--fw-green)); font-size: 13px; font-weight: 600; }
    .bk-opt-card .desc { display: block; margin-top: 4px; color: var(--fw-muted); font-size: 12px; line-height: 1.45; }
    .bk-venue { margin-top: 2px; }

    /* Slot */
    .bk-legend { display: flex; flex-wrap: wrap; gap: 6px 14px; margin-bottom: 10px; color: var(--fw-muted); font-size: 12px; }
    .bk-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .bk-legend i { width: 8px; height: 8px; border-radius: 50%; background: var(--fw-green-3); }
    .bk-legend i.pending { background: var(--fw-orange); } .bk-legend i.booked { background: var(--fw-red); } .bk-legend i.mine { background: var(--fw-blue); } .bk-legend i.off { background: #b9c0bb; }
    .bk-slots { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    @media (max-width: 1180px) { .bk-slots { grid-template-columns: minmax(0, 1fr); } }
    .bk-slot { width: 100%; display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 16px; background: var(--fw-surface); border: 1.5px solid var(--fw-line); text-align: left; font: inherit; color: var(--fw-text); }
    .bk-slot .dot { width: 9px; height: 9px; flex: 0 0 9px; border-radius: 50%; background: var(--fw-green-3); }
    .bk-slot .tm { flex: 1; min-width: 0; }
    .bk-slot .tm strong { display: block; font-size: 15px; font-weight: 600; font-variant-numeric: tabular-nums; }
    .bk-slot .tm small { display: inline-flex; align-items: center; gap: 4px; color: var(--fw-muted); font-size: 12px; }
    .bk-slot .act { flex: 0 0 auto; height: 34px; padding: 0 16px; display: inline-flex; align-items: center; border-radius: 99px; background: var(--fw-green); color: #fff; font-size: 12.5px; font-weight: 600; }
    button.bk-slot { cursor: pointer; }
    button.bk-slot:hover { border-color: rgba(var(--d-green-rgb, 31, 77, 51), .35); }
    button.bk-slot.selected { border-color: var(--fw-green); background: var(--fw-tint-2); }
    button.bk-slot.selected .act { background: var(--fw-lime); color: var(--d-ink-green, var(--fw-green)); }
    button.bk-slot.selected .act::before { content: "✓ "; white-space: pre; }
    div.bk-slot { background: var(--fw-surface-2); color: var(--fw-muted); }
    div.bk-slot .tm strong { color: var(--fw-muted); }
    div.bk-slot.slot-pending .dot { background: var(--fw-orange); }
    div.bk-slot.slot-booked .dot { background: var(--fw-red); }
    div.bk-slot.slot-mine { background: var(--fw-blue-tint); border-color: rgba(59, 111, 182, .25); }
    div.bk-slot.slot-mine .dot { background: var(--fw-blue); }
    div.bk-slot.slot-mine .tm strong { color: var(--fw-blue); }
    div.bk-slot.slot-past .dot, div.bk-slot.slot-blocked .dot { background: #b9c0bb; }
    .slot-hold { padding: 4px 9px; border-radius: 99px; background: var(--fw-orange-tint); color: var(--d-orange-ink, #8a5608); font-size: 11px; font-weight: 600; white-space: nowrap; }
    .slot-hold.urgent { background: var(--fw-red-tint); color: var(--fw-red); }

    /* Ringkasan */
    .bk-summary { position: sticky; top: 92px; padding: 20px; }
    .bk-summary h2 { font-family: var(--fw-serif); font-size: 22px; font-weight: 600; margin-bottom: 14px; }
    .bk-sum-row { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px dashed var(--fw-line-2); font-size: 13.5px; }
    .bk-sum-row span:first-child { color: var(--fw-muted); }
    .bk-sum-row span:last-child { font-weight: 600; text-align: right; }
    .bk-sum-total { display: flex; justify-content: space-between; align-items: baseline; margin: 14px 0; }
    .bk-sum-total strong { font-family: var(--fw-serif); font-size: 26px; color: var(--d-ink-green, var(--fw-green)); }
    .bk-times { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 12px; }
    .bk-times select { height: 44px; font-size: 14px; padding: 0 10px; }
    .bk-note { margin-top: 12px; color: var(--fw-muted); font-size: 12px; line-height: 1.55; }
    .course-box { display: grid; gap: 6px; margin-top: 12px; padding: 12px 14px; border-radius: 14px; background: var(--fw-tint-2); border: 1px solid var(--fw-line); font-size: 13px; }
    .course-box span { color: var(--fw-muted); }
    .course-box .course-ok { color: var(--d-ink-green, var(--fw-green)); font-style: normal; font-weight: 600; }
    .course-box .course-no { color: var(--fw-red); font-style: normal; }
    .course-box .course-note { color: var(--fw-muted); }
    .bk-mine { display: flex; align-items: center; gap: 12px; margin-top: 14px; padding: 14px; }

    /* Bar bawah HP */
    .bk-bar { display: none; }

    @media (max-width: 1000px) {
        .bk-layout { grid-template-columns: minmax(0, 1fr); }
        .bk-summary { position: static; }
    }
    @media (max-width: 820px) {
        .bk-opts { grid-template-columns: 1fr; }
        .bk-day { flex-basis: 58px; }
        .bk-bar {
            position: fixed; left: 10px; right: 10px; bottom: calc(82px + env(safe-area-inset-bottom)); z-index: 240;
            display: flex; align-items: center; gap: 12px; padding: 10px 10px 10px 16px; border-radius: 20px;
            background: var(--fw-green); color: #fff; box-shadow: 0 12px 30px rgba(var(--d-shadow-rgb, 23, 46, 33), .35);
            transform: translateY(160%); visibility: hidden; transition: transform .25s ease, visibility .25s;
        }
        .bk-bar.show { transform: none; visibility: visible; }
        .bk-bar div { flex: 1; min-width: 0; line-height: 1.25; }
        .bk-bar small { display: block; color: rgba(255, 255, 255, .7); font-size: 11.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .bk-bar strong { font-size: 17px; }
        .bk-bar .fw-btn { height: 42px; padding: 0 16px; }
        body.bk-has-bar .fw-main { padding-bottom: 90px; }
    }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ route('dashboard') }}" class="fw-back" aria-label="Kembali">{!! $Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">Booking Lesson</h1>
                <p class="fw-sub">Pilih tanggal, jenis lesson, lokasi, lalu jam yang tersedia.</p>
            </div>
        </div>
        @if (Route::has('jadwal'))
            <a href="{{ route('jadwal') }}" class="fw-btn md ghost fw-nav-hide-m">{!! $Icons::svg('cal-check') !!} Jadwal Saya @if ($upcomingCount) ({{ $upcomingCount }}) @endif</a>
        @endif
    </div>

    @if(session('booking_success'))
        <div class="fw-alert ok">{!! $Icons::svg('check-c') !!} {{ session('booking_success') }}</div>
    @endif

    @if($errors->any())
        <div class="fw-alert err">
            <div>
                <strong>Booking belum dapat diproses:</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    <div class="bk-layout">
        <div>
            {{-- 1. TANGGAL --}}
            <section class="bk-step">
                <div class="bk-step-title">
                    <h2><b>1</b> Pilih tanggal</h2>
                    <span class="bk-month">{{ $selected->locale('id')->translatedFormat('F Y') }}</span>
                </div>
                <div class="bk-days" id="bkDays">
                    @foreach ($stripDays as $d)
                        @php $ds = $d->format('Y-m-d'); @endphp
                        <a href="{{ route('booking', $linkFor($ds)) }}"
                           class="bk-day calendar-day {{ $ds === $selectedDateString ? 'selected' : '' }} {{ $d->isSameDay($today) ? 'today' : '' }}"
                           @if ($ds === $selectedDateString) aria-current="date" @endif>
                            <span>{{ $d->isSameDay($today) ? 'Hari ini' : $d->locale('id')->translatedFormat('D') }}</span>
                            <strong>{{ $d->format('d') }}</strong>
                            <span>{{ $d->locale('id')->translatedFormat('M') }}</span>
                        </a>
                    @endforeach
                    <label class="bk-more" title="Pilih tanggal lain">
                        {!! $Icons::svg('calendar') !!} Lainnya
                        <input type="date" id="bkPickDate" min="{{ $today->format('Y-m-d') }}" value="{{ $selectedDateString }}" aria-label="Pilih tanggal lain">
                    </label>
                </div>
            </section>

            {{-- 2. JENIS LESSON --}}
            <section class="bk-step">
                <div class="bk-step-title"><h2><b>2</b> Jenis lesson</h2></div>
                <div class="bk-opts">
                    @foreach($lessonTypes as $typeKey => $typeLabel)
                        <label class="bk-opt">
                            <input type="radio" name="lesson_type" value="{{ $typeKey }}" form="bookingForm" {{ $lessonType === $typeKey ? 'checked' : '' }}>
                            <span class="bk-opt-card">
                                <span class="bk-opt-ic">{!! $Icons::svg($typeKey === 'course' ? 'flag' : 'target') !!}</span>
                                <span>
                                    <strong>{{ $typeLabel }}</strong>
                                    <span class="price">{{ $typeKey === 'course' ? $rp($coursePrice) . ' / sesi' : $rp($pricePerHour) . ' / jam' }}</span>
                                    <span class="desc">
                                        @if($typeKey === 'course')
                                            Sesi tetap {{ $courseStart }}–{{ $courseEnd }} di lapangan golf pilihan Anda.
                                            @if(\App\Support\BookingRules::courseNote())<br>* {{ \App\Support\BookingRules::courseNote() }}@endif
                                        @else
                                            Latihan di driving range bersama coach, dihitung per jam.
                                        @endif
                                    </span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- 3. LOKASI --}}
            <section class="bk-step">
                @if($locations->isEmpty())
                    <div id="locationStep" class="fw-alert warn">Belum ada lapangan driving range yang aktif. Silakan hubungi admin.</div>
                @else
                    <div id="locationStep">
                        <div class="bk-step-title"><h2><b>3</b> Pilih lokasi driving range</h2></div>
                        <div class="bk-opts">
                            @foreach($locations as $location)
                                <label class="bk-opt">
                                    <input type="radio" name="location_id" value="{{ $location->id }}" form="bookingForm" {{ $locationId === (string) $location->id ? 'checked' : '' }}>
                                    <span class="bk-opt-card">
                                        <span class="bk-opt-ic">{!! $Icons::svg('pin') !!}</span>
                                        <span>
                                            <strong>{{ $location->name }}</strong>
                                            @if($location->area)<span class="desc">{{ $location->area }}</span>@endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div id="courseLocation" class="bk-venue" hidden>
                    <div class="bk-step-title"><h2><b>3</b> Lapangan golf pilihan Anda</h2></div>
                    <div class="fw-field">
                        <input type="text" name="course_venue" id="courseVenue" form="bookingForm" maxlength="150"
                               value="{{ old('course_venue') }}" placeholder="Contoh: Padang Golf Pondok Indah">
                        <small>Admin akan mengonfirmasi ketersediaan lapangan golf yang Anda tulis.</small>
                    </div>
                </div>
            </section>

            {{-- 4. JAM --}}
            <section class="bk-step" id="timeStep">
                <div class="bk-step-title">
                    <h2><b>4</b> <span id="timeStepTitle">Pilih jam</span></h2>
                    <small>{{ $selected->locale('id')->translatedFormat('l, d M') }} · {{ $slotHint }}</small>
                </div>

                <div class="bk-legend">
                    <span><i></i>Tersedia ({{ $counts['available'] }})</span>
                    @if ($counts['pending'])<span><i class="pending"></i>Pending ({{ $counts['pending'] }})</span>@endif
                    @if ($counts['booked'])<span><i class="booked"></i>Booked ({{ $counts['booked'] }})</span>@endif
                    @if ($counts['mine'])<span><i class="mine"></i>Booking Anda ({{ $counts['mine'] }})</span>@endif
                    @if ($counts['blocked'] || $counts['past'])<span><i class="off"></i>Tidak tersedia ({{ $counts['blocked'] + $counts['past'] }})</span>@endif
                </div>

                <div class="bk-slots">
                    @foreach($slotMap as $slot)
                        @if($slot['status'] === 'available')
                            <button type="button" class="bk-slot available-slot" data-start="{{ $slot['start'] }}" data-end="{{ $slot['end'] }}">
                                <span class="dot"></span>
                                <span class="tm"><strong>{{ $slot['start'] }} - {{ $slot['end'] }}</strong><small>★ Tersedia</small></span>
                                <span class="act">Booking</span>
                            </button>
                        @else
                            <div class="bk-slot slot-{{ $slot['status'] }}">
                                <span class="dot"></span>
                                <span class="tm"><strong>{{ $slot['start'] }} - {{ $slot['end'] }}</strong><small>{{ $statusLabels[$slot['status']] }}</small></span>
                                @if(! empty($slot['hold']))
                                    <span class="slot-hold" data-hold-deadline="{{ $slot['hold'] }}">
                                        {{ $slot['status'] === 'mine' ? 'Bayar' : 'Tersedia lagi' }} <b>--:--</b>
                                    </span>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        </div>

        {{-- RINGKASAN --}}
        <aside>
            <form action="{{ route('booking.store') }}" method="POST" id="bookingForm" class="fw-card bk-summary">
                @csrf
                <input type="hidden" name="booking_date" value="{{ $selectedDateString }}">

                <h2>Ringkasan</h2>
                <div class="bk-sum-row"><span>Tanggal</span><span>{{ $selected->locale('id')->translatedFormat('D, d M Y') }}</span></div>
                <div class="bk-sum-row"><span>Coach</span><span>{{ \App\Models\Coach::chatName() }}</span></div>
                <div class="bk-sum-row"><span>Durasi</span><span id="durationText">-</span></div>

                <div class="bk-times" id="timeFields">
                    <div class="fw-field">
                        <label for="start_time">Jam mulai</label>
                        <select name="start_time" id="start_time" required>
                            <option value="">Pilih</option>
                            @foreach($slotMap as $slot)
                                <option value="{{ $slot['start'] }}" {{ $slot['status'] !== 'available' ? 'disabled' : '' }} {{ $oldStart === $slot['start'] ? 'selected' : '' }}>
                                    {{ $slot['start'] }}@if($slot['status'] !== 'available') — {{ $statusLabels[$slot['status']] }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fw-field">
                        <label for="end_time">Jam selesai</label>
                        <select name="end_time" id="end_time" required disabled>
                            <option value="">Pilih</option>
                        </select>
                    </div>
                </div>

                <div class="course-box" id="courseBox" hidden>
                    <div><strong>{{ $courseStart }} – {{ $courseEnd }}</strong> <span>· Sesi Course Lesson</span></div>
                    @if($courseAvailable)
                        <em class="course-ok">✓ Tersedia di tanggal ini</em>
                    @else
                        <em class="course-no">Tidak tersedia. {{ $courseReason }} Pilih tanggal lain.</em>
                    @endif
                    @if(\App\Support\BookingRules::courseNote())
                        <small class="course-note">* {{ \App\Support\BookingRules::courseNote() }}</small>
                    @endif
                    <input type="hidden" name="start_time" value="{{ $courseStart }}" id="courseStartInput" disabled>
                    <input type="hidden" name="end_time" value="{{ $courseEnd }}" id="courseEndInput" disabled>
                </div>

                <div class="bk-sum-total"><span class="fw-muted">Total</span><strong id="priceText">-</strong></div>

                <button type="submit" class="fw-btn block" id="bookButton" disabled>Pilih jam lesson dulu</button>

                <p class="bk-note">
                    Booking baru berstatus <b>Pending</b> sampai pembayaran selesai & disetujui. Mohon hadir 15–30 menit sebelum lesson dimulai.
                </p>
            </form>

            @if (Route::has('jadwal'))
                <a href="{{ route('jadwal') }}" class="fw-card bk-mine">
                    <span class="fw-av sm">{!! $Icons::svg('cal-check') !!}</span>
                    <span style="flex:1"><strong style="display:block;font-size:14px">Jadwal Saya</strong><small class="fw-muted">{{ $upcomingCount }} booking aktif</small></span>
                    {!! $Icons::svg('chev', 'fw-chev') !!}
                </a>
            @endif
        </aside>
    </div>

    {{-- Bar ringkas di HP --}}
    <div class="bk-bar" id="bkBar">
        <div><small id="bkBarInfo">Pilih jam lesson</small><strong id="bkBarPrice">-</strong></div>
        <button type="button" class="fw-btn lime" id="bkBarBtn">Pesan</button>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const slots = @json($slotMap);

    const startSelect  = document.getElementById('start_time');
    const endSelect    = document.getElementById('end_time');
    const durationText = document.getElementById('durationText');
    const bookButton   = document.getElementById('bookButton');
    const form         = document.getElementById('bookingForm');
    const slotButtons  = document.querySelectorAll('.available-slot');

    const initialEnd = @json($oldEnd);

    /* Jenis lesson */
    const config = {
        pricePerHour: {{ (int) $pricePerHour }},
        coursePrice: {{ (int) $coursePrice }},
        courseStart: @json($courseStart),
        courseEnd: @json($courseEnd),
        courseAvailable: @json($courseAvailable),
        hasLocations: @json($locations->isNotEmpty()),
    };

    const timeFields      = document.getElementById('timeFields');
    const courseBox       = document.getElementById('courseBox');
    const courseStartIn   = document.getElementById('courseStartInput');
    const courseEndIn     = document.getElementById('courseEndInput');
    const locationStep    = document.getElementById('locationStep');
    const courseLocation  = document.getElementById('courseLocation');
    const priceText       = document.getElementById('priceText');
    const timeStepTitle   = document.getElementById('timeStepTitle');
    const courseVenue     = document.getElementById('courseVenue');

    const lessonType = () => (document.querySelector('input[name="lesson_type"]:checked') || {}).value || 'driving';
    const locationValue = () => (document.querySelector('input[name="location_id"]:checked') || {}).value || '';
    const isCourse = () => lessonType() === 'course';
    const rupiah = (n) => 'Rp' + Number(n).toLocaleString('id-ID');

    const indexByStart = {};
    slots.forEach((slot, i) => { indexByStart[slot.start] = i; });


    /* Jam selesai yang valid: slot berurutan yang semuanya tersedia */
    function validEnds(start) {
        const ends = [];
        let i = indexByStart[start];

        if (i === undefined) return ends;

        for (; i < slots.length; i++) {
            if (slots[i].status !== 'available') break;
            ends.push(slots[i].end);
        }

        return ends;
    }

    function fillEnds(start, keep) {
        endSelect.innerHTML = '<option value="">Pilih jam selesai</option>';

        if (!start) {
            endSelect.disabled = true;
            return;
        }

        endSelect.disabled = false;

        const ends = validEnds(start);

        ends.forEach(function (end) {
            const option = document.createElement('option');
            option.value = end;
            option.textContent = end;
            endSelect.appendChild(option);
        });

        if (keep && ends.includes(keep)) {
            endSelect.value = keep;
        }
    }

    function toMinutes(value) {
        const parts = value.split(':').map(Number);
        return parts[0] * 60 + parts[1];
    }

    function highlight() {
        const start = isCourse() ? config.courseStart : startSelect.value;
        const end   = isCourse() ? config.courseEnd : endSelect.value;

        slotButtons.forEach(function (button) {
            let on = false;

            if (start && end) {
                on = toMinutes(button.dataset.start) >= toMinutes(start)
                  && toMinutes(button.dataset.end) <= toMinutes(end);
            }

            button.classList.toggle('selected', on);
        });
    }

    function durationLabel(start, end) {
        const duration = toMinutes(end) - toMinutes(start);
        const hours    = Math.floor(duration / 60);
        const minutes  = duration % 60;

        let text = '';
        if (hours > 0) text += hours + ' Jam';
        if (minutes > 0) text += (text ? ' ' : '') + minutes + ' Menit';
        return text;
    }

    function updateSummaryCore() {
        highlight();

        /* Course Lesson: jam & harga tetap */
        if (isCourse()) {
            const venueFilled = courseVenue.value.trim() !== '';

            durationText.textContent = durationLabel(config.courseStart, config.courseEnd) + ' (' + config.courseStart + '–' + config.courseEnd + ')';
            priceText.textContent = rupiah(config.coursePrice);
            bookButton.disabled = !config.courseAvailable || !venueFilled;
            bookButton.textContent = !config.courseAvailable
                ? 'Course Lesson tidak tersedia di tanggal ini'
                : (venueFilled ? 'Pesan Course Lesson' : 'Isi lapangan golf dulu');
            return;
        }

        const start = startSelect.value;
        const end   = endSelect.value;

        if (!locationValue()) {
            durationText.textContent = start && end ? durationLabel(start, end) : '-';
            priceText.textContent = '-';
            bookButton.disabled = true;
            bookButton.textContent = config.hasLocations ? 'Pilih lapangan dulu' : 'Lapangan belum tersedia';
            return;
        }

        if (!start || !end) {
            durationText.textContent = '-';
            priceText.textContent = '-';
            bookButton.disabled = true;
            bookButton.textContent = 'Pilih jam lesson dulu';
            return;
        }

        const minutes = toMinutes(end) - toMinutes(start);

        durationText.textContent = durationLabel(start, end);
        priceText.textContent = rupiah(Math.round(minutes / 60 * config.pricePerHour));
        bookButton.disabled = false;
        bookButton.textContent = 'Pesan Lesson Ini';
    }

    /* Ringkasan juga tampil di bar bawah HP */
    const bar = document.getElementById('bkBar');
    const barInfo = document.getElementById('bkBarInfo');
    const barPrice = document.getElementById('bkBarPrice');
    function updateSummary() {
        updateSummaryCore();
        if (!bar) return;
        const ready = !bookButton.disabled;
        const start = isCourse() ? config.courseStart : startSelect.value;
        const end = isCourse() ? config.courseEnd : endSelect.value;
        barPrice.textContent = priceText.textContent;
        barInfo.textContent = (start && end) ? (start + ' – ' + end + ' · ' + durationText.textContent) : bookButton.textContent;
        bar.classList.toggle('show', !!(start && (end || isCourse())));
        document.body.classList.toggle('bk-has-bar', bar.classList.contains('show'));
        document.getElementById('bkBarBtn').disabled = !ready;
    }
    document.getElementById('bkBarBtn')?.addEventListener('click', function () {
        if (!bookButton.disabled) { form.requestSubmit ? form.requestSubmit(bookButton) : bookButton.click(); }
        else { form.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    });

    /* Pilih tanggal lain dari kalender */
    document.getElementById('bkPickDate')?.addEventListener('change', function () {
        if (!this.value) return;
        const url = new URL(window.location.href);
        url.searchParams.set('date', this.value);
        url.searchParams.set('type', lessonType());
        if (locationValue()) url.searchParams.set('location', locationValue());
        window.location.href = url.toString();
    });
    document.querySelector('.bk-day.selected')?.scrollIntoView({ block: 'nearest', inline: 'center' });

    /* Tampilan sesuai jenis lesson */
    function syncLessonType() {
        const course = isCourse();

        timeFields.hidden = course;
        courseBox.hidden = !course;
        startSelect.disabled = course;
        endSelect.disabled = course || !startSelect.value;
        courseStartIn.disabled = !course;
        courseEndIn.disabled = !course;
        if (locationStep) locationStep.hidden = course;
        courseLocation.hidden = !course;
        courseVenue.disabled = !course;
        courseVenue.required = course;
        timeStepTitle.textContent = course ? 'Jam course' : 'Pilih jam';
        document.querySelector('.bk-slots').style.opacity = course ? '.55' : '';

        updateLinks();
        updateSummary();
    }

    /* Link kalender & bulan membawa pilihan jenis lesson & lapangan */
    function updateLinks() {
        document.querySelectorAll('a.calendar-day').forEach(function (link) {
            const url = new URL(link.href, window.location.origin);
            url.searchParams.set('type', lessonType());
            if (locationValue()) url.searchParams.set('location', locationValue());
            link.href = url.toString();
        });
    }

    document.querySelectorAll('input[name="lesson_type"], input[name="location_id"]').forEach(function (input) {
        input.addEventListener('change', syncLessonType);
    });

    courseVenue.addEventListener('input', updateSummary);


    startSelect.addEventListener('change', function () {
        fillEnds(this.value, null);
        updateSummary();
    });

    endSelect.addEventListener('change', updateSummary);


    /* Klik slot: pilih satu slot, klik slot berikutnya untuk memperpanjang durasi */
    slotButtons.forEach(function (button) {
        button.addEventListener('click', function () {

            if (isCourse()) return;

            const start = startSelect.value;

            if (start &&
                toMinutes(this.dataset.start) >= toMinutes(start) &&
                validEnds(start).includes(this.dataset.end)) {

                endSelect.value = this.dataset.end;

            } else {

                startSelect.value = this.dataset.start;
                fillEnds(this.dataset.start, this.dataset.end);
            }

            updateSummary();

            if (window.matchMedia('(min-width: 1001px)').matches === false && !bar) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });


    form.addEventListener('submit', function (event) {
        if (isCourse()) {
            if (!config.courseAvailable || courseVenue.value.trim() === '') event.preventDefault();
            return;
        }

        if (!startSelect.value || !endSelect.value || !locationValue()) {
            event.preventDefault();
        }
    });


    /* Konfirmasi batalkan */
    document.querySelectorAll('.cancel-form').forEach(function (cancelForm) {
        cancelForm.addEventListener('submit', function (event) {
            const confirmed = confirm(
                'Yakin ingin membatalkan booking ini?\n\n' +
                'Slot tersebut akan kembali tersedia.'
            );

            if (!confirmed) event.preventDefault();
        });
    });


    /* Buka / tutup form ubah jadwal */
    document.querySelectorAll('.reschedule-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.getElementById(this.dataset.target);

            if (!target) return;

            document.querySelectorAll('.reschedule-box.active').forEach(function (box) {
                if (box !== target) box.classList.remove('active');
            });

            target.classList.toggle('active');
        });
    });

    document.querySelectorAll('.reschedule-cancel').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = document.getElementById(this.dataset.target);
            if (target) target.classList.remove('active');
        });
    });


    /* Konfirmasi ubah jadwal */
    document.querySelectorAll('.reschedule-form').forEach(function (rescheduleForm) {
        rescheduleForm.addEventListener('submit', function (event) {
            const confirmed = confirm(
                'Yakin ingin mengubah jadwal booking ini?\n\n' +
                'Booking lama akan diganti dengan jadwal baru dan ' +
                'statusnya kembali menjadi PENDING sampai Admin menyetujui.'
            );

            if (!confirmed) event.preventDefault();
        });
    });


    /* Kondisi awal (termasuk old input setelah validasi gagal) */
    fillEnds(startSelect.value, initialEnd);
    syncLessonType();

});
</script>


<script>
    /* Hitung mundur slot yang ditahan menunggu pembayaran */
    (function () {
        var holds = document.querySelectorAll('[data-hold-deadline]');
        if (!holds.length) return;

        var pad = function (n) { return String(n).padStart(2, '0'); };
        var reloading = false;

        function tick() {
            holds.forEach(function (el) {
                var left = Math.max(0, Math.floor((new Date(el.dataset.holdDeadline).getTime() - Date.now()) / 1000));
                var b = el.querySelector('b');

                if (left === 0) {
                    el.textContent = 'Waktu bayar habis';
                    el.classList.add('urgent');
                    if (!reloading) {
                        reloading = true;
                        setTimeout(function () { window.location.reload(); }, 2000);
                    }
                    return;
                }

                if (b) b.textContent = pad(Math.floor(left / 60)) + ':' + pad(left % 60);
                el.classList.toggle('urgent', left <= 300);
            });
        }

        tick();
        setInterval(tick, 1000);
    })();
</script>
@endpush
