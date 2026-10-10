{{--
    Menu bawah ala aplikasi untuk tampilan HP (<= 820px).
    Dipasang otomatis di semua halaman oleh App\Http\Middleware\MobileResponsive.
--}}
@php
    $mtUser = auth()->user();
    $mtRole = $mtUser->role ?? 'guest';
    $mtIs   = fn (...$names) => request()->routeIs(...$names);

    $mtIcons = [
        'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'calendar' => '<rect x="3" y="4.5" width="18" height="16.5" rx="3"/><path d="M8 2.5v4M16 2.5v4M3 10h18"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'flag'     => '<path d="M5 21V4"/><path d="M5 4h11l-2 4 2 4H5"/>',
        'image'    => '<rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
        'star'     => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
        'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M21.5 20a6.5 6.5 0 0 0-4-6"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'chat'     => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 11h.01M12 11h.01M15.5 11h.01"/>',
        'bell'     => '<path d="M6 16V11a6 6 0 1 1 12 0v5l2 2H4z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'logout'   => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l5-5-5-5M15 12H4"/>',
        'login'    => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="M14 17l5-5-5-5M19 12H8"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
    ];

    $mtSvg = fn ($name) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($mtIcons[$name] ?? '') . '</svg>';

    $mtUnread = 0;
    if ($mtUser) {
        try { $mtUnread = $mtUser->unreadNotifications()->count(); } catch (\Throwable $e) { $mtUnread = 0; }
    }

    $mtChat = 0;
    if ($mtUser && class_exists(\App\Models\ChatMessage::class) && \Illuminate\Support\Facades\Route::has('chat')) {
        try {
            $mtChat = $mtRole === 'admin' ? \App\Models\ChatMessage::unreadForAdmin() : \App\Models\ChatMessage::unreadForMember($mtUser->id);
        } catch (\Throwable $e) { $mtChat = 0; }
    }

    // [label, url, icon, aktif, utama, badge]
    if ($mtRole === 'admin') {
        $mtTabs = [
            ['Dashboard', route('admin.dashboard'), 'home', $mtIs('admin.dashboard', 'admin.bookings.*'), false],
            ['Chat', route('admin.chat.index'), 'chat', $mtIs('admin.chat.*'), false, $mtChat],
            ['Booking', route('admin.offline-booking.create'), 'plus', $mtIs('admin.offline-booking.*'), true],
            ['Customer', route('admin.customers.index'), 'users', $mtIs('admin.customers.*'), false],
        ];
        $mtSheet = [
            ['Jadwal', route('admin.schedule-blocks.index'), 'clock'],
            ['Coach', route('admin.coaches.index'), 'user'],
            ['Galeri', route('admin.gallery.index'), 'image'],
            ['Event', route('admin.events.index'), 'flag'],
            ['Program', route('admin.programs.index'), 'star'],
            ['Contact', route('admin.contact.index'), 'phone'],
            ['Notifikasi', route('notifications.index'), 'bell'],
            ['Settings', route('admin.settings.index'), 'settings'],
            ['Lihat website', route('home'), 'grid'],
        ];
    } elseif ($mtRole === 'customer') {
        $mtTabs = [
            ['Beranda', route('dashboard'), 'home', $mtIs('dashboard', 'home'), false],
            ['Chat', route('chat'), 'chat', $mtIs('chat'), false, $mtChat],
            ['Booking', route('booking'), 'calendar', $mtIs('booking', 'payment', 'payment.*'), true],
            ['Event', route('event'), 'flag', $mtIs('event'), false],
        ];
        $mtSheet = [
            ['Program', route('program'), 'star'],
            ['Notifikasi', route('notifications.index'), 'bell'],
            ['Galeri', route('galeri'), 'image'],
            ['Kontak', route('contact'), 'phone'],
            ['Profil saya', route('profile.edit'), 'user'],
        ];
    } else {
        $mtTabs = [
            ['Home', route('home'), 'home', $mtIs('home'), false],
            ['Program', route('program'), 'star', $mtIs('program'), false],
            ['Booking', route('login'), 'calendar', false, true],
            ['Galeri', route('galeri'), 'image', $mtIs('galeri'), false],
        ];
        $mtSheet = [
            ['Event', route('event'), 'flag'],
            ['Kontak', route('contact'), 'phone'],
            ['Masuk', route('login'), 'login'],
            ['Daftar akun', route('register'), 'user'],
        ];
    }
