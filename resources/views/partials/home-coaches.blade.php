{{-- Bagian "About Coach" di halaman Home: profil 1 coach. Data dari menu admin About Coach. --}}
@php
    $hcCoach = class_exists(\App\Models\Coach::class) && \Illuminate\Support\Facades\Schema::hasTable('coaches')
        ? \App\Models\Coach::active()->ordered()->first()
        : null;
@endphp

@if ($hcCoach)
@php
    $hcExp   = collect($hcCoach->experiences ?? [])->filter(fn ($e) => ! empty($e['text']));
    $hcCert  = collect($hcCoach->certifications ?? []);
    $hcAch   = collect($hcCoach->achievements ?? []);
    $hcSkill = collect($hcCoach->skills ?? []);
    $hcStats = array_filter([
        $hcCoach->years_experience ? [$hcCoach->years_experience . '+', 'Tahun melatih'] : null,
        $hcCoach->students ? [$hcCoach->students, 'Murid dilatih'] : null,
        $hcCert->isNotEmpty() ? [$hcCert->count(), 'Sertifikasi'] : null,
    ]);
@endphp
<style>
    /* Home bisa di-scroll ke bawah untuk melihat bagian coach */
    html, body { height: auto !important; overflow-x: hidden !important; overflow-y: auto !important; }
    .hc { position: relative; overflow: hidden; padding: clamp(52px, 7vw, 96px) 5%; background: radial-gradient(900px 500px at 15% 20%, rgba(156, 255, 56, .08), transparent 60%), linear-gradient(180deg, #071b13 0%, #04100b 100%); color: #f2f7f3; font-family: Arial, Helvetica, sans-serif; }
    .hc *, .hc *::before, .hc *::after { box-sizing: border-box; }
    .hc-inner { max-width: 1180px; margin: 0 auto; display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); column-gap: clamp(28px, 5vw, 64px); row-gap: 0; align-items: start; grid-template-areas: "photo head" "photo body"; grid-template-rows: auto 1fr; }
    .hc-head { grid-area: head; }
    .hc-body { grid-area: body; }

    .hc-photo-wrap { grid-area: photo; position: sticky; top: 90px; perspective: 1200px; }
    .hc-photo { position: relative; aspect-ratio: 4 / 5; border-radius: 26px; overflow: hidden; border: 1px solid rgba(156, 255, 56, .2); background: #0f2e1d center top / cover no-repeat; box-shadow: 0 30px 70px rgba(0, 0, 0, .5); transform-style: preserve-3d; transition: transform .25s ease; }
    .hc-photo canvas { width: 100%; height: 100%; display: block; }
    .hc-photo::after { content: ""; position: absolute; inset: 50% 0 0; background: linear-gradient(to bottom, rgba(4, 16, 11, 0), rgba(4, 16, 11, .92)); }
    .hc-badge { position: absolute; z-index: 1; top: 16px; left: 16px; padding: 6px 12px; border-radius: 99px; background: rgba(4, 16, 11, .8); border: 1px solid rgba(156, 255, 56, .3); color: #9cff38; font-size: 12px; font-weight: 700; }
    .hc-photo-name { position: absolute; z-index: 1; left: 20px; right: 20px; bottom: 18px; }
    .hc-photo-name strong { display: block; font-size: clamp(24px, 2.6vw, 30px); font-weight: 900; line-height: 1.1; text-transform: uppercase; letter-spacing: -.5px; }
    .hc-photo-name span { display: block; margin-top: 4px; color: rgba(242, 247, 243, .7); font-size: 13px; }
    .hc-float { position: absolute; z-index: 2; right: -16px; top: 20%; padding: 12px 16px; border-radius: 16px; background: #9cff38; color: #07120c; box-shadow: 0 16px 34px rgba(156, 255, 56, .3); text-align: center; }
    .hc-float strong { display: block; font-size: 26px; font-weight: 900; line-height: 1; }
    .hc-float span { font-size: 11px; font-weight: 700; }

    .hc-eyebrow { display: inline-flex; align-items: center; gap: 10px; color: #8cf238; font-size: 12px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; }
    .hc-eyebrow::before { content: ""; width: 28px; height: 2px; background: #8cf238; border-radius: 2px; }
    .hc h2 { margin: 10px 0 6px; font-size: clamp(32px, 4.4vw, 52px); font-weight: 900; line-height: 1; letter-spacing: -1.5px; text-transform: uppercase; }
    .hc h2 span { color: #8cf238; }
    .hc-role { margin-bottom: 16px; color: rgba(242, 247, 243, .7); font-size: 15px; font-weight: 700; }
    .hc-bio { margin-top: 0; color: rgba(242, 247, 243, .72); font-size: 15px; line-height: 1.75; white-space: pre-line; }

    .hc-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-top: 22px; }
    .hc-stats div { padding: 14px 16px; border-radius: 16px; border: 1px solid rgba(156, 255, 56, .14); background: rgba(255, 255, 255, .03); }
    .hc-stats strong { display: block; color: #9cff38; font-size: 28px; font-weight: 900; line-height: 1.1; }
    .hc-stats span { color: rgba(242, 247, 243, .6); font-size: 12px; }

    .hc-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 16px; }
    .hc-box { padding: 18px; border-radius: 18px; border: 1px solid rgba(156, 255, 56, .12); background: rgba(8, 26, 18, .7); }
    .hc-box.wide { grid-column: 1 / -1; }
    .hc-box h3 { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 13px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; color: #dfe9e2; }
    .hc-box h3 i { font-style: normal; width: 26px; height: 26px; display: grid; place-items: center; border-radius: 8px; background: rgba(156, 255, 56, .14); color: #9cff38; font-size: 13px; }
    .hc-time { position: relative; display: grid; gap: 14px; padding-left: 18px; }
    .hc-time::before { content: ""; position: absolute; left: 4px; top: 6px; bottom: 6px; width: 2px; background: linear-gradient(#9cff38, rgba(156, 255, 56, .1)); }
    .hc-time div { position: relative; }
    .hc-time div::before { content: ""; position: absolute; left: -18px; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: #04100b; border: 2px solid #9cff38; }
    .hc-time small { display: block; color: #9cff38; font-size: 11.5px; font-weight: 700; }
    .hc-time span { font-size: 14px; color: rgba(242, 247, 243, .85); }
    .hc-list { display: grid; gap: 9px; list-style: none; margin: 0; padding: 0; }
    .hc-list li { display: flex; gap: 9px; font-size: 13.5px; line-height: 1.45; color: rgba(242, 247, 243, .82); }
    .hc-list li::before { content: "✓"; flex: 0 0 18px; height: 18px; display: grid; place-items: center; border-radius: 50%; background: rgba(156, 255, 56, .15); color: #9cff38; font-size: 10px; font-weight: 900; margin-top: 1px; }
    .hc-list.trophy li::before { content: "★"; }
    .hc-chips { display: flex; flex-wrap: wrap; gap: 7px; }
    .hc-chips span { padding: 6px 12px; border-radius: 99px; background: rgba(156, 255, 56, .12); color: #9cff38; font-size: 12.5px; font-weight: 700; }
    .hc-quote { margin-top: 16px; padding: 16px 18px; border-left: 3px solid #9cff38; border-radius: 0 14px 14px 0; background: rgba(156, 255, 56, .06); color: rgba(242, 247, 243, .88); font-size: 15px; font-style: italic; line-height: 1.55; }
    .hc-cta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .hc-cta a { display: inline-flex; align-items: center; justify-content: center; height: 46px; padding: 0 22px; border-radius: 10px; font-size: 13px; font-weight: 800; letter-spacing: .5px; text-decoration: none; text-transform: uppercase; }
    .hc-cta .pri { background: #8cf238; color: #07120c; }
    .hc-cta .sec { border: 1px solid rgba(255, 255, 255, .25); color: #f2f7f3; }

    @media (max-width: 900px) {
        .hc-inner { grid-template-columns: 1fr; grid-template-areas: "head" "photo" "body"; grid-template-rows: auto; }
        .hc-head { margin-bottom: 6px; }
        .hc-body { margin-top: 22px; }
        .hc-photo-wrap { position: relative; top: 0; max-width: 420px; width: 100%; margin: 0 auto; }
        .hc-float { right: -6px; }
    }
    @media (max-width: 600px) {
        .hc { padding: 48px 16px 120px; }
        .hc-cols { grid-template-columns: 1fr; }
        .hc-bio { font-size: 14.5px; }
        .hc-stats { grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .hc-stats div { padding: 12px 10px; text-align: center; }
        .hc-stats strong { font-size: 22px; }
        .hc-cta a { flex: 1; }
    }
    @media (prefers-reduced-motion: reduce) { .hc-photo { transition: none; } }
</style>

<section class="hc" id="coach" aria-labelledby="hcTitle">
    <div class="hc-inner">
        <div class="hc-photo-wrap">
            <div class="hc-photo" id="hcPhoto" @if ($hcCoach->photo_url) style="background-image:url('{{ $hcCoach->photo_url }}')" @endif>
                @unless ($hcCoach->photo_url)<canvas width="480" height="600" id="hcArt" aria-hidden="true"></canvas>@endunless
                @if ($hcCoach->badge)<span class="hc-badge">{{ $hcCoach->badge }}</span>@endif
                <div class="hc-photo-name">
                    <strong>{{ $hcCoach->name }}</strong>
                    @if ($hcCoach->role)<span>{{ $hcCoach->role }}</span>@endif
                </div>
            </div>
            @if ($hcCoach->years_experience)
                <div class="hc-float"><strong>{{ $hcCoach->years_experience }}+</strong><span>tahun<br>melatih</span></div>
            @endif
        </div>

        <div class="hc-head">
            <span class="hc-eyebrow">About coach</span>
            <h2 id="hcTitle">Kenali <span>coach</span> Anda</h2>
            <div class="hc-role">{{ $hcCoach->name }}@if ($hcCoach->role) · {{ $hcCoach->role }}@endif</div>
        </div>

        <div class="hc-body">
            @if ($hcCoach->bio)<p class="hc-bio">{{ $hcCoach->bio }}</p>@endif

            @if ($hcStats)
                <div class="hc-stats">
                    @foreach ($hcStats as [$val, $label])<div><strong>{{ $val }}</strong><span>{{ $label }}</span></div>@endforeach
                </div>
            @endif

            <div class="hc-cols">
                @if ($hcExp->isNotEmpty())
                    <div class="hc-box @if ($hcCert->isEmpty() && $hcAch->isEmpty()) wide @endif">
                        <h3><i>⏱</i>Pengalaman</h3>
                        <div class="hc-time">
                            @foreach ($hcExp as $e)<div>@if (! empty($e['period']))<small>{{ $e['period'] }}</small>@endif<span>{{ $e['text'] }}</span></div>@endforeach
                        </div>
                    </div>
                @endif
                @if ($hcCert->isNotEmpty() || $hcAch->isNotEmpty())
                    <div class="hc-box @if ($hcExp->isEmpty()) wide @endif">
                        @if ($hcCert->isNotEmpty())
                            <h3><i>✓</i>Sertifikasi</h3>
                            <ul class="hc-list">@foreach ($hcCert as $c)<li>{{ $c }}</li>@endforeach</ul>
                        @endif
                        @if ($hcAch->isNotEmpty())
                            <h3 @if ($hcCert->isNotEmpty()) style="margin-top: 18px" @endif><i>★</i>Prestasi</h3>
                            <ul class="hc-list trophy">@foreach ($hcAch as $a)<li>{{ $a }}</li>@endforeach</ul>
                        @endif
                    </div>
                @endif
                @if ($hcSkill->isNotEmpty())
                    <div class="hc-box wide">
                        <h3><i>⛳</i>Keahlian yang diajarkan</h3>
                        <div class="hc-chips">@foreach ($hcSkill as $s)<span>{{ $s }}</span>@endforeach</div>
                    </div>
                @endif
            </div>

            @if ($hcCoach->quote)<blockquote class="hc-quote">“{{ $hcCoach->quote }}”</blockquote>@endif

            <div class="hc-cta">
                @if (Route::has('booking'))<a class="pri" href="{{ route('booking') }}">Book lesson</a>@endif
                @if (Route::has('program'))<a class="sec" href="{{ route('program') }}">Lihat program</a>@endif
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    'use strict';
    var cv = document.getElementById('hcArt');
    if (cv) {
        var x = cv.getContext('2d'), w = cv.width, h = cv.height;
        var sky = x.createLinearGradient(0, 0, 0, h);
        sky.addColorStop(0, '#1f4a2c'); sky.addColorStop(.6, '#0f2c1a'); sky.addColorStop(1, '#04100b');
        x.fillStyle = sky; x.fillRect(0, 0, w, h);
        var g = x.createRadialGradient(w * .72, h * .28, 4, w * .72, h * .28, w * .6);
        g.addColorStop(0, 'rgba(156,255,56,.35)'); g.addColorStop(1, 'rgba(156,255,56,0)'); x.fillStyle = g; x.fillRect(0, 0, w, h);
        x.fillStyle = '#123620'; x.beginPath(); x.moveTo(0, h * .7); x.bezierCurveTo(w * .3, h * .6, w * .6, h * .74, w, h * .64); x.lineTo(w, h); x.lineTo(0, h); x.fill();
        x.save(); x.translate(w * .46, h * .82); x.fillStyle = '#020905'; x.strokeStyle = '#020905'; x.lineCap = 'round'; x.rotate(-.08);
        x.beginPath(); x.arc(0, -h * .5, w * .07, 0, Math.PI * 2); x.fill();
        x.lineWidth = w * .11; x.beginPath(); x.moveTo(0, -h * .42); x.lineTo(w * .02, -h * .2); x.stroke();
        x.lineWidth = w * .06; x.beginPath(); x.moveTo(w * .02, -h * .2); x.lineTo(-w * .06, 0); x.moveTo(w * .02, -h * .2); x.lineTo(w * .1, 0); x.stroke();
        x.lineWidth = w * .045; x.beginPath(); x.moveTo(0, -h * .38); x.lineTo(w * .14, -h * .5); x.stroke();
        x.lineWidth = w * .012; x.strokeStyle = '#c8d4cc'; x.beginPath(); x.moveTo(w * .14, -h * .5); x.lineTo(w * .32, -h * .66); x.stroke();
        x.restore();
    }
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var el = document.getElementById('hcPhoto');
    if (!el) return;
    el.addEventListener('pointermove', function (e) {
        if (e.pointerType === 'touch') return;
        var r = el.getBoundingClientRect(), px = (e.clientX - r.left) / r.width - .5, py = (e.clientY - r.top) / r.height - .5;
        el.style.transform = 'rotateY(' + (px * 10).toFixed(2) + 'deg) rotateX(' + (-py * 8).toFixed(2) + 'deg)';
    });
    el.addEventListener('pointerleave', function () { el.style.transform = ''; });
})();
</script>
@endif
