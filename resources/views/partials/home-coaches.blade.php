{{-- Bagian "Kenali Coach Kami" di halaman Home. Data dari menu admin Kelola Coach. --}}
@php
    $hcCoaches = class_exists(\App\Models\Coach::class) && \Illuminate\Support\Facades\Schema::hasTable('coaches')
        ? \App\Models\Coach::active()->ordered()->get()
        : collect();
@endphp

@if ($hcCoaches->isNotEmpty())
<style>
    /* Home bisa di-scroll ke bawah untuk melihat bagian coach */
    html, body { height: auto !important; overflow-x: hidden !important; overflow-y: auto !important; }
    .hc { position: relative; overflow: hidden; padding: clamp(48px, 7vw, 88px) 5% clamp(56px, 7vw, 96px); background: linear-gradient(180deg, #071b13 0%, #04100b 100%); color: #f2f7f3; font-family: Arial, Helvetica, sans-serif; }
    .hc-inner { max-width: 1250px; margin: 0 auto; }
    .hc-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 16px 32px; margin-bottom: 30px; }
    .hc-eyebrow { display: inline-flex; align-items: center; gap: 10px; color: #8cf238; font-size: 12px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; }
    .hc-eyebrow::before { content: ""; width: 28px; height: 2px; background: #8cf238; border-radius: 2px; }
    .hc h2 { margin-top: 10px; font-size: clamp(34px, 4.6vw, 54px); font-weight: 900; line-height: .98; letter-spacing: -1.5px; text-transform: uppercase; }
    .hc h2 span { color: #8cf238; }
    .hc-head p { max-width: 52ch; color: rgba(242, 247, 243, .62); font-size: 14.5px; line-height: 1.65; }
    .hc-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; perspective: 1200px; }
    .hc-card { position: relative; display: flex; flex-direction: column; min-width: 0; border: 1px solid rgba(156, 255, 56, .16); border-radius: 22px; background: #0b2117; overflow: hidden; transform-style: preserve-3d; transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
    .hc-card:hover { border-color: rgba(156, 255, 56, .45); box-shadow: 0 24px 50px rgba(0, 0, 0, .45); }
    .hc-photo { position: relative; aspect-ratio: 4 / 4.3; overflow: hidden; background: #0f2e1d center top / cover no-repeat; }
    .hc-photo canvas { width: 100%; height: 100%; display: block; }
    .hc-photo::after { content: ""; position: absolute; inset: 55% 0 0; background: linear-gradient(to bottom, rgba(11, 33, 23, 0), #0b2117); }
    .hc-badge { position: absolute; z-index: 1; top: 12px; left: 12px; padding: 5px 10px; border-radius: 99px; background: rgba(4, 16, 11, .78); border: 1px solid rgba(156, 255, 56, .25); color: #9cff38; font-size: 11px; font-weight: 700; }
    .hc-body { display: flex; flex-direction: column; gap: 10px; padding: 4px 16px 18px; margin-top: -26px; position: relative; z-index: 1; }
    .hc-name { line-height: 1.15; font-size: 22px; font-weight: 900; letter-spacing: -.4px; text-transform: uppercase; }
    .hc-role { margin-top: 3px; line-height: 1.35; color: rgba(242, 247, 243, .6); font-size: 12.5px; }
    .hc-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .hc-stats div { padding: 8px 10px; border-radius: 12px; background: rgba(255, 255, 255, .04); }
    .hc-stats strong { display: block; font-size: 16px; }
    .hc-stats span { color: rgba(242, 247, 243, .55); font-size: 11px; }
    .hc-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .hc-chips span { padding: 4px 9px; border-radius: 99px; background: rgba(156, 255, 56, .12); color: #9cff38; font-size: 11.5px; font-weight: 700; }
    .hc-quote { padding-left: 10px; border-left: 2px solid #9cff38; color: rgba(242, 247, 243, .8); font-size: 13px; font-style: italic; line-height: 1.5; }
    .hc-note { margin-top: 22px; color: rgba(242, 247, 243, .5); font-size: 12.5px; }
    @media (max-width: 1080px) { .hc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) {
        .hc { padding-left: 16px; padding-right: 16px; }
        .hc-grid { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; scroll-padding-inline: 16px; gap: 14px; margin: 0 -16px; padding: 4px 16px 12px; scrollbar-width: none; }
        .hc-grid::-webkit-scrollbar { display: none; }
        .hc-card { flex: 0 0 78%; scroll-snap-align: start; }
    }
    @media (prefers-reduced-motion: reduce) { .hc-card { transition: none; } }
</style>

<section class="hc" id="coach" aria-labelledby="hcTitle">
    <div class="hc-inner">
        <div class="hc-head">
            <div>
                <span class="hc-eyebrow">About coach</span>
                <h2 id="hcTitle">Kenali <span>coach</span> kami</h2>
            </div>
            <p>Setiap lesson dipegang coach berpengalaman. Jadwal coach diatur langsung oleh admin, jadi Anda cukup pilih tanggal, jenis lesson, dan lapangan.</p>
        </div>

        <div class="hc-grid">
            @foreach ($hcCoaches as $i => $coach)
                <article class="hc-card">
                    <div class="hc-photo" @if ($coach->photo_url) style="background-image:url('{{ $coach->photo_url }}')" @endif>
                        @unless ($coach->photo_url)<canvas width="400" height="430" data-hc-art="{{ $i }}" aria-hidden="true"></canvas>@endunless
                        @if ($coach->badge)<span class="hc-badge">{{ $coach->badge }}</span>@endif
                    </div>
                    <div class="hc-body">
                        <div>
                            <div class="hc-name">{{ $coach->name }}</div>
                            @if ($coach->role)<div class="hc-role">{{ $coach->role }}</div>@endif
                        </div>
                        @if ($coach->years_experience || $coach->students)
                            <div class="hc-stats">
                                <div><strong>{{ $coach->years_experience ? $coach->years_experience . ' thn' : '-' }}</strong><span>Pengalaman</span></div>
                                <div><strong>{{ $coach->students ?: '-' }}</strong><span>Murid dilatih</span></div>
                            </div>
                        @endif
                        @if (! empty($coach->skills))
                            <div class="hc-chips">@foreach ($coach->skills as $skill)<span>{{ $skill }}</span>@endforeach</div>
                        @endif
                        @if ($coach->quote)<p class="hc-quote">{{ $coach->quote }}</p>@endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<script>
(function () {
    'use strict';
    var hues = [142, 96, 170, 120, 150, 108];
    document.querySelectorAll('canvas[data-hc-art]').forEach(function (cv) {
        var i = +cv.dataset.hcArt, hue = hues[i % hues.length], x = cv.getContext('2d'), w = cv.width, h = cv.height;
        var sky = x.createLinearGradient(0, 0, 0, h);
        sky.addColorStop(0, 'hsl(' + hue + ',40%,22%)'); sky.addColorStop(.6, 'hsl(' + hue + ',45%,13%)'); sky.addColorStop(1, '#0b2117');
        x.fillStyle = sky; x.fillRect(0, 0, w, h);
        var g = x.createRadialGradient(w * .72, h * .3, 4, w * .72, h * .3, w * .55);
        g.addColorStop(0, 'rgba(156,255,56,.35)'); g.addColorStop(1, 'rgba(156,255,56,0)'); x.fillStyle = g; x.fillRect(0, 0, w, h);
        x.fillStyle = 'hsl(' + hue + ',38%,16%)'; x.beginPath(); x.moveTo(0, h * .66); x.bezierCurveTo(w * .3, h * .56, w * .6, h * .7, w, h * .6); x.lineTo(w, h); x.lineTo(0, h); x.fill();
        x.save(); x.translate(w * .46, h * .8); x.fillStyle = '#020905'; x.strokeStyle = '#020905'; x.lineCap = 'round'; x.rotate([-.12, .05, -.04, .1][i % 4]);
        x.beginPath(); x.arc(0, -h * .5, w * .07, 0, Math.PI * 2); x.fill();
        x.lineWidth = w * .11; x.beginPath(); x.moveTo(0, -h * .42); x.lineTo(w * .02, -h * .2); x.stroke();
        x.lineWidth = w * .06; x.beginPath(); x.moveTo(w * .02, -h * .2); x.lineTo(-w * .06, 0); x.moveTo(w * .02, -h * .2); x.lineTo(w * .1, 0); x.stroke();
        x.lineWidth = w * .045; x.beginPath(); x.moveTo(0, -h * .38); x.lineTo(w * .14, -h * .5); x.stroke();
        x.lineWidth = w * .012; x.strokeStyle = '#c8d4cc'; x.beginPath(); x.moveTo(w * .14, -h * .5); x.lineTo(w * .32, -h * .66); x.stroke();
        x.restore();
    });
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    document.querySelectorAll('.hc-card').forEach(function (el) {
        el.addEventListener('pointermove', function (e) {
            if (e.pointerType === 'touch') return;
            var r = el.getBoundingClientRect(), px = (e.clientX - r.left) / r.width - .5, py = (e.clientY - r.top) / r.height - .5;
            el.style.transform = 'rotateY(' + (px * 14).toFixed(2) + 'deg) rotateX(' + (-py * 12).toFixed(2) + 'deg) translateZ(6px)';
        });
        el.addEventListener('pointerleave', function () { el.style.transform = ''; });
    });
})();
</script>
@endif
