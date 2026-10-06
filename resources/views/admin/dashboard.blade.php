@extends('admin.layouts.panel')

@section('title', 'Dashboard')

@php
    use App\Models\Booking;
    use App\Models\User;
    use Carbon\Carbon;

    $hasPayment  = class_exists(\App\Models\Payment::class);
    $hasGallery  = class_exists(\App\Models\Gallery::class);
    $hasBlocks   = class_exists(\App\Models\ScheduleBlock::class);

    /* ---------------------------------------------------------------
     | BOOKING AKTIF (pending & booked)
     * --------------------------------------------------------------- */
    $bookings = Booking::with('user')
        ->whereIn('status', ['pending', 'booked'])
        ->orderBy('booking_date')
        ->orderBy('start_time')
        ->get();

    $payments = $hasPayment
        ? \App\Models\Payment::whereIn('booking_id', $bookings->pluck('id'))->get()->keyBy('booking_id')
        : collect();

    /* ---------------------------------------------------------------
     | HELPER
     * --------------------------------------------------------------- */
    $initials = function (string $name) {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);

        if (! $parts) {
            return 'C';
        }

        return mb_strtoupper(count($parts) > 1
            ? mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1)
            : mb_substr($parts[0], 0, 2));
    };

    $paymentLabels = [
        'paid'      => 'Lunas',
        'verifying' => 'Perlu verifikasi',
        'cash'      => 'Bayar cash',
        'pending'   => 'Menunggu bayar',
        'unpaid'  => 'Belum bayar',
        'offline' => 'Offline',
    ];

    /* ---------------------------------------------------------------
     | DATA UNTUK TABEL & JAVASCRIPT
     * --------------------------------------------------------------- */
    $bookingData = $bookings->map(function ($booking) use ($payments, $initials, $paymentLabels) {
        $user      = $booking->user;
        $isOffline = $booking->source === 'offline';
        $name      = $user?->name ?? $booking->offline_customer_name ?? 'Customer';
        $payment   = $payments->get($booking->id);

        $paymentState = match (true) {
            $isOffline                                => 'offline',
            $payment && $payment->status === 'paid'   => 'paid',
            $payment && $payment->status === 'verifying' => 'verifying',
            $payment && $payment->status === 'cash'      => 'cash',
            $payment && $payment->status === 'pending' => 'pending',
            default                                   => 'unpaid',
        };

        return [
            'id'      => $booking->id,
            'name'    => $name,
            'email'   => $user?->email ?? $booking->offline_customer_email ?? '-',
            'phone'   => ($user ? ($user->getAttributes()['phone'] ?? null) : null) ?? $booking->offline_customer_phone ?? '-',
            'initial' => $initials($name),
            'date'    => $booking->booking_date->locale('id')->translatedFormat('d M Y'),
            'day'     => $booking->booking_date->locale('id')->translatedFormat('l'),
            'rawDate' => $booking->booking_date->format('Y-m-d'),
            'start'   => substr((string) $booking->start_time, 0, 5),
            'end'     => substr((string) $booking->end_time, 0, 5),
            'status'  => strtolower($booking->status),
            'type'    => $isOffline ? 'OFFLINE' : 'ONLINE',
            'lesson'  => method_exists($booking, 'isCourse') ? $booking->lesson_label : 'Lesson Driving Range',
            'place'   => method_exists($booking, 'isCourse') ? ($booking->place_label ?? '-') : '-',
            'placeKey' => (method_exists($booking, 'isCourse') && $booking->isCourse())
                ? 'course'
                : (($booking->getAttributes()['location_id'] ?? null) ? 'loc-' . $booking->getAttributes()['location_id'] : 'none'),
            'notes'   => $booking->admin_notes,
            'createdAt' => $booking->created_at?->locale('id')->translatedFormat('d M Y, H:i') ? $booking->created_at->locale('id')->translatedFormat('d M Y, H:i') . ' WIB' : '-',

            'payment' => [
                'id'         => $payment?->id,
                'state'      => $paymentState,
                'label'      => $paymentLabels[$paymentState],
                'amount'     => $payment?->amount_label,
                'method'     => $payment?->method_label,
                'reference'  => $payment?->reference,
                'va'         => $payment?->va_number ? trim(chunk_split($payment->va_number, 4, ' ')) : null,
                'duration'   => $payment?->duration_label,
                'paidAt'     => $payment?->paid_at?->locale('id')->translatedFormat('d M Y, H:i') ? $payment->paid_at->locale('id')->translatedFormat('d M Y, H:i') . ' WIB' : null,
                'submittedAt' => ($payment?->getAttributes()['submitted_at'] ?? null)
                    ? $payment->submitted_at->locale('id')->translatedFormat('d M Y, H:i') . ' WIB'
                    : null,
                'proofUrl'   => ($payment && method_exists($payment, 'hasProof') && $payment->hasProof() && \Illuminate\Support\Facades\Route::has('admin.payments.proof'))
                    ? route('admin.payments.proof', $payment)
                    : null,
                'proofAt'    => $payment?->proof_uploaded_at?->locale('id')->diffForHumans(),
                'receivedBy' => $payment?->getAttributes()['received_by'] ?? null,
                'note'       => $payment?->getAttributes()['payment_note'] ?? null,
                'receiptUrl' => ($payment && $payment->status === 'paid' && \Illuminate\Support\Facades\Route::has('admin.payments.receipt'))
                    ? route('admin.payments.receipt', $payment)
                    : null,
            ],
        ];
    })->values();

    /* ---------------------------------------------------------------
     | STATISTIK
     * --------------------------------------------------------------- */
    $stats = [
        ['class' => 'pending',  'icon' => '◷', 'label' => 'Pending booking',   'value' => $bookings->where('status', 'pending')->count(), 'note' => 'Menunggu persetujuan'],
        ['class' => 'booked',   'icon' => '✓', 'label' => 'Booked aktif',      'value' => $bookings->where('status', 'booked')->count(),  'note' => 'Sudah dikonfirmasi'],
        ['class' => 'customer', 'icon' => '♟', 'label' => 'Total customer',    'value' => User::where('role', 'customer')->count(),      'note' => 'Member terdaftar'],
        ['class' => 'gallery',  'icon' => '▧', 'label' => 'Gallery',           'value' => $hasGallery ? \App\Models\Gallery::count() : 0, 'note' => 'Foto & video'],
    ];

    $todayBlocks = $hasBlocks
        ? \App\Models\ScheduleBlock::whereDate('date', today())->get()
        : collect();

    $initialStatus = in_array(request('status'), ['pending', 'booked'], true) ? request('status') : '';

    $requirePaid = class_exists(\App\Support\BookingRules::class) && \App\Support\BookingRules::requirePaidBeforeApprove();
    $verifyCount = $bookingData->where('payment.state', 'verifying')->count();
