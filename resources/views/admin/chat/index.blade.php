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
        .ac-bc input, .ac-bc textarea { width: 100%; padding: 11px 12px; border: 1px solid var(--line); border-radius: 11px; background: rgba(0, 0, 0, .2); color: var(--text); font: 14px/1.5 Arial, Helvetica, sans-serif; }
        .ac-bc textarea { min-height: 110px; resize: vertical; }
        .ac-bc .row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; color: var(--text-muted); font-size: 12.5px; }
        .ac-history { margin-top: 6px; display: grid; gap: 6px; padding: 0 16px 16px; }
        .ac-history div { padding: 9px 12px; border-radius: 11px; background: rgba(255, 255, 255, .03); font-size: 12.5px; color: var(--text-soft); }
        .ac-history b { color: var(--text); }
        .ac-back { display: none; }
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
            <p>Balas pertanyaan member dan kirim pengumuman penting ke semua member. Pesan masuk juga muncul di lonceng notifikasi.</p>
        </div>
    </section>

    <details class="ac-bc" @if ($errors->has('title') || $errors->has('body')) open @endif>
        <summary>📢 Kirim pengumuman ke semua member <span>{{ $memberCount }} member akan menerima</span></summary>
        <form method="POST" action="{{ route('admin.chat.broadcast') }}" id="bcForm">
            @csrf
            @if ($errors->has('title') || $errors->has('body'))
                <div class="error-box"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif
            <input type="text" name="title" maxlength="120" required placeholder="Judul, contoh: Driving range Suvarna tutup Sabtu" value="{{ old('title') }}">
            <textarea name="body" maxlength="2000" required placeholder="Isi pengumuman untuk semua member...">{{ old('body') }}</textarea>
            <div class="row">
                <span>Masuk ke Chat dan Notifikasi setiap member.</span>
                <button type="submit" class="btn btn-primary" id="bcSend">Kirim ke semua member</button>
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
        document.getElementById('bcForm')?.addEventListener('submit', (e) => {
            if (!confirm('Kirim pengumuman ini ke {{ $memberCount }} member?')) { e.preventDefault(); return; }
            const b = document.getElementById('bcSend'); b.disabled = true; b.textContent = 'Mengirim...';
        });
    </script>
@endpush
