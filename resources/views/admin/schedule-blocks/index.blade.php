@extends('admin.layouts.panel')

@section('title', 'Kelola Jadwal')

@push('styles')
    <style>
        .sb-grid { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 18px; align-items: start; }

        .sb-card { padding: 20px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--panel); }

        .sb-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .sb-card-head h2 { font-size: 16px; font-weight: 900; }
        .sb-card-head p { margin-top: 4px; color: var(--text-muted); font-size: 11px; line-height: 1.5; }

        /* ---------- Form ---------- */
        .sb-form .field { margin-bottom: 14px; }

        .sb-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            padding: 11px 13px;
            border: 1px solid var(--line);
            border-radius: 9px;
            background: var(--d-surface-2, #f8f7f2);
            cursor: pointer;
        }

        .sb-switch strong { display: block; font-size: 12px; }
        .sb-switch small { display: block; margin-top: 2px; color: var(--text-muted); font-size: 10px; }

        .sb-switch input { position: absolute; opacity: 0; pointer-events: none; }

        .sb-toggle {
            position: relative;
            width: 38px;
            height: 22px;
            flex: 0 0 38px;
            border-radius: 999px;
            background: rgba(var(--d-ink-rgb, 23, 46, 33), 0.060);
            transition: background .2s ease;
        }

        .sb-toggle::after {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--d-surface, #ffffff);
            transition: transform .2s ease;
        }

        .sb-switch input:checked + .sb-toggle { background: var(--lime); }
        .sb-switch input:checked + .sb-toggle::after { transform: translateX(16px); background: var(--ink-dark); }
        .sb-switch input:focus-visible + .sb-toggle { outline: 2px solid var(--lime); outline-offset: 2px; }

        .sb-form-actions { display: grid; grid-template-columns: auto 1fr; gap: 8px; margin-top: 4px; }

        /* ---------- Daftar ---------- */
        .sb-list { display: grid; gap: 10px; }

        .sb-item {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 13px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--d-surface-2, #f8f7f2);
        }

        .sb-date {
            padding: 7px 0;
            border-radius: 9px;
            background: rgba(216, 35, 61, .12);
            border: 1px solid rgba(216, 35, 61, .3);
            text-align: center;
            color: var(--d-red-ink, #c9413a);
        }

        .sb-date strong { display: block; font-size: 20px; font-weight: 900; line-height: 1; }
        .sb-date small { display: block; margin-top: 3px; font-size: 9px; font-weight: 800; text-transform: uppercase; }

        .sb-info { min-width: 0; }
        .sb-info strong { display: block; font-size: 13px; }
        .sb-time { margin-top: 3px; color: var(--d-orange-ink, #a2650c); font-size: 11px; font-weight: 800; }
        .sb-meta { margin-top: 3px; color: var(--text-muted); font-size: 10px; }

        .sb-conflict {
            margin-top: 8px;
            padding: 8px 10px;
            border-radius: 8px;
            background: rgba(245, 174, 0, .08);
            border: 1px solid rgba(245, 174, 0, .28);
            color: var(--d-orange-ink, #8a5608);
            font-size: 11px;
            line-height: 1.6;
        }

        .sb-conflict ul { margin-top: 6px; padding: 0; list-style: none; display: grid; gap: 6px; color: var(--d-text-2, #3c4a42); }
        .sb-conflict li { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }

        .sb-resched {
            padding: 5px 10px;
            border-radius: 6px;
            background: #a2650c;
            color: var(--d-text, #17261d);
            font-size: 10px;
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
        }

        .sb-resched:hover { background: #ffd866; }

        .sb-cancel {
            height: 32px;
            padding: 0 13px;
            border: 1px solid rgba(216, 35, 61, .45);
            border-radius: 7px;
            background: transparent;
            color: var(--d-red-ink, #c9413a);
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .sb-cancel:hover { background: rgba(216, 35, 61, .15); color: var(--d-text, #17261d); }

        .sb-past { opacity: .6; }

        @media (max-width: 1050px) { .sb-grid { grid-template-columns: 1fr; } }

        @media (max-width: 560px) {
            .sb-item { grid-template-columns: 52px minmax(0, 1fr); }
            .sb-item form { grid-column: 1 / -1; }
            .sb-cancel { width: 100%; }
        }
    </style>
@endpush

@section('content')
    <section class="page-head">
        <div>
            <h1>Kelola Jadwal</h1>
            <p>Tutup hari atau jam tertentu untuk acara mendadak, turnamen, atau perawatan lapangan. Jam yang ditutup tidak bisa di-booking customer.</p>
        </div>
    </section>

    <div class="sb-grid">

        {{-- ================= FORM TUTUP JADWAL ================= --}}
        <form method="POST" action="{{ route('admin.schedule-blocks.store') }}" class="sb-card sb-form" id="blockForm">
            @csrf

            <div class="sb-card-head">
                <div>
                    <h2>Tutup jadwal</h2>
                    <p>Customer akan melihat jam ini sebagai "Ditutup".</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="error-box">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="field">
                <label for="fDate">Tanggal</label>
                <input type="date" name="date" id="fDate" required class="input"
                       min="{{ today()->format('Y-m-d') }}" value="{{ old('date') }}">
            </div>

            <input type="hidden" name="all_day" value="0">
            <label class="sb-switch">
                <span>
                    <strong>Tutup seharian</strong>
                    <small>Semua jam pada tanggal ini tidak bisa di-booking</small>
                </span>
                <input type="checkbox" name="all_day" id="fAllDay" value="1" @checked(old('all_day'))>
                <span class="sb-toggle"></span>
            </label>

            <div class="field-row" id="timeFields">
                <div class="field">
                    <label for="fStart">Jam mulai</label>
                    <input type="time" name="start_time" id="fStart" class="input" min="07:00" max="20:00" step="1800"
                           value="{{ old('start_time') }}">
                </div>
                <div class="field">
                    <label for="fEnd">Jam selesai</label>
                    <input type="time" name="end_time" id="fEnd" class="input" min="07:00" max="20:00" step="1800"
                           value="{{ old('end_time') }}">
                </div>
            </div>

            <div class="field">
                <label for="fReason">Alasan</label>
                <input type="text" name="reason" id="fReason" required maxlength="150" class="input"
                       value="{{ old('reason') }}" placeholder="Contoh: Turnamen internal">
            </div>

            <div class="sb-form-actions">
                <button type="reset" class="btn btn-ghost" id="btnReset">Reset</button>
                <button type="submit" class="btn btn-primary">Tutup jadwal ini</button>
            </div>
        </form>

        {{-- ================= DAFTAR JADWAL DITUTUP ================= --}}
        <section class="sb-card">
            <div class="sb-card-head">
                <div>
                    <h2>{{ $showPast ? 'Riwayat jadwal ditutup' : 'Jadwal ditutup' }}</h2>
                    <p>
                        @if ($showPast)
                            Jadwal tutup yang tanggalnya sudah lewat.
                        @else
                            {{ $upcomingCount }} jadwal ditutup mulai hari ini. Klik <strong>Batalkan</strong> untuk membuka jam tersebut lagi.
                        @endif
                    </p>
                </div>

                <a href="{{ route('admin.schedule-blocks.index', $showPast ? [] : ['show' => 'past']) }}" class="btn btn-ghost">
                    {{ $showPast ? '← Mendatang' : 'Riwayat' }}
                </a>
            </div>

            @if ($blocks->isEmpty())
                <div class="empty-state">
                    {{ $showPast ? 'Belum ada riwayat.' : 'Tidak ada jadwal yang ditutup. Semua jam bisa di-booking.' }}
                </div>
            @else
                <div class="sb-list">
                    @foreach ($blocks as $block)
                        <article class="sb-item {{ $showPast ? 'sb-past' : '' }}">
                            <div class="sb-date">
                                <strong>{{ $block->date->format('d') }}</strong>
                                <small>{{ $block->date->locale('id')->translatedFormat('M Y') }}</small>
                            </div>

                            <div class="sb-info">
                                <strong>{{ $block->reason }}</strong>
                                <div class="sb-time">⊘ {{ $block->date->locale('id')->translatedFormat('l') }} · {{ $block->time_label }}</div>
                                <div class="sb-meta">Ditutup {{ $block->created_at?->locale('id')->diffForHumans() }}</div>

                                @if ($block->conflicts->isNotEmpty())
                                    <div class="sb-conflict">
                                        ⚠ {{ $block->conflicts->count() }} booking aktif bentrok. Pindahkan jadwalnya:
                                        <ul>
                                            @foreach ($block->conflicts as $conflict)
                                                <li>
                                                    <span>
                                                        {{ $conflict->user->name ?? $conflict->offline_customer_name ?? 'Customer' }}
                                                        · {{ substr($conflict->start_time, 0, 5) }}–{{ substr($conflict->end_time, 0, 5) }}
                                                        · {{ ucfirst($conflict->status) }}
                                                        {{ $conflict->user_id ? '' : '(offline)' }}
                                                    </span>
                                                    <a href="{{ route('admin.bookings.reschedule', ['booking' => $conflict, 'from' => 'schedule']) }}" class="sb-resched">↻ Reschedule</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            @unless ($showPast)
                                <form method="POST" action="{{ route('admin.schedule-blocks.destroy', $block) }}"
                                      data-confirm="Batalkan penutupan jadwal {{ $block->date->locale('id')->translatedFormat('d M Y') }} ({{ $block->time_label }})?&#10;&#10;Jam tersebut akan bisa di-booking customer lagi.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sb-cancel">✕ Batalkan</button>
                                </form>
                            @endunless
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const allDay = document.getElementById('fAllDay');
            const times  = document.getElementById('timeFields');
            const start  = document.getElementById('fStart');
            const end    = document.getElementById('fEnd');

            // Sembunyikan jam jika "Tutup seharian" aktif
            const sync = () => {
                times.hidden = allDay.checked;
                start.disabled = end.disabled = allDay.checked;
                start.required = end.required = !allDay.checked;
            };

            allDay.addEventListener('change', sync);
            document.getElementById('btnReset').addEventListener('click', () => setTimeout(sync, 0));
            sync();

            document.querySelectorAll('form[data-confirm]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (!confirm(form.dataset.confirm)) event.preventDefault();
                });
            });
        })();
    </script>
@endpush
