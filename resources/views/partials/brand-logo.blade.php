{{--
    Logo + nama usaha dari menu Settings, ikon tampil sebagai koin 3D yang berputar.
    Depan: logo upload (atau ⛳), belakang: bola golf.
    Variabel opsional: $iconClass, $textClass, $accentClass
--}}
@php
    [$brandFirst, $brandMiddle, $brandLast] = \App\Support\Brand::nameParts();
    $brandLogo = \App\Support\Brand::logoUrl();
@endphp

@once
<style>
    .bl3d { display: inline-grid; place-items: center; width: 1.3em; height: 1.3em; line-height: 1; perspective: 260px; vertical-align: middle; flex: 0 0 auto; }
    .bl3d-coin { position: relative; width: 100%; height: 100%; transform-style: preserve-3d; animation: bl3dSpin 7s cubic-bezier(.65, 0, .35, 1) infinite; }
    a:hover > .bl3d .bl3d-coin, .bl3d:hover .bl3d-coin { animation-duration: 1.6s; }
    .bl3d-face, .bl3d-edge { position: absolute; inset: 0; border-radius: 50%; backface-visibility: hidden; }
    .bl3d-face { display: grid; place-items: center; overflow: hidden; }
    .bl3d-front { transform: translateZ(2.5px); background: radial-gradient(circle at 32% 28%, #2f6b31, #0d2a17 62%, #06160d); box-shadow: inset 0 0 0 2px #9cff38, inset 0 -4px 8px rgba(0, 0, 0, .45); font-size: .72em; }
    .bl3d-front img { width: 78%; height: 78%; object-fit: contain; }
    .bl3d-back { transform: rotateY(180deg) translateZ(2.5px); background:
        radial-gradient(circle at 35% 30%, rgba(255,255,255,.95) 0 8%, transparent 30%),
        radial-gradient(circle, rgba(0,0,0,.16) 0 1.2px, transparent 1.6px) 0 0 / 5px 5px,
        radial-gradient(circle at 40% 35%, #ffffff, #dfe6e1 55%, #9fb0a6);
        box-shadow: inset 0 0 0 2px #9cff38, inset -3px -4px 8px rgba(0, 0, 0, .25); }
    .bl3d-edge { background: #5fae1c; }
    .bl3d-edge:nth-child(1) { transform: translateZ(1.5px); }
    .bl3d-edge:nth-child(2) { transform: translateZ(.5px); background: #4f9617; }
    .bl3d-edge:nth-child(3) { transform: translateZ(-.5px); background: #4f9617; }
    .bl3d-edge:nth-child(4) { transform: translateZ(-1.5px); }
    .bl3d-edge { backface-visibility: visible; }
    @keyframes bl3dSpin {
        0%, 30%   { transform: rotateY(-18deg) rotateX(8deg); }
        50%, 78%  { transform: rotateY(180deg) rotateX(8deg); }
        100%      { transform: rotateY(342deg) rotateX(8deg); }
    }
    @media (prefers-reduced-motion: reduce) { .bl3d-coin { animation: none; transform: rotateY(-18deg); } }
</style>
@endonce

<span class="bl3d {{ $iconClass ?? '' }}" aria-hidden="true">
    <span class="bl3d-coin">
        <span class="bl3d-edge"></span><span class="bl3d-edge"></span><span class="bl3d-edge"></span><span class="bl3d-edge"></span>
        <span class="bl3d-face bl3d-front">@if ($brandLogo)<img src="{{ $brandLogo }}" alt="">@else⛳@endif</span>
        <span class="bl3d-face bl3d-back"></span>
    </span>
</span>
<span class="{{ $textClass ?? '' }}">{{ $brandFirst }}@if ($brandMiddle) <span class="{{ $accentClass ?? '' }}">{{ $brandMiddle }}</span>@endif{{ $brandLast ? ' ' . $brandLast : '' }}</span>
