@extends('admin.layouts.panel')

@section('title', 'Reschedule Booking')

@php
    $customerName  = $booking->user->name ?? $booking->offline_customer_name ?? 'Customer';
    $customerEmail = $booking->user->email ?? $booking->offline_customer_email;
    $customerPhone = $booking->user ? ($booking->user->getAttributes()['phone'] ?? null) : $booking->offline_customer_phone;
    $isOffline     = ! $booking->user_id;

    $waUrl = null;
    if ($customerPhone) {
        $digits = preg_replace('/\D+/', '', $customerPhone);
        $digits = str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;
        $waUrl  = $digits ? 'https://wa.me/' . $digits : null;
    }

    $durationLabel = trim((intdiv($duration, 60) ? intdiv($duration, 60) . ' jam ' : '') . ($duration % 60 ? ($duration % 60) . ' menit' : ''));

    $oldDate  = old('booking_date', $date->format('Y-m-d'));
    $oldStart = old('start_time');
    $oldEnd   = old('end_time');

    $slotLabels = [
        'available' => 'Tersedia',
        'booked'    => 'Booked',
        'pending'   => 'Pending',
        'blocked'   => 'Ditutup',
        'current'   => 'Jadwal lama',
        'past'      => 'Lewat',
    ];

    $backUrl = $from === 'schedule' ? route('admin.schedule-blocks.index') : route('admin.dashboard');
@endphp