@endphp

@push('styles')
    <style>
        /* ---------- Sambutan ---------- */
        .db-welcome { display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 18px; }
        .db-welcome h1 { font-size: 29px; font-weight: 900; letter-spacing: -1px; }
        .db-welcome p { margin-top: 6px; color: rgba(255, 255, 255, .64); font-size: 12px; }
        .db-clock {
            display: flex;
            gap: 16px;
            padding: 11px 15px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: rgba(4, 22, 15, .64);
            color: rgba(255, 255, 255, .82);
            font-size: 11px;
        }

        .db-alert {
            margin-bottom: 14px;
            padding: 11px 14px;
            border-radius: 9px;
            border: 1px solid rgba(216, 35, 61, .4);
            background: rgba(216, 35, 61, .1);
            color: #ff9aa6;
            font-size: 12px;
        }

        .db-alert a { color: #fff; font-weight: 800; text-decoration: underline; }

        /* ---------- Statistik ---------- */
        .db-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 15px; }
        .db-stat {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border: 1px solid rgba(156, 255, 0, .15);
            border-radius: var(--radius);
            background: rgba(1, 20, 13, .78);
        }
        .db-stat-icon {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            font-size: 19px;
            font-weight: 900;
        }
        .db-stat-icon.pending  { background: rgba(245, 181, 0, .16); color: #ffc52c; }
        .db-stat-icon.booked   { background: rgba(68, 190, 82, .16); color: #65ec65; }
        .db-stat-icon.customer { background: rgba(0, 160, 235, .16); color: #31b9ff; }
        .db-stat-icon.gallery  { background: rgba(136, 72, 255, .16); color: #9b67ff; }
        .db-stat span { display: block; color: rgba(255, 255, 255, .64); font-size: 11px; font-weight: 700; }
        .db-stat strong { display: block; margin-top: 3px; font-size: 26px; font-weight: 900; line-height: 1; }
        .db-stat small { display: block; margin-top: 5px; color: #72ed4c; font-size: 10px; }

        /* ---------- Filter ---------- */
        .db-filter {
            display: grid;
            grid-template-columns: minmax(200px, 1.6fr) repeat(4, minmax(120px, .8fr)) auto;
            gap: 9px;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid rgba(156, 255, 0, .12);
            border-radius: 10px;
            background: rgba(2, 20, 14, .75);
        }
        .db-filter .input { font-size: 11px; }
        .db-filter select.input option { background: #07150f; }

        /* ---------- Tabel + detail ---------- */
        .db-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 15px; align-items: start; }
        .db-grid.detail-hidden { grid-template-columns: minmax(0, 1fr); }

        .db-card { min-width: 0; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); overflow: hidden; }
        .db-card-head { height: 52px; display: flex; align-items: center; justify-content: space-between; padding: 0 16px; border-bottom: 1px solid var(--line); }
        .db-card-head h2 { font-size: 15px; font-weight: 900; }
        .db-card-head small { color: var(--text-muted); font-size: 11px; }
        .db-card-head .modal-close { border: 0; background: transparent; color: rgba(255, 255, 255, .6); font-size: 20px; cursor: pointer; }

        .db-table-wrap { overflow-x: auto; }
        .db-table { width: 100%; min-width: 720px; border-collapse: collapse; }
        .db-table th {
            height: 36px;
            padding: 0 10px;
            background: rgba(0, 0, 0, .2);
            color: var(--text-muted);
            text-align: left;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .db-table td { padding: 9px 10px; border-top: 1px solid rgba(255, 255, 255, .06); color: var(--text-soft); font-size: 11px; vertical-align: middle; }
        .db-row { cursor: pointer; transition: background .15s ease; }
        .db-row:hover, .db-row.selected { background: rgba(58, 150, 57, .16); }
        .db-row td strong { display: block; color: #fff; font-size: 11px; }
        .db-row td small { display: block; margin-top: 2px; color: var(--text-muted); font-size: 10px; }

        .db-pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 999px; font-size: 9px; font-weight: 900; white-space: nowrap; }
        .db-pill.pending  { background: rgba(245, 174, 0, .18); color: #ffc62d; }
        .db-pill.booked   { background: rgba(67, 190, 77, .18); color: #68ed62; }
        .db-pill.paid     { background: rgba(184, 255, 0, .16); color: #b8ff00; }
        .db-pill.unpaid   { background: rgba(255, 77, 94, .16); color: #ff8a96; }
        .db-pill.waiting  { background: rgba(245, 174, 0, .16); color: #ffc62d; }
        .db-pill.offline  { background: rgba(255, 255, 255, .08); color: rgba(255, 255, 255, .78); }
        .db-pill.verify   { background: rgba(92, 168, 255, .18); color: #9ccdff; }
        .db-pill.cash     { background: rgba(255, 196, 0, .16); color: #ffd45c; }
        .db-cash { display: grid; gap: 8px; margin-top: 10px; padding: 12px; border-radius: 9px; border: 1px solid rgba(255, 196, 0, .3); background: rgba(255, 196, 0, .06); }
        .db-cash strong { color: #ffd45c; font-size: 12px; }
        .db-cash small { color: var(--text-muted); font-size: 10px; line-height: 1.5; }
        .db-cash-row { display: grid; grid-template-columns: 100px 1fr; gap: 6px; }
        .db-cash .input { height: 34px; font-size: 11px; }
        .db-btn.cash { background: #ffc62d; color: #1a1300; }
        .db-btn.verify    { background: #5ca8ff; color: #06111c; }
        .db-btn.verify-no { background: transparent; border: 1px solid rgba(255, 120, 130, .5); color: #ff9aa6; }
        .db-verify-alert { margin-bottom: 14px; padding: 11px 14px; border-radius: 9px; border: 1px solid rgba(92, 168, 255, .4); background: rgba(92, 168, 255, .1); color: #b9dcff; font-size: 12px; }
        .db-pill.online   { background: rgba(92, 168, 255, .14); color: #8ec7ff; }

        .db-empty { padding: 50px 20px; text-align: center; color: var(--text-muted); font-size: 12px; }

        /* Detail */
        .db-detail-body { padding: 14px 16px; }
        .db-detail-person { display: flex; align-items: center; gap: 11px; padding-bottom: 13px; border-bottom: 1px solid var(--line); }
        .db-detail-person h3 { font-size: 14px; font-weight: 900; }
        .db-detail-person p { margin-top: 3px; color: var(--text-muted); font-size: 10px; word-break: break-all; }
        .db-detail-person .person-avatar { width: 50px; height: 50px; flex-basis: 50px; font-size: 15px; }

        .db-section { padding: 12px 0; border-bottom: 1px solid var(--line); }
        .db-section h4 { margin-bottom: 8px; font-size: 10px; font-weight: 900; letter-spacing: .5px; text-transform: uppercase; color: var(--lime); }
        .db-row-info { display: grid; grid-template-columns: 90px 1fr; gap: 6px; margin-bottom: 6px; font-size: 10px; }
        .db-row-info span:first-child { color: var(--text-muted); }
        .db-row-info span:last-child { color: #fff; font-weight: 700; word-break: break-word; }

        .db-pay-note { padding: 9px 10px; border-radius: 8px; background: rgba(255, 255, 255, .05); color: var(--text-muted); font-size: 10px; line-height: 1.5; }
        .db-proof { display: block; max-width: 180px; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .db-proof img { display: block; width: 100%; height: auto; max-height: 220px; object-fit: cover; }

        .db-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding-top: 12px; }
        .db-btn { height: 36px; border: 0; border-radius: 7px; font-size: 10px; font-weight: 900; cursor: pointer; }
        .db-btn.approve { background: #61cf45; color: #07120c; }
        .db-btn.reject  { background: #d8233d; color: #fff; }
        .db-btn.receipt { grid-column: 1 / -1; background: transparent; border: 1px solid rgba(156, 255, 0, .4); color: var(--lime); }
        .db-btn.resched {
            grid-column: 1 / -1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 1px solid rgba(245, 174, 0, .45);
            color: #ffc62d;
            text-decoration: none;
        }
        .db-btn.resched:hover { background: rgba(245, 174, 0, .12); }
        .db-btn:disabled { opacity: .35; cursor: not-allowed; }

        /* Modal bukti */
        .db-modal { position: fixed; inset: 0; z-index: 300; display: none; align-items: center; justify-content: center; padding: 20px; background: rgba(0, 0, 0, .72); }
        .db-modal.open { display: flex; }
        .db-modal-box { width: 100%; max-width: 440px; border-radius: 14px; background: #ffffff; color: #18211c; overflow: hidden; }
        .db-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #07130f; color: #fff; }
        .db-modal-head strong { font-size: 15px; }
        .db-modal-head button { border: 0; background: transparent; color: rgba(255, 255, 255, .7); font-size: 22px; cursor: pointer; }
        .db-receipt-status { display: flex; align-items: center; gap: 10px; padding: 16px 20px; border-bottom: 1px dashed #d5dcd8; }
        .db-receipt-status span { width: 36px; height: 36px; display: grid; place-items: center; border-radius: 50%; background: #2e9e3a; color: #fff; font-weight: 900; }
        .db-receipt-amount { padding: 14px 20px; border-bottom: 1px dashed #d5dcd8; }
        .db-receipt-amount small { color: #66706b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .db-receipt-amount strong { display: block; margin-top: 3px; font-size: 26px; font-weight: 900; }
        .db-receipt-rows { padding: 12px 20px; }
        .db-receipt-rows div { display: flex; justify-content: space-between; gap: 12px; padding: 5px 0; font-size: 12px; }
        .db-receipt-rows span:first-child { color: #66706b; }
        .db-receipt-rows span:last-child { font-weight: 700; text-align: right; }
        .db-receipt-rows div[hidden] { display: none; }
        .db-modal-box { max-height: calc(100vh - 40px); overflow-y: auto; }
        .db-modal-foot { display: flex; gap: 8px; padding: 14px 20px 18px; }
        .db-modal-foot a, .db-modal-foot button {
            flex: 1;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }
        .db-modal-foot a { background: #07130f; color: #fff; border: 0; }
        .db-modal-foot button { background: #fff; color: #07130f; border: 1px solid #cfd6d2; }

        @media (max-width: 1250px) { .db-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 1050px) {
            .db-grid { grid-template-columns: 1fr; }
            .db-filter { grid-template-columns: 1fr 1fr; }
            .db-filter .input:first-child { grid-column: 1 / -1; }
        }
        @media (max-width: 560px) { .db-stats { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')

    {{-- ================= SAMBUTAN ================= --}}
    <section class="db-welcome">
        <div>
            <h1>Selamat Datang, {{ auth()->user()->name ?? 'Admin' }} 👋</h1>
            <p>Kelola pemesanan lesson, pembayaran, dan jadwal Golf Booking Lesson.</p>
        </div>

        <div class="db-clock">
            <span>▣ <span id="currentDate">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span></span>
            <span>◷ <span id="currentTime">{{ now()->format('H:i:s') }}</span> WIB</span>
        </div>
    </section>

    @if ($todayBlocks->isNotEmpty())
        <div class="db-alert">
            ⊘ Hari ini ada jadwal yang ditutup:
            @foreach ($todayBlocks as $block)
                <strong>{{ $block->time_label }}</strong> ({{ $block->reason }}){{ $loop->last ? '.' : ',' }}
            @endforeach
            <a href="{{ route('admin.schedule-blocks.index') }}">Kelola jadwal</a>
        </div>
    @endif

    @if ($verifyCount > 0)
        <div class="db-verify-alert">
            ◷ Ada <strong>{{ $verifyCount }}</strong> pembayaran QRIS / transfer yang menunggu verifikasi.
            Pilih filter <strong>Perlu verifikasi</strong>, cek mutasi rekening, lalu konfirmasi di panel detail.
        </div>
    @endif

    {{-- ================= STATISTIK ================= --}}
    <section class="db-stats">
        @foreach ($stats as $stat)
            <div class="db-stat">
                <span class="db-stat-icon {{ $stat['class'] }}">{{ $stat['icon'] }}</span>
                <div>
                    <span>{{ $stat['label'] }}</span>
                    <strong>{{ $stat['value'] }}</strong>
                    <small>↑ {{ $stat['note'] }}</small>
                </div>
            </div>
        @endforeach
    </section>

    {{-- ================= FILTER ================= --}}
    <section class="db-filter">
        <input type="text" id="searchBooking" class="input" placeholder="Cari nama, email, HP, atau lapangan...">

        <select id="placeFilter" class="input" aria-label="Filter lapangan">
            <option value="">Semua lapangan</option>
            @if (class_exists(\App\Support\BookingRules::class))
                @foreach (\App\Support\BookingRules::activeLocations() as $filterLocation)
                    <option value="loc-{{ $filterLocation->id }}">{{ $filterLocation->name }}</option>
                @endforeach
                <option value="course">Course Lesson (lapangan golf)</option>
            @endif
            <option value="none">Tanpa lapangan (booking lama)</option>
        </select>

        <select id="statusFilter" class="input">
            <option value="">Semua status</option>
            <option value="pending" @selected($initialStatus === 'pending')>Pending</option>
            <option value="booked" @selected($initialStatus === 'booked')>Booked</option>
        </select>

        <select id="paymentFilter" class="input">
            <option value="">Semua pembayaran</option>
            <option value="paid">Lunas</option>
            <option value="verifying">Perlu verifikasi</option>
            <option value="cash">Bayar cash</option>
            <option value="unpaid">Belum dibayar</option>
            <option value="offline">Offline</option>
        </select>

        <input type="date" id="dateFilter" class="input">

        <button type="button" class="btn btn-outline" id="resetFilter">↻ Reset</button>
    </section>

    {{-- ================= TABEL + DETAIL ================= --}}
    <section class="db-grid" id="booking-list">

        <div class="db-card">
            <div class="db-card-head">
                <h2>▣ Daftar Booking</h2>
                <small id="resultCount">{{ $bookingData->count() }} booking aktif</small>
            </div>

            <div class="db-table-wrap">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th style="width: 34px;">No</th>
                            <th>Customer</th>
                            <th>Jadwal</th>
                            <th>Tipe</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookingData as $index => $row)
                            @php
                                $payClass = ['paid' => 'paid', 'verifying' => 'verify', 'cash' => 'cash', 'pending' => 'waiting', 'unpaid' => 'unpaid', 'offline' => 'offline'][$row['payment']['state']];
                            @endphp
                            <tr class="db-row" data-index="{{ $index }}">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="person">
                                        <span class="person-avatar {{ $row['type'] === 'OFFLINE' ? 'offline' : '' }}">{{ $row['initial'] }}</span>
                                        <div>
                                            <strong>{{ $row['name'] }}</strong>
                                            <small>{{ $row['phone'] !== '-' ? $row['phone'] : $row['email'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ $row['date'] }}</strong>
                                    <small>{{ $row['start'] }} – {{ $row['end'] }}</small>
                                    <small style="display: block; color: var(--lime)">{{ $row['lesson'] }}{{ $row['place'] !== '-' ? ' · ' . $row['place'] : '' }}</small>
                                </td>
                                <td>
                                    <span class="db-pill {{ strtolower($row['type']) }}">{{ $row['type'] }}</span>
                                </td>
                                <td>
                                    <span class="db-pill {{ $payClass }}">
                                        {{ $row['payment']['state'] === 'paid' ? '✓' : ($row['payment']['state'] === 'offline' ? '⌂' : ($row['payment']['state'] === 'verifying' ? '◷' : '!')) }}
                                        {{ $row['payment']['label'] }}
                                    </span>
                                    @if ($row['payment']['amount'] && $row['payment']['state'] !== 'offline')
                                        <small>{{ $row['payment']['amount'] }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="db-pill {{ $row['status'] }}">● {{ strtoupper($row['status']) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="db-empty">Belum ada booking aktif.</td>
                            </tr>
                        @endforelse

                        <tr id="noResultRow" hidden>
                            <td colspan="6" class="db-empty">Tidak ada booking yang cocok dengan filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detail --}}
        <aside class="db-card" id="detailCard" @if ($bookingData->isEmpty()) hidden @endif>
            <div class="db-card-head">
                <h2>Detail Booking</h2>
                <button type="button" class="modal-close" id="closeDetail" aria-label="Tutup detail">×</button>
            </div>

            <div class="db-detail-body">
                <div class="db-detail-person">
                    <span class="person-avatar" id="dAvatar">-</span>
                    <div style="flex: 1; min-width: 0;">
                        <h3 id="dName">-</h3>
                        <p id="dEmail">-</p>
                        <p id="dPhone">-</p>
                    </div>
                    <span class="db-pill" id="dStatusPill">-</span>
                </div>

                <div class="db-section">
                    <h4>Jadwal</h4>
                    <div class="db-row-info"><span>Tanggal</span><span id="dDate">-</span></div>
                    <div class="db-row-info"><span>Jam</span><span id="dTime">-</span></div>
                    <div class="db-row-info"><span>Tipe</span><span id="dType">-</span></div>
                    <div class="db-row-info"><span>Booking dibuat</span><span id="dCreatedAt">-</span></div>
                    <div class="db-row-info"><span>Jenis lesson</span><span id="dLesson">-</span></div>
                    <div class="db-row-info"><span>Lapangan</span><span id="dPlace">-</span></div>
                    <div class="db-row-info" id="dNotesRow"><span>Catatan admin</span><span id="dNotes">-</span></div>
                </div>

                <div class="db-section">
                    <h4>Pembayaran</h4>
                    <div id="dPayOnline">
                        <div class="db-row-info"><span>Status</span><span><span class="db-pill" id="dPayPill">-</span></span></div>
                        <div class="db-row-info"><span>Jumlah</span><span id="dPayAmount">-</span></div>
                        <div class="db-row-info"><span>Metode</span><span id="dPayMethod">-</span></div>
                        <div class="db-row-info"><span>Referensi</span><span id="dPayRef">-</span></div>
                        <div class="db-row-info"><span>Dikirim customer</span><span id="dSubmittedAt">-</span></div>
                        <div class="db-row-info"><span>Dibayar</span><span id="dPayAt">-</span></div>
                        <div id="dProof" hidden style="margin-top: 8px">
                            <a href="#" id="dProofLink" target="_blank" rel="noopener" class="db-proof">
                                <img src="" alt="Bukti pembayaran dari customer" id="dProofImg">
                            </a>
                            <small id="dProofAt" style="display: block; margin-top: 4px; color: var(--text-muted); font-size: 10px"></small>
                        </div>
                    </div>
                    <div class="db-pay-note" id="dPayOffline" hidden>
                        Booking offline dibuat oleh admin. Pembayaran dilakukan langsung di tempat,
                        sehingga tidak ada bukti pembayaran digital. Status otomatis <strong>Booked</strong>.
                    </div>
                    <div class="db-pay-note" id="dPayUnpaid" hidden>
                        Customer belum menyelesaikan pembayaran online.
                    </div>
                    <div class="db-pay-note" id="dPayVerify" hidden style="color: #b9dcff">
                        Customer mengonfirmasi sudah membayar. Bandingkan bukti di atas dengan mutasi rekening / QRIS
                        (nominal & no. referensi), lalu konfirmasi atau tolak.
                    </div>
                    <div class="db-actions" id="dVerifyActions" hidden>
                        <button type="button" class="db-btn verify" id="btnVerify">✓ Dana masuk, konfirmasi</button>
                        <button type="button" class="db-btn verify-no" id="btnVerifyReject">× Belum masuk</button>
                    </div>

                    <div class="db-row-info" id="dReceivedRow" hidden><span>Diterima oleh</span><span id="dReceivedBy">-</span></div>
                    <div class="db-row-info" id="dNoteRow" hidden><span>Catatan</span><span id="dPayNote">-</span></div>

                    {{-- Tandai lunas karena dibayar cash --}}
                    @if (\Illuminate\Support\Facades\Route::has('admin.payments.cash'))
                        <form method="POST" action="#" class="db-cash" id="cashForm" hidden>
                            @csrf
                            @method('PATCH')
                            <strong id="cashTitle">Customer membayar cash?</strong>
                            <small id="cashHint">Tandai lunas setelah uang tunai diterima.</small>
                            <input type="text" name="payment_note" class="input" maxlength="255" placeholder="Catatan (opsional)">
                            <button type="submit" class="db-btn cash">✓ Tandai lunas (Cash)</button>
                        </form>
                    @endif
                </div>

                <div class="db-actions">
                    <button type="button" class="db-btn approve" id="btnApprove">✓ Approve</button>
                    <button type="button" class="db-btn reject" id="btnReject">× Reject</button>
                    <a href="#" class="db-btn resched" id="btnReschedule">↻ Reschedule jadwal</a>
                    <button type="button" class="db-btn receipt" id="btnReceipt" hidden>🧾 Lihat bukti pembayaran</button>
                </div>
            </div>
        </aside>

    </section>

    {{-- Form approve / reject --}}
    <form id="bookingActionForm" method="POST" action="#" hidden>
        @csrf
        @method('PATCH')
    </form>

    {{-- ================= MODAL BUKTI PEMBAYARAN ================= --}}
    <div class="db-modal" id="receiptModal">
        <div class="db-modal-box" role="dialog" aria-modal="true" aria-labelledby="receiptTitle">
            <div class="db-modal-head">
                <strong id="receiptTitle">Bukti Pembayaran</strong>
                <button type="button" data-close-receipt aria-label="Tutup">×</button>
            </div>

            <div class="db-receipt-status">
                <span>✓</span>
                <div>
                    <strong>Pembayaran berhasil</strong>
                    <div style="color: #66706b; font-size: 11px;" id="rPaidAt">-</div>
                </div>
            </div>

            <div class="db-receipt-amount">
                <small>Total dibayar</small>
                <strong id="rAmount">-</strong>
            </div>

            <div class="db-receipt-rows">
                <div><span>No. referensi</span><span id="rRef">-</span></div>
                <div><span>Customer</span><span id="rName">-</span></div>
                <div><span>Metode</span><span id="rMethod">-</span></div>
                <div id="rVaRow"><span>No. VA</span><span id="rVa">-</span></div>
                <div><span>Jenis lesson</span><span id="rLesson">-</span></div>
                <div><span>Lapangan</span><span id="rPlace">-</span></div>
                <div><span>Jadwal lesson</span><span id="rSchedule">-</span></div>
                <div><span>Durasi</span><span id="rDuration">-</span></div>
                <div><span>Booking dibuat</span><span id="rCreatedAt">-</span></div>
                <div id="rSubmittedRow"><span>Dikirim customer</span><span id="rSubmittedAt">-</span></div>
                <div id="rReceivedRow"><span>Diterima oleh</span><span id="rReceivedBy">-</span></div>
            </div>

            <div class="db-modal-foot">
                <button type="button" data-close-receipt>Tutup</button>
                <a href="#" id="rPrint" target="_blank" rel="noopener">Cetak bukti</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const bookings = @json($bookingData);
            const routes = {
                approve: @json(route('admin.bookings.approve', '__ID__')),
                reject:  @json(route('admin.bookings.reject', '__ID__')),
                reschedule: @json(route('admin.bookings.reschedule', '__ID__')),
                verify: @json(\Illuminate\Support\Facades\Route::has('admin.payments.verify') ? route('admin.payments.verify', '__ID__') : null),
                verifyReject: @json(\Illuminate\Support\Facades\Route::has('admin.payments.reject') ? route('admin.payments.reject', '__ID__') : null),
            };
            const requirePaid = @json($requirePaid);

            const $ = (id) => document.getElementById(id);
            const setText = (id, value) => { const el = $(id); if (el) el.textContent = value ?? '-'; };

            const rows = Array.from(document.querySelectorAll('.db-row'));
            let current = null;

            const payClass = { paid: 'paid', verifying: 'verify', cash: 'cash', pending: 'waiting', unpaid: 'unpaid', offline: 'offline' };

            /* ---------- Jam ---------- */
            function updateClock() {
                const now = new Date();
                setText('currentDate', now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
                setText('currentTime', now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':'));
            }
            updateClock();
            setInterval(updateClock, 1000);

            /* ---------- Detail ---------- */
            function showDetail(index) {
                const b = bookings[index];
                if (!b) return;
                current = b;

                $('detailCard').hidden = false;
                $('booking-list').classList.remove('detail-hidden');

                setText('dAvatar', b.initial);
                $('dAvatar').classList.toggle('offline', b.type === 'OFFLINE');
                setText('dName', b.name);
                setText('dEmail', b.email);
                setText('dPhone', b.phone);
                setText('dDate', b.day + ', ' + b.date);
                setText('dTime', b.start + ' – ' + b.end);
                setText('dType', b.type);
                setText('dCreatedAt', b.createdAt || '-');
                setText('dLesson', b.lesson || '-');
                setText('dPlace', b.place || '-');
                setText('dNotes', b.notes || '-');
                $('dNotesRow').hidden = !b.notes;

                const pill = $('dStatusPill');
                pill.textContent = '● ' + b.status.toUpperCase();
                pill.className = 'db-pill ' + b.status;

                const p = b.payment;
                $('dPayOffline').hidden = p.state !== 'offline';
                $('dPayOnline').hidden  = p.state === 'offline';
                $('dPayUnpaid').hidden  = p.state !== 'unpaid';
                $('dPayVerify').hidden  = p.state !== 'verifying';
                $('dVerifyActions').hidden = p.state !== 'verifying' || !routes.verify;

                $('dReceivedRow').hidden = !p.receivedBy;
                setText('dReceivedBy', p.receivedBy || '-');
                $('dNoteRow').hidden = !p.note;
                setText('dPayNote', p.note || '-');

                const cashForm = $('cashForm');
                if (cashForm) {
                    const canCash = Boolean(p.id) && ['cash', 'unpaid', 'pending'].includes(p.state)
                        && ['pending', 'booked'].includes(b.status);
                    cashForm.hidden = !canCash;
                    if (canCash) {
                        cashForm.action = @json(\Illuminate\Support\Facades\Route::has('admin.payments.cash') ? route('admin.payments.cash', '__ID__') : '#').replace('__ID__', p.id);
                        $('cashTitle').textContent = p.state === 'cash' ? 'Customer memilih bayar cash' : 'Customer membayar cash?';
                        $('cashHint').textContent = p.state === 'cash'
                            ? 'Tandai lunas setelah uang ' + (p.amount || '') + ' Anda terima.'
                            : 'Gunakan jika customer membayar tunai, bukan lewat QRIS / transfer.';
                    }
                }

                const payPill = $('dPayPill');
                payPill.textContent = p.label;
                payPill.className = 'db-pill ' + payClass[p.state];

                setText('dPayAmount', p.amount || '-');
                setText('dPayMethod', p.method || '-');
                setText('dPayRef', p.reference || '-');
                setText('dPayAt', p.paidAt || '-');
                setText('dSubmittedAt', p.submittedAt || '-');

                $('dProof').hidden = !p.proofUrl;
                if (p.proofUrl) {
                    $('dProofLink').href = p.proofUrl;
                    $('dProofImg').src = p.proofUrl;
                    setText('dProofAt', 'Bukti dikirim ' + (p.proofAt || '') + ' · klik untuk memperbesar');
                }

                $('btnApprove').disabled = b.status !== 'pending'
                    || (requirePaid && ['unpaid', 'pending', 'verifying'].includes(p.state));
                $('btnApprove').title = (requirePaid && ['unpaid', 'pending', 'verifying'].includes(p.state))
                    ? 'Wajib lunas sebelum di-approve (diatur di Settings)' : '';
                $('btnReschedule').href = routes.reschedule.replace('__ID__', b.id);
                $('btnReceipt').hidden = p.state !== 'paid';

                rows.forEach((row) => row.classList.toggle('selected', Number(row.dataset.index) === index));
            }

            rows.forEach((row) => row.addEventListener('click', () => showDetail(Number(row.dataset.index))));

            $('closeDetail')?.addEventListener('click', () => {
                $('detailCard').hidden = true;
                $('booking-list').classList.add('detail-hidden');
                rows.forEach((row) => row.classList.remove('selected'));
            });

            /* ---------- Approve / Reject ---------- */
            function submitAction(type) {
                if (!current) return;

                let message = (type === 'approve' ? 'Setujui' : 'Tolak') + ' booking atas nama ' + current.name + '?';

                if (type === 'approve' && ['unpaid', 'pending', 'verifying'].includes(current.payment.state)) {
                    message = '⚠ Booking ini BELUM DIBAYAR.\n\n' + message;
                }

                if (!confirm(message)) return;

                const form = $('bookingActionForm');
                form.action = routes[type].replace('__ID__', current.id);
                form.submit();
            }

            $('btnApprove').addEventListener('click', () => submitAction('approve'));

            const cashFormEl = $('cashForm');
            if (cashFormEl) {
                cashFormEl.addEventListener('submit', (event) => {
                    if (!current || !confirm('Tandai pembayaran ' + (current.payment.amount || '') + ' dari ' + current.name + ' LUNAS secara cash?')) {
                        event.preventDefault();
                    }
                });
            }

            function submitVerify(ok) {
                if (!current || !current.payment.id) return;

                const message = ok
                    ? 'Konfirmasi pembayaran ' + current.payment.amount + ' dari ' + current.name + ' sudah MASUK?'
                    : 'Tandai pembayaran ' + current.name + ' BELUM masuk? Customer akan diminta membayar ulang.';

                if (!confirm(message)) return;

                const form = $('bookingActionForm');
                form.action = (ok ? routes.verify : routes.verifyReject).replace('__ID__', current.payment.id);
                form.submit();
            }

            $('btnVerify').addEventListener('click', () => submitVerify(true));
            $('btnVerifyReject').addEventListener('click', () => submitVerify(false));
            $('btnReject').addEventListener('click', () => submitAction('reject'));

            /* ---------- Modal bukti pembayaran ---------- */
            const modal = $('receiptModal');

            $('btnReceipt').addEventListener('click', () => {
                if (!current) return;
                const p = current.payment;

                setText('rPaidAt', p.paidAt ? 'Dibayar ' + p.paidAt : '-');
                setText('rAmount', p.amount);
                setText('rRef', p.reference);
                setText('rName', current.name);
                setText('rMethod', p.method);
                setText('rVa', p.va);
                $('rVaRow').hidden = !p.va;
                setText('rSchedule', current.day + ', ' + current.date + ' · ' + current.start + '–' + current.end + ' WIB');
                setText('rDuration', p.duration);
                setText('rLesson', current.lesson || 'Lesson Driving Range');
                setText('rPlace', current.place && current.place !== '-' ? current.place : '-');
                setText('rCreatedAt', current.createdAt || '-');
                setText('rSubmittedAt', p.submittedAt || '-');
                $('rSubmittedRow').hidden = !p.submittedAt;
                setText('rReceivedBy', p.receivedBy || '-');
                $('rReceivedRow').hidden = !p.receivedBy;

                const print = $('rPrint');
                print.href = p.receiptUrl || '#';
                print.hidden = !p.receiptUrl;

                modal.classList.add('open');
            });

            document.querySelectorAll('[data-close-receipt]').forEach((btn) => btn.addEventListener('click', () => modal.classList.remove('open')));
            modal.addEventListener('click', (event) => { if (event.target === modal) modal.classList.remove('open'); });
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape') modal.classList.remove('open'); });

            /* ---------- Filter ---------- */
            const search  = $('searchBooking');
            const status  = $('statusFilter');
            const payment = $('paymentFilter');
            const date    = $('dateFilter');
            const place   = $('placeFilter');

            function applyFilters() {
                const q = search.value.trim().toLowerCase();
                let visible = 0;

                rows.forEach((row) => {
                    const b = bookings[Number(row.dataset.index)];
                    const payState = b.payment.state === 'pending' ? 'unpaid' : b.payment.state;  // verifying punya filter sendiri

                    const match =
                        (!q || [b.name, b.email, b.phone, b.place, b.lesson].some((v) => String(v).toLowerCase().includes(q))) &&
                        (!place.value || b.placeKey === place.value) &&
                        (!status.value || b.status === status.value) &&
                        (!payment.value || payState === payment.value) &&
                        (!date.value || b.rawDate === date.value);

                    row.hidden = !match;
                    if (match) visible++;
                });

                $('noResultRow').hidden = rows.length === 0 || visible > 0;
                setText('resultCount', visible + ' booking ditampilkan');
            }

            search.addEventListener('input', applyFilters);
            [status, payment, date, place].forEach((el) => el.addEventListener('change', applyFilters));

            $('resetFilter').addEventListener('click', () => {
                search.value = status.value = payment.value = date.value = place.value = '';
                applyFilters();
            });

            /* ---------- Awal ---------- */
            applyFilters();
            const firstVisible = rows.find((row) => !row.hidden);
            if (firstVisible) showDetail(Number(firstVisible.dataset.index));
        })();
    </script>
@endpush
