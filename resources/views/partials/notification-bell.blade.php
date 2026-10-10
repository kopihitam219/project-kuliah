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
            .nb { position: relative; display: inline-flex; font-family: var(--fw-sans, Inter, Arial, sans-serif); }
            .nb-button { position: relative; width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .1); border-radius: 50%; background: var(--d-surface, #fff); color: var(--d-text, #17261d); cursor: pointer; transition: border-color .2s, color .2s; }
            .nb-button:hover, .nb.open .nb-button { border-color: var(--d-ink-green, #1f4d33); color: var(--d-ink-green, #1f4d33); }
            .nb-button svg { width: 19px; height: 19px; }
            .nb-badge { position: absolute; top: -4px; right: -5px; min-width: 19px; height: 19px; padding: 0 5px; display: flex; align-items: center; justify-content: center; border-radius: 999px; background: #c9413a; color: #fff; font-size: 10px; font-weight: 700; box-shadow: 0 0 0 2px #f3f1ea; }
            .nb-panel { position: absolute; top: calc(100% + 10px); right: 0; z-index: 500; width: 360px; max-width: calc(100vw - 24px); border: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .1); border-radius: 18px; background: var(--d-surface, #fff); box-shadow: 0 18px 50px rgba(var(--d-shadow-rgb, 23, 46, 33), .16); overflow: hidden; text-align: left; color: var(--d-text, #17261d); }
            .nb-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 16px; border-bottom: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); }
            .nb-head strong { font-size: 15px; font-weight: 700; }
            .nb-head form { margin: 0; }
            .nb-read-all { border: 0; background: transparent; color: var(--d-ink-green, #1f4d33); font-size: 12px; font-weight: 600; cursor: pointer; }
            .nb-list { max-height: 380px; overflow-y: auto; }
            .nb-item { display: flex; gap: 12px; padding: 12px 16px; border-bottom: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .06); color: var(--d-text-2, #3c4a42); text-decoration: none; transition: background .15s; }
            .nb-item:last-child { border-bottom: 0; }
            .nb-item:hover { background: var(--d-surface-2, #f8f7f2); }
            .nb-item.unread { background: var(--d-tint-2, #f0f5ef); }
            .nb-icon { width: 34px; height: 34px; flex: 0 0 34px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--d-tint, #e5eee6); color: var(--d-ink-green, #1f4d33); font-size: 13px; font-weight: 700; }
            .nb-icon.created { background: var(--d-blue-tint, #e6eef9); color: var(--d-blue-ink, #3b6fb6); }
            .nb-icon.rescheduled { background: var(--d-orange-tint, #fdf1de); color: var(--d-orange-ink, #a2650c); }
            .nb-icon.cancelled, .nb-icon.rejected { background: var(--d-red-tint, #fbe7e5); color: var(--d-red-ink, #c9413a); }
            .nb-icon.approved, .nb-icon.paid { background: var(--d-tint, #e5eee6); color: var(--d-ink-green, #1f4d33); }
            .nb-icon.paid { font-size: 10px; }
            .nb-text { min-width: 0; flex: 1; }
            .nb-text strong { display: flex; align-items: center; gap: 6px; color: var(--d-text, #17261d); font-size: 13px; font-weight: 600; }
            .nb-text p { margin: 3px 0 0; font-size: 12px; line-height: 1.45; color: var(--d-muted, #6b776f); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            .nb-text small { display: block; margin-top: 5px; color: var(--d-muted, #9aa59e); font-size: 11px; }
            .nb-unread-dot { width: 7px; height: 7px; border-radius: 50%; background: #e9a23b; }
            .nb-footer { display: block; padding: 13px 16px; border-top: 1px solid rgba(var(--d-ink-rgb, 23, 46, 33), .08); color: var(--d-ink-green, #1f4d33); font-size: 13px; font-weight: 600; text-align: center; text-decoration: none; }
            .nb-footer:hover { background: var(--d-surface-2, #f8f7f2); }
            .nb-empty { padding: 30px 16px; text-align: center; color: var(--d-muted, #77837b); font-size: 13px; }
            @media (max-width: 820px) { .nb-panel { position: fixed; top: 70px; left: 12px; right: 12px; width: auto; } }
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
