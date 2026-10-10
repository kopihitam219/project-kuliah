{{--
    Lonceng notifikasi (admin & customer).
    Pakai di navbar: @include('partials.notification-bell')
--}}
@auth
    @php
        $nbUser   = auth()->user();
        $nbItems  = $nbUser->notifications()->latest()->take(10)->get();
        $nbUnread = $nbUser->unreadNotifications()->count();
    @endphp

    @once
        <style>
            .nb { position: relative; display: inline-flex; font-family: Arial, Helvetica, sans-serif; }

            .nb-button {
                position: relative;
                width: 40px;
                height: 40px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(255, 255, 255, .12);
                border-radius: 50%;
                background: rgba(255, 255, 255, .04);
                color: #e4ece7;
                cursor: pointer;
                transition: border-color .2s ease, color .2s ease;
            }

            .nb-button:hover,
            .nb.open .nb-button { border-color: rgba(156, 255, 0, .5); color: #9cff38; }
            .nb-button svg { width: 19px; height: 19px; }

            .nb-badge {
                position: absolute;
                top: -5px;
                right: -6px;
                min-width: 19px;
                height: 19px;
                padding: 0 5px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                background: #ff4d5e;
                color: #ffffff;
                font-size: 10px;
                font-weight: 900;
                box-shadow: 0 0 0 2px #03150f;
            }

            .nb-panel {
                position: absolute;
                top: calc(100% + 10px);
                right: 0;
                z-index: 500;
                width: 340px;
                max-width: calc(100vw - 24px);
                border: 1px solid rgba(156, 255, 0, .22);
                border-radius: 12px;
                background: #03150f;
                box-shadow: 0 18px 45px rgba(0, 0, 0, .55);
                overflow: hidden;
                text-align: left;
            }

            .nb-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 13px 15px;
                border-bottom: 1px solid rgba(255, 255, 255, .08);
            }

            .nb-head strong { color: #ffffff; font-size: 14px; font-weight: 900; }
            .nb-head form { margin: 0; }

            .nb-read-all {
                border: 0;
                background: transparent;
                color: #9cff38;
                font-size: 11px;
                font-weight: 800;
                cursor: pointer;
            }

            .nb-list { max-height: 360px; overflow-y: auto; }

            .nb-item {
                display: flex;
                gap: 11px;
                padding: 12px 15px;
                border-bottom: 1px solid rgba(255, 255, 255, .05);
                color: rgba(255, 255, 255, .78);
                text-decoration: none;
                transition: background .15s ease;
            }

            .nb-item:last-child { border-bottom: 0; }
            .nb-item:hover { background: rgba(156, 255, 0, .06); }
            .nb-item.unread { background: rgba(156, 255, 0, .045); }

            .nb-icon {
                width: 30px;
                height: 30px;
                flex: 0 0 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                font-size: 13px;
                font-weight: 900;
            }

            .nb-icon.created     { background: rgba(92, 168, 255, .16); color: #8ec7ff; }
            .nb-icon.rescheduled { background: rgba(245, 174, 0, .16);  color: #ffc62d; }
            .nb-icon.cancelled,
            .nb-icon.rejected    { background: rgba(255, 77, 94, .16);  color: #ff8a96; }
            .nb-icon.approved    { background: rgba(67, 190, 77, .18);  color: #68ed62; }
            .nb-icon.paid        { background: rgba(184, 255, 0, .16);  color: #b8ff00; font-size: 10px; }

            .nb-text { min-width: 0; flex: 1; }
            .nb-text strong { display: flex; align-items: center; gap: 6px; color: #ffffff; font-size: 12px; font-weight: 800; }
            .nb-text p { margin-top: 3px; font-size: 11px; line-height: 1.45; color: rgba(255, 255, 255, .68); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            .nb-text small { display: block; margin-top: 5px; color: rgba(255, 255, 255, .38); font-size: 10px; }

            .nb-unread-dot { width: 7px; height: 7px; border-radius: 50%; background: #9cff38; }

            .nb-footer {
                display: block;
                padding: 12px 15px;
                border-top: 1px solid rgba(255, 255, 255, .08);
                color: #9cff38;
                font-size: 12px;
                font-weight: 800;
                text-align: center;
                text-decoration: none;
            }

            .nb-footer:hover { background: rgba(156, 255, 0, .06); }

            .nb-empty { padding: 30px 15px; text-align: center; color: rgba(255, 255, 255, .45); font-size: 12px; }
        </style>

        <script>
            document.addEventListener('click', function (event) {
                const button = event.target.closest('.nb-button');

                document.querySelectorAll('.nb.open').forEach(function (bell) {
                    if (!button || !bell.contains(button)) {
                        bell.classList.remove('open');
                        bell.querySelector('.nb-panel').hidden = true;
                        bell.querySelector('.nb-button').setAttribute('aria-expanded', 'false');
                    }
                });

                if (button) {
                    const bell  = button.closest('.nb');
                    const panel = bell.querySelector('.nb-panel');
                    const open  = panel.hidden;

                    panel.hidden = !open;
                    bell.classList.toggle('open', open);
                    button.setAttribute('aria-expanded', String(open));
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') return;
                document.querySelectorAll('.nb.open .nb-button').forEach(function (button) { button.click(); });
            });
        </script>
    @endonce

    @php
        $nbIcons = [
            'created'     => '＋',
            'rescheduled' => '↻',
            'cancelled'   => '×',
            'rejected'    => '×',
            'approved'    => '✓',
            'paid'        => 'Rp',
            'chat'        => '✉',
            'broadcast'   => '📢',
        ];
    @endphp

    <div class="nb">
        <button type="button" class="nb-button" aria-label="Notifikasi" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>

            @if ($nbUnread > 0)
                <span class="nb-badge">{{ $nbUnread > 99 ? '99+' : $nbUnread }}</span>
            @endif
        </button>

        <div class="nb-panel" hidden>
            <div class="nb-head">
                <strong>Notifikasi</strong>

                @if ($nbUnread > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="nb-read-all">Tandai semua dibaca</button>
                    </form>
                @endif
            </div>

            <div class="nb-list">
                @forelse ($nbItems as $nbItem)
                    @php
                        $nbEvent = $nbItem->data['event'] ?? 'created';
                    @endphp

                    <a href="{{ route('notifications.show', $nbItem->id) }}" class="nb-item {{ $nbItem->read_at ? '' : 'unread' }}">
                        <span class="nb-icon {{ $nbEvent }}">{{ $nbIcons[$nbEvent] ?? '•' }}</span>

                        <span class="nb-text">
                            <strong>
                                {{ $nbItem->data['title'] ?? 'Notifikasi' }}
                                @unless ($nbItem->read_at)
                                    <span class="nb-unread-dot"></span>
                                @endunless
                            </strong>
                            <p>{{ $nbItem->data['message'] ?? '' }}</p>
                            <small>{{ $nbItem->created_at?->locale('id')->diffForHumans() }}</small>
                        </span>
                    </a>
                @empty
                    <div class="nb-empty">Belum ada notifikasi.</div>
                @endforelse
            </div>

            <a href="{{ route('notifications.index') }}" class="nb-footer">Lihat semua notifikasi →</a>
        </div>
    </div>
@endauth
