@extends('layouts.fw')

@section('title', 'Galeri')

@php
    use App\Models\Gallery;
    use App\Support\Icons;

    // Hanya media berstatus "active" yang tampil ke customer & visitor
    $items = Gallery::active()->ordered()->get();
    $photoCount = $items->where('type', '!=', 'video')->count();
    $videoCount = $items->where('type', 'video')->count();
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
@endphp

@push('head')
<style>
    .gl-tabs { margin-bottom: 18px; }
    .gl-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); grid-auto-flow: dense; gap: 12px; }
    .gl-item { position: relative; aspect-ratio: 1; padding: 0; border: 0; border-radius: 18px; overflow: hidden; background: #cfd8c9 center / cover no-repeat; cursor: pointer; box-shadow: var(--fw-shadow); }
    .gl-item.wide { grid-column: span 2; aspect-ratio: 2 / 1; }
    .gl-item img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
    .gl-item:hover img { transform: scale(1.04); }
    .gl-item .cap { position: absolute; left: 0; right: 0; bottom: 0; padding: 30px 12px 10px; background: linear-gradient(180deg, transparent, rgba(23, 46, 33, .8)); color: #fff; font-size: 13px; font-weight: 600; text-align: left; opacity: 0; transition: opacity .2s; }
    .gl-item:hover .cap, .gl-item:focus-visible .cap { opacity: 1; }
    .gl-item .play { position: absolute; inset: 0; display: grid; place-items: center; background: rgba(23, 46, 33, .15); }
    .gl-item .play span { width: 52px; height: 52px; display: grid; place-items: center; border-radius: 50%; background: rgba(255, 255, 255, .94); color: var(--fw-green); box-shadow: var(--fw-shadow-lg); }
    .gl-item .play svg { width: 22px; height: 22px; margin-left: 3px; }
    .gl-item .vtitle { position: absolute; left: 12px; bottom: 10px; right: 12px; color: #fff; font-size: 13px; font-weight: 600; text-align: left; text-shadow: 0 1px 6px rgba(0, 0, 0, .4); }
    .gl-ph { width: 100%; height: 100%; display: grid; place-items: center; background: linear-gradient(140deg, #1f4d33, #3d7d57); color: rgba(255, 255, 255, .8); }

    .lb { position: fixed; inset: 0; z-index: 330; display: none; align-items: center; justify-content: center; padding: 24px; background: rgba(15, 26, 19, .88); }
    .lb.open { display: flex; }
    .lb-box { width: min(980px, 100%); }
    .lb-media { border-radius: 20px; overflow: hidden; background: #000; }
    .lb-media img { width: 100%; max-height: 78vh; object-fit: contain; display: block; margin: 0 auto; }
    .lb-media .ratio { position: relative; aspect-ratio: 16 / 9; }
    .lb-media iframe, .lb-media video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
    .lb-cap { margin-top: 12px; color: #fff; }
    .lb-cap strong { display: block; font-size: 16px; }
    .lb-cap span { color: rgba(255, 255, 255, .7); font-size: 13.5px; }
    .lb-x { position: absolute; top: 16px; right: 16px; width: 44px; height: 44px; display: grid; place-items: center; border: 0; border-radius: 50%; background: rgba(255, 255, 255, .14); color: #fff; cursor: pointer; }
    .lb-x svg { width: 20px; height: 20px; }
    @media (max-width: 960px) { .gl-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 820px) {
        .gl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .gl-item { border-radius: 16px; }
        .gl-item.wide { grid-column: span 2; aspect-ratio: 2 / 1; }
        .lb { padding: 12px; }
    }
</style>
@endpush

@section('content')
    <div class="fw-pagehead">
        <div class="fw-pagehead-title">
            <a href="{{ $isCustomer ? route('dashboard') : route('home') }}" class="fw-back" aria-label="Kembali">{!! Icons::svg('back') !!}</a>
            <div>
                <h1 class="fw-h1">Galeri</h1>
                <p class="fw-sub">Aktivitas latihan, suasana lapangan, dan video rekomendasi.</p>
            </div>
        </div>
    </div>

    <div class="fw-pills gl-tabs" id="glTabs" role="tablist">
        <button type="button" class="fw-pill on" data-type="">Semua <small>{{ $items->count() }}</small></button>
        <button type="button" class="fw-pill" data-type="image">Foto <small>{{ $photoCount }}</small></button>
        <button type="button" class="fw-pill" data-type="video">Video <small>{{ $videoCount }}</small></button>
    </div>

    @if ($items->isEmpty())
        <div class="fw-empty"><b>Galeri masih kosong</b>Foto & video akan segera ditambahkan.</div>
    @else
        <div class="gl-grid" id="glGrid">
            @foreach ($items as $g)
                @php $isVideo = $g->type === 'video'; $thumb = $isVideo ? $g->thumbnail_url : $g->image_url; @endphp
                <button type="button" class="gl-item {{ $isVideo ? 'wide' : '' }}" data-type="{{ $isVideo ? 'video' : 'image' }}"
                        data-title="{{ $g->title }}" data-desc="{{ $g->category ?: \Illuminate\Support\Str::limit($g->description, 90) }}"
                        @if ($isVideo)
                            data-embed="{{ $g->embed_url }}" data-file="{{ $g->video_file_url }}" data-poster="{{ $g->image_url }}"
                        @else
                            data-src="{{ $g->image_url }}"
                        @endif
                        aria-label="{{ $g->title }}">
                    @if ($thumb)
                        <img src="{{ $thumb }}" alt="{{ $g->title }}" loading="lazy">
                    @else
                        <span class="gl-ph">{!! Icons::svg($isVideo ? 'video' : 'image') !!}</span>
                    @endif
                    @if ($isVideo)
                        <span class="play"><span>{!! Icons::svg('play') !!}</span></span>
                        <span class="vtitle">{{ $g->title }}</span>
                    @else
                        <span class="cap">{{ $g->title }}</span>
                    @endif
                </button>
            @endforeach
        </div>
        <div class="fw-empty" id="glEmpty" hidden><b>Belum ada media di kategori ini</b></div>
    @endif

    <div class="lb" id="lb" role="dialog" aria-modal="true">
        <button type="button" class="lb-x" data-lb-close aria-label="Tutup">{!! Icons::svg('x') !!}</button>
        <div class="lb-box">
            <div class="lb-media" id="lbMedia"></div>
            <div class="lb-cap"><strong id="lbTitle"></strong><span id="lbDesc"></span></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var grid = document.getElementById('glGrid');
    if (!grid) return;
    document.querySelectorAll('#glTabs .fw-pill').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#glTabs .fw-pill').forEach(function (x) { x.classList.remove('on'); });
            b.classList.add('on');
            var shown = 0;
            grid.querySelectorAll('.gl-item').forEach(function (it) {
                var ok = !b.dataset.type || it.dataset.type === b.dataset.type;
                it.hidden = !ok; if (ok) shown++;
            });
            document.getElementById('glEmpty').hidden = shown > 0;
        });
    });

    var lb = document.getElementById('lb'), media = document.getElementById('lbMedia');
    function close() { lb.classList.remove('open'); media.innerHTML = ''; document.body.style.overflow = ''; }
    grid.querySelectorAll('.gl-item').forEach(function (it) {
        it.addEventListener('click', function () {
            media.innerHTML = '';
            if (it.dataset.type === 'video') {
                var wrap = document.createElement('div'); wrap.className = 'ratio';
                if (it.dataset.embed) {
                    var f = document.createElement('iframe');
                    f.src = it.dataset.embed + (it.dataset.embed.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1';
                    f.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture'; f.allowFullscreen = true;
                    wrap.appendChild(f);
                } else if (it.dataset.file) {
                    var v = document.createElement('video'); v.controls = true; v.autoplay = true; v.src = it.dataset.file;
                    if (it.dataset.poster) v.poster = it.dataset.poster;
                    wrap.appendChild(v);
                }
                media.appendChild(wrap);
            } else {
                var img = document.createElement('img'); img.src = it.dataset.src; img.alt = it.dataset.title; media.appendChild(img);
            }
            document.getElementById('lbTitle').textContent = it.dataset.title || '';
            document.getElementById('lbDesc').textContent = it.dataset.desc || '';
            lb.classList.add('open'); document.body.style.overflow = 'hidden';
        });
    });
    lb.addEventListener('click', function (e) { if (e.target === lb || e.target.closest('[data-lb-close]')) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
@endpush
