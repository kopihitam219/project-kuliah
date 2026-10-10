@extends('admin.layouts.panel')

@section('title', 'Chat Member')

@section('content')
    <style>
        .ac-grid { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 16px; align-items: start; }
        .ac-list { border: 1px solid var(--border); border-radius: 16px; background: var(--panel); overflow: hidden; }
        .ac-search { padding: 12px; border-bottom: 1px solid var(--line); }
        .ac-search input { width: 100%; height: 42px; padding: 0 12px; border: 1px solid var(--line); border-radius: 11px; background: rgba(255, 255, 255, .04); color: var(--text); font-size: 14px; }
        .ac-items { max-height: 62vh; overflow-y: auto; }
        .ac-item { display: grid; grid-template-columns: 40px minmax(0, 1fr) auto; gap: 10px; align-items: center; padding: 12px 14px; border-bottom: 1px solid var(--line); color: var(--text); text-decoration: none; }
        .ac-item:hover { background: rgba(156, 255, 0, .05); }
        .ac-item.on { background: rgba(156, 255, 0, .1); }
        .ac-av { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 14px; background: rgba(49, 185, 255, .25); color: #cfeeff; font-weight: 900; }
        .ac-item strong { display: block; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ac-item small { display: block; color: var(--text-muted); font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ac-side { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; color: var(--text-muted); font-size: 11px; }
        .ac-badge { min-width: 20px; height: 20px; padding: 0 6px; display: grid; place-items: center; border-radius: 99px; background: var(--lime); color: var(--ink-dark); font-size: 11px; font-weight: 900; }
        .ac-thread { height: max(420px, min(calc(100vh - 300px), 680px)); }
        .ac-placeholder { display: grid; place-items: center; height: min(72vh, 680px); border: 1px dashed var(--border); border-radius: 16px; color: var(--text-muted); text-align: center; padding: 24px; }
        .ac-bc { margin-bottom: 16px; border: 1px solid rgba(156, 255, 0, .3); border-radius: 16px; background: linear-gradient(120deg, rgba(156, 255, 56, .1), rgba(156, 255, 56, .02)); }
        .ac-bc summary { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; cursor: pointer; list-style: none; font-weight: 800; }
        .ac-bc summary::-webkit-details-marker { display: none; }
        .ac-bc summary span { color: var(--text-muted); font-weight: 600; font-size: 12.5px; }
        .ac-bc form { display: grid; gap: 10px; padding: 0 16px 16px; }
        .ac-bc input[type=text], .ac-bc input[type=search], .ac-bc textarea { width: 100%; padding: 11px 12px; border: 1px solid var(--line); border-radius: 11px; background: rgba(0, 0, 0, .2); color: var(--text); font: 14px/1.5 Arial, Helvetica, sans-serif; }
        .ac-bc textarea { min-height: 110px; resize: vertical; }
        .ac-bc .row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; color: var(--text-muted); font-size: 12.5px; }
        .ac-history { margin-top: 6px; display: grid; gap: 6px; padding: 0 16px 16px; }
        .ac-history div { padding: 9px 12px; border-radius: 11px; background: rgba(255, 255, 255, .03); font-size: 12.5px; color: var(--text-soft); }
        .ac-history b { color: var(--text); }
        .ac-back { display: none; }
        .ac-target { display: flex; flex-wrap: wrap; gap: 8px; }
        .ac-target label { display: inline-flex; align-items: center; gap: 8px; padding: 9px 14px; border: 1px solid var(--line); border-radius: 11px; color: var(--text-soft); font-size: 13px; font-weight: 700; cursor: pointer; }
        .ac-target label:has(input:checked) { border-color: var(--lime); color: var(--lime); background: rgba(156, 255, 0, .08); }
        .ac-target input { accent-color: var(--lime); }
        .ac-pick { display: none; border: 1px solid var(--line); border-radius: 12px; background: rgba(0, 0, 0, .2); }
        .ac-pick.show { display: block; }
        .ac-pick-top { display: flex; gap: 8px; align-items: center; padding: 10px; border-bottom: 1px solid var(--line); }
        .ac-pick-top input { flex: 1; height: 38px; padding: 0 10px; }
        .ac-pick-top button { height: 38px; padding: 0 12px; border: 1px solid var(--line); border-radius: 10px; background: transparent; color: var(--text-soft); font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap; }
        .ac-pick-list { max-height: 230px; overflow-y: auto; padding: 6px; }
        .ac-pick-list label { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 9px; cursor: pointer; font-size: 13px; }
        .ac-pick-list label:hover { background: rgba(156, 255, 0, .05); }
        .ac-pick-list input { width: 16px; height: 16px; accent-color: var(--lime); }
        .ac-pick-list small { color: var(--text-muted); }
        .ac-count { color: var(--lime); font-weight: 800; }
        .ac-start { display: flex; gap: 8px; padding: 12px; border-bottom: 1px solid var(--line); }
        .ac-start select { flex: 1; min-width: 0; height: 40px; padding: 0 10px; border: 1px solid var(--line); border-radius: 11px; background: #07150f; color: var(--text); font-size: 13px; }
        .ac-start button { height: 40px; padding: 0 14px; border: 0; border-radius: 11px; background: var(--lime); color: var(--ink-dark); font-weight: 800; font-size: 13px; cursor: pointer; }
        @media (max-width: 900px) {
            .ac-grid { grid-template-columns: minmax(0, 1fr); }
            .ac-grid.has-active .ac-list { display: none; }
            .ac-grid:not(.has-active) .ac-main { display: none; }
            .ac-back { display: inline-flex; margin-bottom: 10px; }
            .ac-thread { height: calc(100vh - 60px - 210px); min-height: 380px; }
            .ac-items { max-height: none; }
        }
    </style>

    <section class="page-head">
        <div>
            <h1>Chat Member</h1>
            <p>Anda membalas sebagai <b>{{ $coachName }}</b> (coach). Pilih member untuk chat, atau kirim pesan ke semua / beberapa member sekaligus. Pesan juga masuk ke lonceng notifikasi member.</p>
        </div>
    </section>

    <details class="ac-bc" @if ($errors->has('title') || $errors->has('body') || old('target')) open @endif>
        <summary>📢 Kirim pesan ke beberapa / semua member <span>Pilih penerima</span></summary>
        <form method="POST" action="{{ route('admin.chat.broadcast') }}" id="bcForm">
            @csrf
            @if ($errors->has('title') || $errors->has('body'))
                <div class="error-box"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <div class="ac-target">
                <label><input type="radio" name="target" value="all" @checked(old('target', 'all') === 'all')> Semua member ({{ $memberCount }})</label>
                <label><input type="radio" name="target" value="selected" @checked(old('target') === 'selected')> Pilih member</label>
            </div>
            <div class="ac-pick {{ old('target') === 'selected' ? 'show' : '' }}" id="acPick">
                <div class="ac-pick-top">
                    <input type="search" id="acPickSearch" placeholder="Cari nama / email...">
                    <button type="button" id="acPickAll">Pilih semua</button>
                    <button type="button" id="acPickNone">Kosongkan</button>
                </div>
                <div class="ac-pick-list">
                    @foreach ($allMembers as $m)
                        <label data-q="{{ mb_strtolower($m->name . ' ' . $m->email) }}">
                            <input type="checkbox" name="member_ids[]" value="{{ $m->id }}" @checked(in_array($m->id, (array) old('member_ids', [])))>
                            <span>{{ $m->name }} <small>{{ $m->email }}</small></span>
                        </label>
                    @endforeach
                </div>
            </div>
            <input type="text" name="title" maxlength="120" required placeholder="Judul, contoh: Driving range Suvarna tutup Sabtu" value="{{ old('title') }}">
            <textarea name="body" maxlength="2000" required placeholder="Isi pengumuman untuk semua member...">{{ old('body') }}</textarea>
            <div class="row">
                <span>Penerima: <b class="ac-count" id="acCount">{{ $memberCount }} member</b> · masuk ke Chat & Notifikasi.</span>
                <button type="submit" class="btn btn-primary" id="bcSend">Kirim pesan</button>
            </div>
        </form>
        @if ($broadcasts->isNotEmpty())
            <div class="ac-history">
                @foreach ($broadcasts as $bc)
                    <div><b>{{ $bc->title }}</b> · {{ $bc->recipients }} member · {{ $bc->created_at->timezone(config('app.timezone'))->locale('id')->translatedFormat('d M Y, H:i') }}</div>
                @endforeach
            </div>
        @endif
    </details>

    <div class="ac-grid {{ $active ? 'has-active' : '' }}">
        <aside class="ac-list">
            <form class="ac-start" method="GET" action="{{ route('admin.chat.index') }}">
                <select name="member" required aria-label="Pilih member untuk chat">
                    <option value="">Mulai chat dengan...</option>
                    @foreach ($allMembers as $m)<option value="{{ $m->id }}" @selected($active && $active->id === $m->id)>{{ $m->name }}</option>@endforeach
                </select>
                <button type="submit">Chat</button>
            </form>
            <form class="ac-search" method="GET" action="{{ route('admin.chat.index') }}">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau email member...">
            </form>
            <div class="ac-items">
                @forelse ($members as $m)
                    <a href="{{ route('admin.chat.index', array_filter(['member' => $m->id, 'q' => $search ?: null])) }}" class="ac-item {{ $active && $active->id === $m->id ? 'on' : '' }}">
                        <span class="ac-av">{{ mb_strtoupper(mb_substr($m->name, 0, 2)) }}</span>
                        <span style="min-width:0">
                            <strong>{{ $m->name }}</strong>
                            <small>{{ $m->last_body ? (($m->last_from === 'admin' && ! str_starts_with($m->last_body, '📢') ? 'Anda: ' : '') . \Illuminate\Support\Str::limit($m->last_body, 40)) : $m->email }}</small>
                        </span>
                        <span class="ac-side">
                            @if ($m->last_at) {{ $m->last_at->timezone(config('app.timezone'))->format('d/m H:i') }} @endif
                            @if ((int) $m->unread_chat > 0) <span class="ac-badge">{{ $m->unread_chat }}</span> @endif
                        </span>
                    </a>
                @empty
                    <p style="padding:16px;color:var(--text-muted)">Member tidak ditemukan.</p>
                @endforelse
            </div>
        </aside>

        <div class="ac-main">
            @if ($active)
                <a href="{{ route('admin.chat.index') }}" class="btn btn-ghost ac-back">← Semua chat</a>
                <div class="ac-thread">
                    @include('partials.chat-thread', [
                        'messages'  => $messages,
                        'pollUrl'   => route('admin.chat.poll', $active),
                        'sendUrl'   => route('admin.chat.send', $active),
                        'viewer'    => 'admin',
                        'peerName'  => $active->name,
                        'emptyText' => 'Belum ada percakapan dengan ' . $active->name . '. Kirim pesan pertama.',
                    ])
                </div>
            @else
                <div class="ac-placeholder">Pilih member di sebelah kiri untuk membuka percakapan.</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('bcForm');
            if (!form) return;
            const pick = document.getElementById('acPick');
            const count = document.getElementById('acCount');
            const total = {{ $memberCount }};
            const boxes = () => [...pick.querySelectorAll('input[type=checkbox]')];
            const selected = () => form.querySelector('input[name=target]:checked')?.value === 'selected';
            const n = () => selected() ? boxes().filter(b => b.checked).length : total;
            const refresh = () => { pick.classList.toggle('show', selected()); count.textContent = n() + ' member'; };
            form.addEventListener('change', refresh);
            document.getElementById('acPickSearch').addEventListener('input', (e) => {
                const q = e.target.value.trim().toLowerCase();
                pick.querySelectorAll('label[data-q]').forEach(l => { l.style.display = !q || l.dataset.q.includes(q) ? '' : 'none'; });
            });
            document.getElementById('acPickAll').addEventListener('click', () => { pick.querySelectorAll('label[data-q]').forEach(l => { if (l.style.display !== 'none') l.querySelector('input').checked = true; }); refresh(); });
            document.getElementById('acPickNone').addEventListener('click', () => { boxes().forEach(b => b.checked = false); refresh(); });
            refresh();
            form.addEventListener('submit', (e) => {
                if (n() < 1) { e.preventDefault(); alert('Pilih minimal 1 member.'); return; }
                if (!confirm('Kirim pesan ini ke ' + n() + ' member?')) { e.preventDefault(); return; }
                const b = document.getElementById('bcSend'); b.disabled = true; b.textContent = 'Mengirim...';
            });
        })();
    </script>
@endpush
