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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking | {{ \App\Support\Brand::name() }}</title>

    <style>
        :root {
            --lime: #b8ff00;
            --yellow: #ffc400;
            --red: #ff5c5c;
            --blue: #5ca8ff;
            --text: #f4f7f4;
            --muted: #8a9690;
            --border: rgba(184,255,0,.16);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100%; }

        body {
            color: var(--text);
            background:
                linear-gradient(rgba(1,12,9,.78), rgba(1,12,9,.90)),
                url('{{ \App\Support\Brand::background('public') }}') center / cover fixed no-repeat;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: hidden;
        }

        a { color: inherit; text-decoration: none; }
        button, select, input { font: inherit; }

        /* NAVBAR */
        .navbar {
            min-height: 64px; padding: 0 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 15px;
            background: rgba(2,15,11,.92); border-bottom: 1px solid rgba(184,255,0,.10);
            position: sticky; top: 0; z-index: 100;
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        }
        .brand { font-size: 20px; font-weight: 800; letter-spacing: -.6px; white-space: nowrap; }
        .brand span { color: var(--lime); }
        .nav-right { display: flex; align-items: center; justify-content: flex-end; gap: 6px; flex-wrap: wrap; }
        .nav-link { padding: 8px 11px; border-radius: 8px; color: #cbd2cf; font-size: 13px; font-weight: 600; transition: .18s ease; }
        .nav-link:hover { color: var(--lime); background: rgba(184,255,0,.06); }
        .booking-nav { padding: 9px 18px; border-radius: 9px; background: var(--lime); color: #071000; font-size: 12px; font-weight: 800; letter-spacing: .4px; }
        .user-name {
            max-width: 130px; padding: 8px 12px; color: #cbd2cf; background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07); border-radius: 9px; font-size: 12px; font-weight: 600;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .logout-button { padding: 8px 10px; border: 0; background: transparent; color: #aab3af; font-size: 12px; font-weight: 600; cursor: pointer; }
        .logout-button:hover { color: #fff; }

        /* PAGE */
        .page { width: min(1400px, calc(100% - 40px)); margin: auto; padding: 32px 0 36px; }
        .page-heading { margin-bottom: 20px; }
        .eyebrow { margin-bottom: 6px; color: var(--lime); font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; }
        .page-heading h1 { font-size: 36px; line-height: 1.1; font-weight: 800; letter-spacing: -1.2px; }
        .page-heading p { max-width: 760px; margin-top: 8px; color: var(--muted); font-size: 14px; line-height: 1.55; }

        /* ALERT */
        .alert { margin-bottom: 14px; padding: 12px 15px; border-radius: 10px; font-size: 13px; line-height: 1.45; }
        .alert-success { color: #d9ff79; background: rgba(67,105,12,.22); border: 1px solid rgba(184,255,0,.25); }
        .alert-error { color: #ffb4b4; background: rgba(80,15,15,.35); border: 1px solid rgba(255,92,92,.35); }
        .alert-error strong { display: block; margin-bottom: 3px; }
        .alert-error ul { margin-left: 18px; }

        /* LAYOUT */
        .booking-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
        .panel {
            padding: 22px; border: 1px solid var(--border); border-radius: 16px;
            background: linear-gradient(145deg, rgba(7,30,23,.95), rgba(2,18,13,.94));
            box-shadow: 0 15px 45px rgba(0,0,0,.22), inset 0 1px 0 rgba(255,255,255,.02);
        }
        .section-title { display: flex; align-items: center; gap: 11px; margin-bottom: 18px; font-size: 20px; font-weight: 800; letter-spacing: -.4px; }
        .section-title svg { width: 22px; height: 22px; flex: 0 0 22px; stroke: var(--lime); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .section-title .sub { margin-left: auto; color: var(--muted); font-size: 13px; font-weight: 600; letter-spacing: 0; }

        /* CALENDAR */
        .calendar-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .calendar-month { font-size: 24px; font-weight: 800; letter-spacing: -.6px; }
        .month-actions { display: flex; gap: 8px; }
        .month-button {
            width: 40px; height: 40px; display: grid; place-items: center;
            border: 1px solid rgba(255,255,255,.10); border-radius: 10px;
            background: rgba(255,255,255,.04); color: #fff; font-size: 21px; cursor: pointer;
        }
        .month-button:hover:not(:disabled) { color: var(--lime); border-color: rgba(184,255,0,.35); }
        .month-button:disabled { opacity: .22; cursor: not-allowed; }
        .weekdays, .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
        .weekdays { margin-bottom: 6px; }
        .weekday { text-align: center; color: #69736f; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .calendar-day {
            min-height: 44px; display: grid; place-items: center;
            border: 1px solid transparent; border-radius: 9px; background: rgba(255,255,255,.035);
            color: #d3dad7; font-size: 14px; font-weight: 700; transition: .15s ease;
        }
        a.calendar-day:hover { background: rgba(184,255,0,.07); border-color: rgba(184,255,0,.30); }
        .calendar-day.empty { visibility: hidden; }
        .calendar-day.disabled { color: #48534e; background: rgba(255,255,255,.015); cursor: not-allowed; }
        .calendar-day.selected { color: #071000; background: var(--lime); border-color: var(--lime); font-weight: 800; }
        .calendar-day.today { border-color: rgba(184,255,0,.45); }

        /* FORM */
        .divider { height: 1px; margin: 18px 0; background: rgba(255,255,255,.07); }
        .form-title { margin-bottom: 10px; color: #cdd5d1; font-size: 13px; font-weight: 800; letter-spacing: .9px; text-transform: uppercase; }
        .time-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .field label { display: block; margin-bottom: 6px; color: var(--muted); font-size: 12px; font-weight: 600; }
        .field select {
            width: 100%; height: 46px; padding: 0 12px; color: #fff; background: rgba(0,0,0,.25);
            border: 1px solid rgba(255,255,255,.10); border-radius: 9px; outline: none; font-size: 14px; font-weight: 600;
        }
        .field select:focus { border-color: rgba(184,255,0,.5); }
        .field select:disabled { opacity: .5; cursor: not-allowed; }
        .field select option { color: #fff; background: #081812; }
        .field select option:disabled { color: #55605b; }
        .duration-box {
            margin-top: 12px; padding: 11px 14px; color: var(--muted);
            background: rgba(184,255,0,.045); border: 1px solid rgba(184,255,0,.08); border-radius: 9px; font-size: 13px;
        }
        .duration-box strong { color: var(--lime); font-size: 14px; font-weight: 800; }
        .book-button {
            width: 100%; height: 52px; margin-top: 12px; border: 0; border-radius: 10px;
            background: var(--lime); color: #071000; font-size: 14px; font-weight: 800; letter-spacing: .4px;
            cursor: pointer; transition: .18s ease;
        }
        .book-button:hover:not(:disabled) { background: #d0ff45; transform: translateY(-1px); }
        .book-button:disabled { background: rgba(255,255,255,.06); color: #6a756f; border: 1px dashed rgba(255,255,255,.14); cursor: not-allowed; }
        .booking-note { margin-top: 12px; color: #7d8883; font-size: 12px; line-height: 1.5; text-align: center; }

        /* SLOT GRID */
        .legend { display: flex; flex-wrap: wrap; gap: 8px 16px; margin-bottom: 14px; }
        .legend-item { display: flex; align-items: center; gap: 7px; color: #b6bfba; font-size: 12px; font-weight: 600; }
        .legend-dot { width: 9px; height: 9px; border-radius: 50%; }
        .legend-dot.available { background: var(--lime); }
        .legend-dot.pending { background: var(--yellow); }
        .legend-dot.booked { background: var(--red); }
        .legend-dot.mine { background: var(--blue); }
        .legend-dot.past { background: #55605b; }

        .slot-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .slot {
            min-height: 54px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;
            padding: 7px 8px; border-radius: 10px; border: 1px solid transparent;
            font-size: 13px; font-weight: 700; text-align: center; white-space: nowrap; transition: .15s ease;
        }
        .slot small { font-size: 10px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; opacity: .85; }

        .slot-available { color: var(--lime); background: rgba(184,255,0,.05); border-color: rgba(184,255,0,.30); cursor: pointer; }
        .slot-available:hover { background: rgba(184,255,0,.16); }
        .slot-available.selected { color: #071000; background: var(--lime); border-color: var(--lime); }

        .slot-pending { color: #ffd45c; background: rgba(255,196,0,.06); border-color: rgba(255,196,0,.28); cursor: not-allowed; }
        .slot-booked  { color: #ff9a9a; background: rgba(255,92,92,.07); border-color: rgba(255,92,92,.28); cursor: not-allowed; }
        .slot-mine    { color: #a9d0ff; background: rgba(92,168,255,.09); border-color: rgba(92,168,255,.40); cursor: not-allowed; }
        .slot-past    { color: #55605b; background: rgba(255,255,255,.02); border-color: rgba(255,255,255,.05); cursor: not-allowed; text-decoration: line-through; }
        .slot-past small { text-decoration: none; }
        .slot-blocked { color: #ff9aa6; background: repeating-linear-gradient(135deg, rgba(216,35,61,.10) 0 6px, rgba(216,35,61,.04) 6px 12px); border-color: rgba(216,35,61,.35); cursor: not-allowed; }
        .legend-dot.blocked { background: #d8233d; }

        /* YOUR BOOKING */
        .your-booking { margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.07); }
        .your-booking-title { display: flex; align-items: center; gap: 9px; margin-bottom: 12px; font-size: 16px; font-weight: 800; }
        .your-booking-title svg { width: 19px; height: 19px; stroke: var(--lime); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
        .empty-status { padding: 12px 14px; color: #7d8883; border: 1px dashed rgba(255,255,255,.10); border-radius: 9px; font-size: 13px; }

        .booking-card { margin-bottom: 10px; padding: 14px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 11px; }
        .booking-card:last-child { margin-bottom: 0; }
        .booking-card-grid { display: grid; grid-template-columns: 1.3fr 1fr 1fr .9fr; gap: 10px; }
        .booking-meta-label { margin-bottom: 4px; color: #7d8883; font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; }
        .booking-meta-value { color: #edf2ef; font-size: 13px; font-weight: 700; }
        .booking-status { display: flex; align-items: center; gap: 7px; margin-top: 11px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,.06); font-size: 12px; font-weight: 700; }
        .booking-status.pending { color: #ffd45c; }
        .booking-status.booked { color: var(--lime); }
        .booking-status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .booking-status.pending .booking-status-dot { background: var(--yellow); }
        .booking-status.booked .booking-status-dot { background: var(--lime); }

        .booking-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px; }
        .action-button { width: 100%; min-height: 40px; border-radius: 8px; font-size: 11px; font-weight: 800; letter-spacing: .3px; cursor: pointer; transition: .16s ease; }
        .cancel-button { color: #ff9c9c; background: rgba(255,92,92,.05); border: 1px solid rgba(255,92,92,.25); }
        .cancel-button:hover { color: #fff; background: rgba(255,92,92,.16); border-color: rgba(255,92,92,.45); }
        .reschedule-button { color: #b9d9ff; background: rgba(92,168,255,.05); border: 1px solid rgba(92,168,255,.25); }
        .reschedule-button:hover { color: #fff; background: rgba(92,168,255,.14); border-color: rgba(92,168,255,.45); }
        .action-locked { margin-top: 10px; padding-top: 9px; color: #7d8883; border-top: 1px solid rgba(255,255,255,.06); font-size: 11px; line-height: 1.5; }

        .reschedule-box { display: none; margin-top: 12px; padding: 13px; background: rgba(0,0,0,.20); border: 1px solid rgba(92,168,255,.18); border-radius: 10px; }
        .reschedule-box.active { display: block; }
        .reschedule-title { margin-bottom: 10px; color: #b9d9ff; font-size: 11px; font-weight: 800; letter-spacing: .7px; text-transform: uppercase; }
        .reschedule-grid { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 8px; }
        .reschedule-field label { display: block; margin-bottom: 4px; color: #7d8883; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .reschedule-field input { width: 100%; height: 40px; padding: 0 8px; color: #fff; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.09); border-radius: 7px; outline: none; font-size: 12px; font-weight: 600; }
        .reschedule-field input:focus { border-color: rgba(92,168,255,.5); }
        .reschedule-submit { width: 100%; height: 40px; margin-top: 9px; border: 0; border-radius: 7px; color: #06111c; background: #8ec7ff; font-size: 11px; font-weight: 800; cursor: pointer; }
        .reschedule-submit:hover { background: #b9dcff; }
        .reschedule-cancel { width: 100%; height: 36px; margin-top: 6px; border: 1px solid rgba(255,255,255,.08); border-radius: 7px; color: #89948f; background: transparent; font-size: 11px; font-weight: 700; cursor: pointer; }
        .reschedule-cancel:hover { color: #fff; }

        /* RESPONSIVE */
        @media (max-width: 1200px) { .nav-link { padding-left: 8px; padding-right: 8px; } }
        @media (max-width: 1050px) {
            .booking-layout { grid-template-columns: 1fr; }
            .slot-list { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        @media (max-width: 900px) { .nav-link { display: none; } }
        @media (max-width: 760px) {
            .navbar { padding: 10px 14px; }
            .page { width: calc(100% - 20px); padding-top: 22px; }
            .page-heading h1 { font-size: 28px; }
            .slot-list { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .booking-card-grid { grid-template-columns: 1fr 1fr; }
            .reschedule-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .brand { font-size: 16px; }
            .booking-nav { padding: 8px 12px; }
            .logout-button { display: none; }
            .panel { padding: 16px; }
            .time-form-grid { grid-template-columns: 1fr; }
            .calendar-day { min-height: 38px; font-size: 12px; }
            .slot-list { grid-template-columns: 1fr 1fr; }
            .booking-actions { grid-template-columns: 1fr; }
        }
    
        /* ---------- Jenis lesson & lapangan ---------- */
        .step-label { display: flex; align-items: center; gap: 9px; margin: 4px 0 10px; color: #cdd5d1; font-size: 13px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; }
        .step-num { width: 22px; height: 22px; flex: 0 0 22px; display: grid; place-items: center; border-radius: 50%; background: var(--lime); color: #071000; font-size: 11px; font-weight: 900; letter-spacing: 0; }

        .lesson-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 18px; }
        .lesson-option input, .location-option input { position: absolute; opacity: 0; pointer-events: none; }
        .lesson-card {
            height: 100%; display: flex; flex-direction: column; gap: 6px; padding: 14px 15px;
            border: 1px solid rgba(255,255,255,.10); border-radius: 13px; background: rgba(255,255,255,.03);
            cursor: pointer; transition: border-color .15s ease, background .15s ease;
        }
        .lesson-card strong { font-size: 14px; font-weight: 800; }
        .lesson-price { color: var(--lime); font-size: 19px; font-weight: 900; }
        .lesson-price small { color: var(--muted); font-size: 11px; font-weight: 700; }
        .lesson-desc { color: var(--muted); font-size: 12px; line-height: 1.45; }
        .lesson-option:hover .lesson-card { border-color: rgba(184,255,0,.35); }
        .lesson-option input:checked + .lesson-card { border-color: var(--lime); background: rgba(184,255,0,.08); box-shadow: inset 0 0 0 1px var(--lime); }
        .lesson-option input:focus-visible + .lesson-card { outline: 2px solid var(--lime); outline-offset: 2px; }

        .location-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 4px; }
        .location-card {
            display: flex; align-items: center; gap: 11px; padding: 12px 14px;
            border: 1px solid rgba(255,255,255,.10); border-radius: 12px; background: rgba(255,255,255,.03); cursor: pointer;
        }
        .location-card strong { display: block; font-size: 13px; font-weight: 800; }
        .location-card small { display: block; margin-top: 2px; color: var(--muted); font-size: 11px; }
        .location-pin { color: var(--lime); font-size: 16px; }
        .location-option:hover .location-card { border-color: rgba(184,255,0,.35); }
        .location-option input:checked + .location-card { border-color: var(--lime); background: rgba(184,255,0,.08); box-shadow: inset 0 0 0 1px var(--lime); }
        .location-option input:focus-visible + .location-card { outline: 2px solid var(--lime); outline-offset: 2px; }
        .venue-input {
            width: 100%; padding: 13px 15px; border: 1px solid rgba(255,255,255,.12); border-radius: 12px;
            background: rgba(0,0,0,.25); color: #fff; font-size: 14px; outline: none;
        }
        .venue-input:focus { border-color: rgba(184,255,0,.55); }
        .venue-input::placeholder { color: #6a756f; }
        .venue-hint { display: block; margin-top: 7px; color: var(--muted); font-size: 12px; line-height: 1.45; }

        .course-box {
            display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
            padding: 14px 16px; border: 1px solid rgba(184,255,0,.35); border-radius: 12px; background: rgba(184,255,0,.06);
        }
        .course-box strong { display: block; color: var(--lime); font-size: 20px; font-weight: 900; }
        .course-box span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }
        .course-box em { font-style: normal; font-size: 12px; font-weight: 800; }
        .course-ok { color: var(--lime); }
        .course-no { color: #ff9a9a; max-width: 260px; }
        .course-box.unavailable { border-color: rgba(255,92,92,.35); background: rgba(255,92,92,.06); }
        .lesson-note { display: block; margin-top: 4px; color: #ffd45c; font-size: 11px; font-weight: 700; }
        .course-note { flex-basis: 100%; color: #ffd45c; font-size: 12px; font-weight: 700; }

        .duration-box { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }

        .booking-place { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 2px 0 10px; }
        .booking-place-type { padding: 4px 10px; border-radius: 999px; background: rgba(184,255,0,.12); color: var(--lime); font-size: 11px; font-weight: 800; }
        .booking-place-type.course { background: rgba(92,168,255,.14); color: #a9d0ff; }
        .booking-place-name { color: #cdd5d1; font-size: 12px; font-weight: 700; }
        .reschedule-fixed { padding: 10px 12px; border-radius: 9px; background: rgba(255,255,255,.04); color: #cdd5d1; font-size: 13px; font-weight: 700; }

        .locked-help { display: flex; flex-direction: column; gap: 10px; }
        .locked-help strong { display: block; margin-top: 4px; color: #ffd45c; font-weight: 700; }
        .contact-admin {
            align-self: flex-start; display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 16px; border-radius: 10px; background: #25d366; color: #04210f;
            font-size: 13px; font-weight: 800;
        }
        .contact-admin:hover { background: #3fe07a; }
        .slot-hold {
            display: inline-flex; align-items: center; gap: 4px; margin-top: 3px;
            padding: 2px 8px; border-radius: 999px; background: rgba(255, 196, 0, .12);
            color: #ffd45c; font-size: 10px; font-weight: 800; letter-spacing: .2px;
        }
        .slot-hold b { font-variant-numeric: tabular-nums; }
        .slot-hold.urgent { background: rgba(255, 92, 92, .14); color: #ff9a9a; }

        [hidden] { display: none !important; }
    </style>
    @include('partials.brand-head')
</head>

<body>

{{-- ============================ NAVBAR ============================ --}}
@include('partials.site-navbar')


{{-- ============================== PAGE ============================== --}}
<main class="page">

    <div class="page-heading">
        <div class="eyebrow">Golf Booking Lesson</div>
        <h1>Pesan Lesson Anda</h1>
        <p>
            Pilih tanggal dan jam lesson yang tersedia. Slot yang berstatus Pending
            atau Booked tetap ditampilkan, tetapi tidak dapat dipilih.
        </p>
    </div>

    @if(session('booking_success'))
        <div class="alert alert-success">{{ session('booking_success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <strong>Booking belum dapat diproses:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <div class="booking-layout">

        {{-- ========================= KIRI ========================= --}}
        <section class="panel">

            <div class="section-title">
                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg>
                Booking Lesson
            </div>

            {{-- ---------- 1. TANGGAL ---------- --}}
            <div class="step-label"><span class="step-num">1</span> Pilih tanggal</div>

            <div class="calendar-top">
                <div class="calendar-month">{{ $selected->format('F Y') }}</div>

                <div class="month-actions">
                    @if($previousAllowed)
                        <a href="{{ route('booking', $linkFor($previousMonth->format('Y-m-d'))) }}"
                           class="month-button" aria-label="Bulan sebelumnya">‹</a>
                    @else
                        <button type="button" class="month-button" disabled aria-label="Bulan sebelumnya">‹</button>
                    @endif

                    <a href="{{ route('booking', $linkFor($nextMonth->format('Y-m-d'))) }}"
                       class="month-button" aria-label="Bulan berikutnya">›</a>
                </div>
            </div>

            <div class="weekdays">
                <div class="weekday">Min</div>
                <div class="weekday">Sen</div>
                <div class="weekday">Sel</div>
                <div class="weekday">Rab</div>
                <div class="weekday">Kam</div>
                <div class="weekday">Jum</div>
                <div class="weekday">Sab</div>
            </div>

            <div class="calendar-grid">

                @for($i = 0; $i < $startWeekday; $i++)
                    <div class="calendar-day empty"></div>
                @endfor

                @for($day = 1; $day <= $monthEnd->day; $day++)
                    @php
                        $date         = $monthStart->copy()->day($day);
                        $dateString   = $date->format('Y-m-d');
                        $isPast       = $date->lt($today);
                        $isSelected   = $dateString === $selectedDateString;
                        $isTodayCell  = $date->isSameDay($today);
                    @endphp

                    @if($isPast)
                        <div class="calendar-day disabled {{ $isTodayCell ? 'today' : '' }}">{{ $day }}</div>
                    @else
                        <a href="{{ route('booking', $linkFor($dateString)) }}"
                           class="calendar-day {{ $isSelected ? 'selected' : '' }} {{ $isTodayCell ? 'today' : '' }}">
                            {{ $day }}
                        </a>
                    @endif
                @endfor

            </div>

            <div class="divider"></div>

            {{-- ---------- 2. JENIS LESSON ---------- --}}
            <div class="step-label"><span class="step-num">2</span> Jenis lesson</div>

            <div class="lesson-options">
                @foreach($lessonTypes as $typeKey => $typeLabel)
                    <label class="lesson-option">
                        <input type="radio" name="lesson_type" value="{{ $typeKey }}" form="bookingForm"
                               {{ $lessonType === $typeKey ? 'checked' : '' }}>
                        <span class="lesson-card">
                            <strong>{{ $typeLabel }}</strong>
                            <span class="lesson-price">
                                {{ $typeKey === 'course' ? $rp($coursePrice) : $rp($pricePerHour) }}
                                <small>{{ $typeKey === 'course' ? '/ sesi' : '/ jam' }}</small>
                            </span>
                            <span class="lesson-desc">
                                @if($typeKey === 'course')
                                    Sesi tetap {{ $courseStart }} – {{ $courseEnd }} di lapangan golf
                                    @if(\App\Support\BookingRules::courseNote())
                                        <span class="lesson-note">* {{ \App\Support\BookingRules::courseNote() }}</span>
                                    @endif
                                @else
                                    Latihan di driving range, per jam bersama coach
                                @endif
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            {{-- ---------- 3. LAPANGAN ---------- --}}
            @if($locations->isEmpty())
                <div id="locationStep" class="course-box unavailable" style="margin-bottom: 4px">
                    <em class="course-no">Belum ada lapangan driving range yang aktif. Silakan hubungi admin.</em>
                </div>
            @else
                <div id="locationStep">
                    <div class="step-label"><span class="step-num">3</span> Pilih lapangan driving range</div>

                    <div class="location-options">
                        @foreach($locations as $location)
                            <label class="location-option">
                                <input type="radio" name="location_id" value="{{ $location->id }}" form="bookingForm"
                                       {{ $locationId === (string) $location->id ? 'checked' : '' }}>
                                <span class="location-card">
                                    <span class="location-pin">◉</span>
                                    <span>
                                        <strong>{{ $location->name }}</strong>
                                        @if($location->area)
                                            <small>{{ $location->area }}</small>
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div id="courseLocation" class="course-location" hidden>
                <div class="step-label"><span class="step-num">3</span> Lapangan golf pilihan Anda</div>
                <input type="text" name="course_venue" id="courseVenue" form="bookingForm"
                       class="venue-input" maxlength="150"
                       value="{{ old('course_venue') }}"
                       placeholder="Contoh: Padang Golf Pondok Indah">
                <small class="venue-hint">Tulis lapangan golf tempat Anda ingin Course Lesson. Admin akan mengonfirmasi ketersediaannya.</small>
            </div>

            <div class="divider"></div>

            <div class="step-label"><span class="step-num">4</span> <span id="timeStepTitle">Jam lesson</span></div>

            <form action="{{ route('booking.store') }}" method="POST" id="bookingForm">
                @csrf

                <input type="hidden" name="booking_date" value="{{ $selectedDateString }}">

                <div class="time-form-grid" id="timeFields">

                    <div class="field">
                        <label for="start_time">Jam Mulai</label>

                        <select name="start_time" id="start_time" required>
                            <option value="">Pilih jam mulai</option>

                            @foreach($slotMap as $slot)
                                <option value="{{ $slot['start'] }}"
                                        {{ $slot['status'] !== 'available' ? 'disabled' : '' }}
                                        {{ $oldStart === $slot['start'] ? 'selected' : '' }}>
                                    {{ $slot['start'] }}
                                    @if($slot['status'] !== 'available')
                                        — {{ $statusLabels[$slot['status']] }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="end_time">Jam Selesai</label>

                        <select name="end_time" id="end_time" required disabled>
                            <option value="">Pilih jam selesai</option>
                        </select>
                    </div>

                </div>

                <div class="course-box {{ $courseAvailable ? '' : 'unavailable' }}" id="courseBox" hidden>
                    <div>
                        <strong>{{ $courseStart }} – {{ $courseEnd }}</strong>
                        <span>Sesi Course Lesson · {{ $selected->locale('id')->translatedFormat('l, d M Y') }}</span>
                    </div>
                    @if($courseAvailable)
                        <em class="course-ok">✓ Tersedia</em>
                    @else
                        <em class="course-no">Tidak tersedia. {{ $courseReason }} Pilih tanggal lain.</em>
                    @endif
                    @if(\App\Support\BookingRules::courseNote())
                        <small class="course-note">* {{ \App\Support\BookingRules::courseNote() }}</small>
                    @endif
                    <input type="hidden" name="start_time" value="{{ $courseStart }}" id="courseStartInput" disabled>
                    <input type="hidden" name="end_time" value="{{ $courseEnd }}" id="courseEndInput" disabled>
                </div>

                <div class="duration-box">
                    <span>Durasi: <strong id="durationText">-</strong></span>
                    <span>Total: <strong id="priceText">-</strong></span>
                </div>

                <button type="submit" class="book-button" id="bookButton" disabled>
                    Pilih jam lesson dulu
                </button>

                <div class="booking-note">
                    Diharapkan hadir 30 menit sebelum lesson dimulai.
                    Booking baru berstatus <strong>Pending</strong> sampai disetujui Admin.
                    Jadwal coach berlaku untuk semua lapangan.
                </div>

            </form>

        </section>


        {{-- ========================= KANAN ========================= --}}
        <section class="panel">

            <div class="section-title">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                Jadwal Lesson
                <span class="sub">{{ $selected->locale('id')->translatedFormat('l, d F Y') }}</span>
            </div>

            <div class="legend">
                <span class="legend-item"><span class="legend-dot available"></span>Tersedia ({{ $counts['available'] }})</span>
                <span class="legend-item"><span class="legend-dot pending"></span>Pending ({{ $counts['pending'] }})</span>
                <span class="legend-item"><span class="legend-dot booked"></span>Booked ({{ $counts['booked'] }})</span>
                @if($counts['blocked'] > 0)
                    <span class="legend-item"><span class="legend-dot blocked"></span>Ditutup ({{ $counts['blocked'] }})</span>
                @endif
                <span class="legend-item"><span class="legend-dot mine"></span>Booking Anda ({{ $counts['mine'] }})</span>
                @if($selectedIsToday)
                    <span class="legend-item"><span class="legend-dot past"></span>Lewat ({{ $counts['past'] }})</span>
                @endif
            </div>

            <div class="slot-list">
                @foreach($slotMap as $slot)
                    @if($slot['status'] === 'available')
                        <button type="button"
                                class="slot slot-available available-slot"
                                data-start="{{ $slot['start'] }}"
                                data-end="{{ $slot['end'] }}">
                            {{ $slot['start'] }} - {{ $slot['end'] }}
                        </button>
                    @else
                        <div class="slot slot-{{ $slot['status'] }}">
                            {{ $slot['start'] }} - {{ $slot['end'] }}
                            <small>{{ $statusLabels[$slot['status']] }}</small>
                            @if(! empty($slot['hold']))
                                <span class="slot-hold" data-hold-deadline="{{ $slot['hold'] }}"
                                      title="{{ $slot['status'] === 'mine' ? 'Bayar sebelum waktu habis' : 'Tersedia lagi jika tidak dibayar' }}">
                                    {{ $slot['status'] === 'mine' ? 'Bayar' : 'Tersedia lagi' }} <b>--:--</b>
                                </span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>


            {{-- ===================== BOOKING ANDA ===================== --}}
            <div class="your-booking">

                <div class="your-booking-title">
                    <svg viewBox="0 0 24 24"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/></svg>
                    Booking Anda
                </div>

                @forelse($yourBookings as $booking)

                    @php
                        $yourStart   = Carbon::parse($booking->start_time);
                        $yourEnd     = Carbon::parse($booking->end_time);
                        $minutes     = $yourStart->diffInMinutes($yourEnd);
                        $hours       = intdiv($minutes, 60);
                        $remaining   = $minutes % 60;
                        $bookingDate = Carbon::parse($booking->booking_date);
                        $canModify   = $canModifyBooking($booking);
                    @endphp

                    <div class="booking-card">

                        <div class="booking-card-grid">
                            <div>
                                <div class="booking-meta-label">Tanggal</div>
                                <div class="booking-meta-value">{{ $bookingDate->format('d M Y') }}</div>
                            </div>

                            <div>
                                <div class="booking-meta-label">Mulai</div>
                                <div class="booking-meta-value">{{ $yourStart->format('H:i') }}</div>
                            </div>

                            <div>
                                <div class="booking-meta-label">Selesai</div>
                                <div class="booking-meta-value">{{ $yourEnd->format('H:i') }}</div>
                            </div>

                            <div>
                                <div class="booking-meta-label">Durasi</div>
                                <div class="booking-meta-value">
                                    @if($hours > 0){{ $hours }} Jam @endif
                                    @if($remaining > 0){{ $remaining }} Menit @endif
                                </div>
                            </div>
                        </div>

                        <div class="booking-place">
                            <span class="booking-place-type {{ $booking->isCourse() ? 'course' : '' }}">{{ $booking->lesson_label }}</span>
                            @if($booking->place_label)
                                <span class="booking-place-name">◉ {{ $booking->place_label }}</span>
                            @endif
                        </div>

                        <div class="booking-status {{ $booking->status === 'pending' ? 'pending' : 'booked' }}">
                            <span class="booking-status-dot"></span>

                            @if($booking->status === 'pending')
                                PENDING (Menunggu persetujuan Admin)
                            @else
                                BOOKED (Disetujui Admin)
                            @endif
                        </div>

                        @include('payments._booking-status', ['booking' => $booking])

                        @if($canModify)

                            <div class="booking-actions">
                                <form action="{{ route('booking.cancel', $booking) }}" method="POST" class="cancel-form">
                                    @csrf
                                    <button type="submit" class="action-button cancel-button">BATALKAN BOOKING</button>
                                </form>

                                <button type="button"
                                        class="action-button reschedule-button reschedule-toggle"
                                        data-target="reschedule-{{ $booking->id }}">
                                    UBAH JADWAL
                                </button>
                            </div>

                            <div class="reschedule-box" id="reschedule-{{ $booking->id }}">

                                <div class="reschedule-title">Ubah Jadwal Lesson</div>

                                <form action="{{ route('booking.reschedule', $booking) }}" method="POST" class="reschedule-form">
                                    @csrf

                                    <div class="reschedule-grid">
                                        <div class="reschedule-field">
                                            <label>Tanggal</label>
                                            <input type="date" name="booking_date"
                                                   min="{{ \App\Support\BookingRules::minRescheduleDate()->format('Y-m-d') }}"
                                                   value="{{ $bookingDate->format('Y-m-d') }}" required>
                                        </div>

                                        @if($booking->isCourse())
                                        <div class="reschedule-field" style="grid-column: span 2">
                                            <label>Jam</label>
                                            <div class="reschedule-fixed">{{ $courseStart }} – {{ $courseEnd }} (Course Lesson)</div>
                                        </div>
                                        @else
                                        <div class="reschedule-field">
                                            <label>Mulai</label>
                                            <input type="time" name="start_time"
                                                   value="{{ $yourStart->format('H:i') }}"
                                                   min="{{ \App\Support\BookingRules::openTime() }}" max="{{ \App\Support\BookingRules::closeTime() }}" step="{{ \App\Support\BookingRules::slotMinutes() * 60 }}" required>
                                        </div>

                                        <div class="reschedule-field">
                                            <label>Selesai</label>
                                            <input type="time" name="end_time"
                                                   value="{{ $yourEnd->format('H:i') }}"
                                                   min="{{ \App\Support\BookingRules::openTime() }}" max="{{ \App\Support\BookingRules::closeTime() }}" step="{{ \App\Support\BookingRules::slotMinutes() * 60 }}" required>
                                        </div>
                                        @endif
                                    </div>

                                    <button type="submit" class="reschedule-submit">AJUKAN JADWAL BARU</button>

                                    <button type="button" class="reschedule-cancel"
                                            data-target="reschedule-{{ $booking->id }}">
                                        TUTUP
                                    </button>
                                </form>

                            </div>

                            <div class="action-locked">
                                {{ \App\Support\BookingRules::cancelRuleText() }}
                            </div>

                        @else

                            @php
                                $waNumber = class_exists(\App\Models\ContactSetting::class)
                                    ? preg_replace('/\D+/', '', (string) \App\Models\ContactSetting::query()->value('whatsapp'))
                                    : '';
                                $waNumber = str_starts_with($waNumber, '0') ? '62' . substr($waNumber, 1) : $waNumber;
                                $waText   = 'Halo Admin, saya ' . auth()->user()->name . ' ingin mengubah booking '
                                    . $booking->lesson_label . ' tanggal ' . $bookingDate->locale('id')->translatedFormat('d M Y')
                                    . ' jam ' . $yourStart->format('H:i') . '–' . $yourEnd->format('H:i')
                                    . ($booking->place_label ? ' di ' . $booking->place_label : '')
                                    . '. Apakah ada jadwal lain yang tersedia?';
                            @endphp

                            <div class="action-locked locked-help">
                                <span>
                                    @if($bookingDate->isSameDay($today))
                                        Pembatalan dan perubahan jadwal sudah ditutup karena lesson berlangsung hari ini.
                                    @else
                                        Pembatalan dan perubahan jadwal sudah ditutup. {{ \App\Support\BookingRules::cancelRuleText() }}
                                    @endif
                                    <strong>Jika ada perubahan, silakan hubungi admin untuk melihat ketersediaan jadwal.</strong>
                                </span>

                                @if($waNumber)
                                    <a href="https://wa.me/{{ $waNumber }}?text={{ rawurlencode($waText) }}"
                                       target="_blank" rel="noopener" class="contact-admin">
                                        Hubungi Admin via WhatsApp
                                    </a>
                                @else
                                    <a href="{{ route('contact') }}" class="contact-admin">Hubungi Admin</a>
                                @endif
                            </div>

                        @endif

                    </div>

                @empty

                    <div class="empty-status">Anda belum memiliki booking aktif.</div>

                @endforelse

            </div>

        </section>

    </div>

</main>


{{-- ============================ JAVASCRIPT ============================ --}}
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

    function updateSummary() {
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
        timeStepTitle.textContent = course ? 'Jam course' : 'Jam lesson';

        updateLinks();
        updateSummary();
    }

    /* Link kalender & bulan membawa pilihan jenis lesson & lapangan */
    function updateLinks() {
        document.querySelectorAll('a.calendar-day, a.month-button').forEach(function (link) {
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

            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
</body>
</html>