@push('styles')
    <style>
        .rs-grid { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 18px; align-items: start; }

        .rs-card { padding: 20px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }
        .rs-card h2 { margin-bottom: 14px; font-size: 16px; font-weight: 900; }

        .rs-person { display: flex; align-items: center; gap: 12px; padding-bottom: 14px; border-bottom: 1px solid var(--line); }
        .rs-person .person-avatar { width: 48px; height: 48px; flex-basis: 48px; font-size: 15px; }
        .rs-person strong { display: block; font-size: 15px; }
        .rs-person small { display: block; margin-top: 2px; color: var(--text-muted); font-size: 11px; word-break: break-all; }

        .rs-rows { display: grid; gap: 8px; padding: 14px 0; border-bottom: 1px solid var(--line); }
        .rs-row { display: grid; grid-template-columns: 100px 1fr; gap: 8px; font-size: 12px; }
        .rs-row span:first-child { color: var(--text-muted); }
        .rs-row span:last-child { color: #fff; font-weight: 700; }

        .rs-warning {
            margin-top: 14px;
            padding: 11px 12px;
            border-radius: 9px;
            background: rgba(216, 35, 61, .1);
            border: 1px solid rgba(216, 35, 61, .35);
            color: #ff9aa6;
            font-size: 12px;
            line-height: 1.5;
        }

        .rs-info { margin-top: 14px; padding: 10px 12px; border-radius: 9px; background: rgba(255, 255, 255, .05); color: var(--text-muted); font-size: 11px; line-height: 1.5; }

        .rs-contact { display: grid; gap: 8px; margin-top: 14px; }
        .rs-contact .btn { width: 100%; }

        /* Grid jam */
        .rs-legend { display: flex; flex-wrap: wrap; gap: 6px 14px; margin: 4px 0 12px; color: var(--text-muted); font-size: 11px; }
        .rs-legend span { display: inline-flex; align-items: center; gap: 6px; }
        .rs-legend i { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }

        .rs-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 8px; margin-bottom: 16px; }

        .rs-slot {
            min-height: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 800;
        }

        .rs-slot small { font-size: 9px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; opacity: .85; }

        .rs-slot.available { color: var(--lime); background: rgba(156, 255, 0, .05); border-color: rgba(156, 255, 0, .3); cursor: pointer; }
        .rs-slot.available:hover { background: rgba(156, 255, 0, .14); }
        .rs-slot.current   { color: #8ec7ff; background: rgba(92, 168, 255, .08); border-color: rgba(92, 168, 255, .4); cursor: pointer; }
        .rs-slot.selected  { color: #07120c !important; background: var(--lime) !important; border-color: var(--lime) !important; }
        .rs-slot.booked    { color: #ff9a9a; background: rgba(255, 92, 92, .07); border-color: rgba(255, 92, 92, .25); }
        .rs-slot.pending   { color: #ffd45c; background: rgba(255, 196, 0, .06); border-color: rgba(255, 196, 0, .25); }
        .rs-slot.blocked   { color: #ff9aa6; background: repeating-linear-gradient(135deg, rgba(216, 35, 61, .10) 0 6px, rgba(216, 35, 61, .04) 6px 12px); border-color: rgba(216, 35, 61, .35); }
        .rs-slot.past      { color: rgba(255, 255, 255, .3); background: rgba(255, 255, 255, .02); border-color: rgba(255, 255, 255, .05); text-decoration: line-through; }
        button.rs-slot { font-family: inherit; }
        div.rs-slot { cursor: not-allowed; }

        .rs-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 4px 0 14px;
            padding: 12px 14px;
            border-radius: 9px;
            background: rgba(156, 255, 0, .06);
            border: 1px solid rgba(156, 255, 0, .2);
            font-size: 12px;
        }

        .rs-summary strong { color: var(--lime); font-size: 15px; }

        .rs-actions { display: grid; grid-template-columns: auto 1fr; gap: 8px; }

        @media (max-width: 1050px) { .rs-grid { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <section class="page-head">
        <div>
            <h1>Reschedule Booking</h1>
            <p>Pindahkan jadwal booking {{ $customerName }}. Customer akan menerima notifikasi jadwal baru secara otomatis.</p>
        </div>

        <div class="head-actions">
            <a href="{{ $backUrl }}" class="btn btn-ghost">← Kembali</a>
        </div>
    </section>

    <div class="rs-grid">

        {{-- ================= INFO BOOKING ================= --}}
        <aside class="rs-card">
            <h2>Booking saat ini</h2>

            <div class="rs-person">
                <span class="person-avatar {{ $isOffline ? 'offline' : '' }}">{{ mb_strtoupper(mb_substr($customerName, 0, 2)) }}</span>
                <div>
                    <strong>{{ $customerName }}</strong>
                    <small>{{ $customerEmail ?: 'Tanpa email' }}</small>
                    @if ($customerPhone)
                        <small>{{ $customerPhone }}</small>
                    @endif
                </div>
            </div>

            <div class="rs-rows">
                <div class="rs-row"><span>Tanggal</span><span>{{ $booking->booking_date->locale('id')->translatedFormat('l, d F Y') }}</span></div>
                <div class="rs-row"><span>Jam</span><span>{{ substr($booking->start_time, 0, 5) }} – {{ substr($booking->end_time, 0, 5) }}</span></div>
                <div class="rs-row"><span>Durasi</span><span>{{ $durationLabel }}</span></div>
                <div class="rs-row"><span>Status</span><span>{{ ucfirst($booking->status) }}</span></div>
                <div class="rs-row"><span>Tipe</span><span>{{ $isOffline ? 'Offline' : 'Online (member)' }}</span></div>
                @if ($payment)
                    <div class="rs-row"><span>Pembayaran</span><span>{{ $payment->status_label }} · {{ $payment->amount_label }}</span></div>
                @endif
            </div>

            @if ($currentBlocks->isNotEmpty())
                <div class="rs-warning">
                    ⊘ Jadwal ini terkena penutupan lapangan:
                    @foreach ($currentBlocks as $block)
                        <strong>{{ $block->time_label }}</strong> ({{ $block->reason }}){{ $loop->last ? '.' : ',' }}
                    @endforeach
                </div>
            @endif

            @if ($payment && $payment->status === 'paid')
                <div class="rs-info">Booking sudah lunas. Pilih jadwal baru dengan durasi yang sama ({{ $durationLabel }}).</div>
            @endif

            @if ($isOffline)
                <div class="rs-info">Customer offline tidak punya akun, jadi tidak menerima notifikasi otomatis. Hubungi customer setelah jadwal dipindah.</div>
            @endif

            @if ($waUrl)
                <div class="rs-contact">
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-outline">☎ Hubungi via WhatsApp</a>
                </div>
            @endif
        </aside>

        {{-- ================= PILIH JADWAL BARU ================= --}}
        <section class="rs-card">
            <h2>Pilih jadwal baru</h2>

            @if ($errors->any())
                <div class="error-box">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Ganti tanggal: muat ulang grid jam --}}
            <form method="GET" action="{{ route('admin.bookings.reschedule', $booking) }}" id="dateForm" class="field">
                <input type="hidden" name="from" value="{{ $from }}">
                <label for="fDate">Tanggal baru</label>
                <input type="date" name="date" id="fDate" class="input" min="{{ today()->format('Y-m-d') }}"
                       value="{{ $date->format('Y-m-d') }}" onchange="this.form.submit()">
            </form>

            <div class="rs-legend">
                <span><i style="background: #9cff38;"></i>Tersedia</span>
                <span><i style="background: #5ca8ff;"></i>Jadwal lama</span>
                <span><i style="background: #ff5c5c;"></i>Booked</span>
                <span><i style="background: #ffc400;"></i>Pending</span>
                <span><i style="background: #d8233d;"></i>Ditutup</span>
            </div>

            <div class="rs-slots">
                @foreach ($slots as $slot)
                    @if (in_array($slot['status'], ['available', 'current'], true))
                        <button type="button" class="rs-slot {{ $slot['status'] }}" data-start="{{ $slot['start'] }}">
                            {{ $slot['start'] }} – {{ $slot['end'] }}
                            <small>{{ $slotLabels[$slot['status']] }}</small>
                        </button>
                    @else
                        <div class="rs-slot {{ $slot['status'] }}">
                            {{ $slot['start'] }} – {{ $slot['end'] }}
                            <small>{{ $slotLabels[$slot['status']] }}</small>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Simpan jadwal baru --}}
            <form method="POST" action="{{ route('admin.bookings.reschedule.update', $booking) }}" id="rescheduleForm">
                @csrf
                @method('PUT')

                <input type="hidden" name="from" value="{{ $from }}">
                <input type="hidden" name="booking_date" value="{{ $date->format('Y-m-d') }}">

                <div class="field-row">
                    <div class="field">
                        <label for="fStart">Jam mulai</label>
                        <input type="time" name="start_time" id="fStart" class="input" required
                               min="07:00" max="19:30" step="1800" value="{{ $oldStart }}">
                    </div>
                    <div class="field">
                        <label for="fEnd">Jam selesai</label>
                        <input type="time" name="end_time" id="fEnd" class="input" required
                               min="07:30" max="20:00" step="1800" value="{{ $oldEnd }}">
                    </div>
                </div>

                <div class="rs-summary">
                    <span>Jadwal baru: <strong id="summaryText">pilih jam di atas</strong></span>
                    <span>Durasi lama: {{ $durationLabel }}</span>
                </div>

                <div class="rs-actions">
                    <a href="{{ $backUrl }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-primary">↻ Pindahkan jadwal</button>
                </div>
            </form>
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const duration = {{ (int) $duration }};
            const dateLabel = @json($date->locale('id')->translatedFormat('l, d M Y'));
            const start = document.getElementById('fStart');
            const end = document.getElementById('fEnd');
            const summary = document.getElementById('summaryText');
            const slots = document.querySelectorAll('button.rs-slot');

            const toMinutes = (t) => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
            const toTime = (m) => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');

            function refresh() {
                slots.forEach((slot) => {
                    const s = toMinutes(slot.dataset.start);
                    const on = start.value && end.value && s >= toMinutes(start.value) && s < toMinutes(end.value);
                    slot.classList.toggle('selected', Boolean(on));
                });

                summary.textContent = (start.value && end.value)
                    ? dateLabel + ' · ' + start.value + '–' + end.value
                    : 'pilih jam di atas';
            }

            // Klik jam: jam selesai otomatis sesuai durasi lama (maks. 20:00)
            slots.forEach((slot) => {
                slot.addEventListener('click', () => {
                    const s = toMinutes(slot.dataset.start);
                    start.value = slot.dataset.start;
                    end.value = toTime(Math.min(s + duration, 20 * 60));
                    refresh();
                });
            });

            [start, end].forEach((input) => input.addEventListener('change', refresh));

            document.getElementById('rescheduleForm').addEventListener('submit', (event) => {
                if (!confirm('Pindahkan jadwal ke ' + summary.textContent + '?')) event.preventDefault();
            });

            refresh();
        })();
    </script>
@endpush