@endphp

<style>
    .mt-bar, .mt-sheet, .mt-backdrop { display: none; }

    @media (max-width: 820px) {
        body { padding-bottom: calc(84px + env(safe-area-inset-bottom)) !important; }

        .mt-bar {
            position: fixed; left: 10px; right: 10px; bottom: calc(10px + env(safe-area-inset-bottom)); z-index: 250;
            display: grid; grid-template-columns: repeat(5, 1fr); align-items: end;
            height: 64px; padding: 0 6px 8px;
            border: 1px solid rgba(156, 255, 0, .16); border-radius: 22px;
            background: rgba(4, 16, 11, .88);
            -webkit-backdrop-filter: blur(16px) saturate(140%); backdrop-filter: blur(16px) saturate(140%);
            box-shadow: 0 12px 34px rgba(0, 0, 0, .55), inset 0 1px 0 rgba(255, 255, 255, .05);
            font-family: Arial, Helvetica, sans-serif;
        }
        .mt-item {
            position: relative; display: flex; flex-direction: column; align-items: center; gap: 4px;
            padding-top: 8px; border: 0; background: none;
            color: rgba(228, 236, 231, .62); font-size: 10.5px; font-weight: 700; letter-spacing: .1px;
            text-decoration: none; cursor: pointer; -webkit-tap-highlight-color: transparent;
        }
        .mt-item svg { width: 23px; height: 23px; transition: transform .2s ease; }
        .mt-item.active { color: #9cff38; }
        .mt-item.active::before {
            content: ""; position: absolute; top: 0; width: 22px; height: 3px; border-radius: 0 0 4px 4px; background: #9cff38;
        }
        .mt-item:active svg { transform: scale(.88); }

        .mt-item.mt-main { gap: 5px; }
        .mt-item.mt-main .mt-fab {
            width: 54px; height: 54px; margin-top: -30px;
            display: grid; place-items: center; border-radius: 18px;
            background: linear-gradient(145deg, #b8ff4d, #7bdc1f); color: #062010;
            box-shadow: 0 10px 22px rgba(124, 220, 31, .38), 0 0 0 5px rgba(4, 16, 11, .95);
        }
        .mt-item.mt-main .mt-fab svg { width: 25px; height: 25px; stroke-width: 2.3; }
        .mt-item.mt-main.active { color: #9cff38; }
        .mt-item.mt-main.active::before { display: none; }

        .mt-dot {
            position: absolute; top: 4px; left: calc(50% + 6px); min-width: 17px; height: 17px; padding: 0 4px;
            display: grid; place-items: center; border-radius: 99px; background: #ff4d5e; color: #fff;
            font-size: 9.5px; font-weight: 900; box-shadow: 0 0 0 2px rgba(4, 16, 11, .95);
        }

        .mt-backdrop {
            position: fixed; inset: 0; z-index: 251; display: block;
            background: rgba(0, 0, 0, .55); opacity: 0; pointer-events: none; transition: opacity .2s ease;
        }
        .mt-sheet {
            position: fixed; left: 10px; right: 10px; bottom: calc(84px + env(safe-area-inset-bottom)); z-index: 252;
            display: block; max-height: 70vh; overflow-y: auto; padding: 14px;
            border: 1px solid rgba(156, 255, 0, .18); border-radius: 22px;
            background: rgba(6, 22, 15, .97); box-shadow: 0 20px 50px rgba(0, 0, 0, .6);
            font-family: Arial, Helvetica, sans-serif; color: #fff;
            transform: translateY(16px); opacity: 0; pointer-events: none; transition: transform .22s ease, opacity .22s ease;
        }
        body.mt-open .mt-backdrop { opacity: 1; pointer-events: auto; }
        body.mt-open .mt-sheet { transform: none; opacity: 1; pointer-events: auto; }

        .mt-sheet-head { display: flex; align-items: center; gap: 12px; padding: 4px 4px 14px; margin-bottom: 10px; border-bottom: 1px solid rgba(255, 255, 255, .07); }
        .mt-avatar { width: 42px; height: 42px; flex: 0 0 42px; display: grid; place-items: center; border-radius: 14px; background: #9cff38; color: #062010; font-size: 18px; font-weight: 900; }
        .mt-sheet-head strong { display: block; font-size: 15px; }
        .mt-sheet-head small { display: block; margin-top: 2px; color: rgba(255, 255, 255, .5); font-size: 11.5px; }

        .mt-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .mt-tile {
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 7px;
            min-height: 78px; padding: 10px 6px; border: 1px solid rgba(255, 255, 255, .07); border-radius: 16px;
            background: rgba(255, 255, 255, .035); color: #e4ece7; font-size: 11.5px; font-weight: 700; text-align: center; text-decoration: none;
        }
        .mt-tile svg { width: 22px; height: 22px; color: #9cff38; }
        .mt-tile:active { background: rgba(156, 255, 0, .1); }

        .mt-logout { margin-top: 10px; }
        .mt-logout button {
            width: 100%; height: 46px; display: flex; align-items: center; justify-content: center; gap: 8px;
            border: 1px solid rgba(255, 90, 90, .35); border-radius: 14px; background: rgba(255, 90, 90, .08);
            color: #ff9b9b; font-family: inherit; font-size: 13px; font-weight: 800; cursor: pointer;
        }
        .mt-logout svg { width: 18px; height: 18px; }
    }

    @media print { .mt-bar, .mt-sheet, .mt-backdrop { display: none !important; } }
</style>

<div class="mt-backdrop" data-mt-close></div>

<div class="mt-sheet" id="mtSheet" role="dialog" aria-label="Menu">
    @if ($mtUser)
        <div class="mt-sheet-head">
            <div class="mt-avatar">{{ mb_strtoupper(mb_substr($mtUser->name, 0, 1)) }}</div>
            <div>
                <strong>{{ $mtUser->name }}</strong>
                <small>{{ $mtRole === 'admin' ? 'Administrator' : $mtUser->email }}</small>
            </div>
        </div>
    @endif

    <div class="mt-grid">
        @foreach ($mtSheet as [$label, $url, $icon])
            <a href="{{ $url }}" class="mt-tile">{!! $mtSvg($icon) !!}{{ $label }}</a>
        @endforeach
    </div>

    @if ($mtUser)
        <form method="POST" action="{{ route('logout') }}" class="mt-logout">
            @csrf
            <button type="submit">{!! $mtSvg('logout') !!} Logout</button>
        </form>
    @endif
</div>

<nav class="mt-bar" aria-label="Menu utama">
    @foreach ($mtTabs as $mtTab)
        @php [$label, $url, $icon, $active, $main] = $mtTab; $badge = (int) ($mtTab[5] ?? 0); @endphp
        <a href="{{ $url }}" class="mt-item {{ $active ? 'active' : '' }} {{ $main ? 'mt-main' : '' }}">
            @if ($main)
                <span class="mt-fab">{!! $mtSvg($icon) !!}</span>
            @else
                {!! $mtSvg($icon) !!}
            @endif
            {{ $label }}
            @if ($badge > 0)<span class="mt-dot">{{ $badge > 9 ? '9+' : $badge }}</span>@endif
        </a>
    @endforeach

    <button type="button" class="mt-item" data-mt-toggle aria-controls="mtSheet" aria-expanded="false">
        {!! $mtSvg('menu') !!}
        Menu
        @if ($mtUnread > 0)
            <span class="mt-dot">{{ $mtUnread > 9 ? '9+' : $mtUnread }}</span>
        @endif
    </button>
</nav>

<script>
    (function () {
        var body = document.body;
        var toggle = document.querySelector('[data-mt-toggle]');
        function set(open) {
            body.classList.toggle('mt-open', open);
            if (toggle) toggle.setAttribute('aria-expanded', String(open));
        }
        if (toggle) toggle.addEventListener('click', function () { set(!body.classList.contains('mt-open')); });
        document.querySelectorAll('[data-mt-close]').forEach(function (el) { el.addEventListener('click', function () { set(false); }); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
    })();
</script>
