{{-- Tombol & panel edit About Coach langsung dari halaman Home (hanya admin/coach). Variabel: $coach --}}
@php
    $hceOpen = $errors->any() && old('return') === 'home';
@endphp
<style>
    .hce-btn { position: absolute; z-index: 20; top: 18px; right: 5%; display: inline-flex; align-items: center; gap: 8px; height: 42px; padding: 0 16px; border: 1px solid rgba(156, 255, 56, .55); border-radius: 12px; background: rgba(4, 16, 11, .85); color: #9cff38; font: 800 13px Arial, Helvetica, sans-serif; cursor: pointer; box-shadow: 0 10px 26px rgba(0, 0, 0, .4); }
    .hce-btn:hover { background: #9cff38; color: #07120c; }
    .hce-off { position: absolute; z-index: 20; top: 18px; left: 5%; padding: 9px 14px; border-radius: 12px; background: rgba(255, 196, 0, .14); border: 1px solid rgba(255, 196, 0, .4); color: #ffd25a; font: 700 12.5px Arial, Helvetica, sans-serif; }
    .hce-toast { position: fixed; z-index: 400; left: 50%; top: 84px; transform: translateX(-50%); padding: 12px 18px; border-radius: 14px; background: #9cff38; color: #07120c; font: 800 14px Arial, Helvetica, sans-serif; box-shadow: 0 16px 40px rgba(0, 0, 0, .45); animation: hceToast 4s ease forwards; }
    @keyframes hceToast { 0%, 85% { opacity: 1; } 100% { opacity: 0; visibility: hidden; } }
    .hce-back { position: fixed; inset: 0; z-index: 300; background: rgba(0, 0, 0, .55); opacity: 0; visibility: hidden; transition: opacity .2s; }
    .hce-panel { position: fixed; z-index: 310; top: 0; right: 0; bottom: 0; width: min(560px, 100%); display: flex; flex-direction: column; background: #071711; border-left: 1px solid rgba(156, 255, 56, .2); box-shadow: -20px 0 60px rgba(0, 0, 0, .5); transform: translateX(100%); transition: transform .25s ease; color: #f2f7f3; font-family: Arial, Helvetica, sans-serif; }
    .hce.open .hce-back { opacity: 1; visibility: visible; }
    .hce.open .hce-panel { transform: none; }
    .hce-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 16px 20px; border-bottom: 1px solid rgba(255, 255, 255, .08); }
    .hce-top strong { font-size: 17px; }
    .hce-top small { display: block; color: rgba(242, 247, 243, .55); font-size: 12px; font-weight: 400; margin-top: 2px; }
    .hce-x { width: 38px; height: 38px; border: 1px solid rgba(255, 255, 255, .14); border-radius: 11px; background: transparent; color: #f2f7f3; font-size: 18px; cursor: pointer; }
    .hce-form { flex: 1; min-height: 0; display: flex; flex-direction: column; }
    .hce-scroll { flex: 1; overflow-y: auto; padding: 18px 20px 24px; overscroll-behavior: contain; }
    .hce-err { margin-bottom: 14px; padding: 10px 12px; border-radius: 12px; background: rgba(255, 90, 90, .1); border: 1px solid rgba(255, 90, 90, .35); color: #ffb3b3; font-size: 13px; }
    .hce-err ul { margin: 0; padding-left: 18px; }
    .hce-foot { display: flex; gap: 10px; padding: 14px 20px calc(14px + env(safe-area-inset-bottom)); border-top: 1px solid rgba(255, 255, 255, .08); background: #06130e; }
    .hce-foot a, .hce-foot button { flex: 1; display: inline-flex; align-items: center; justify-content: center; height: 46px; border-radius: 12px; font: 800 14px Arial, Helvetica, sans-serif; text-decoration: none; cursor: pointer; }
    .hce-foot a { border: 1px solid rgba(255, 255, 255, .18); color: #f2f7f3; background: transparent; }
    .hce-foot button { border: 0; background: #9cff38; color: #07120c; }
    .hce-foot button:disabled { opacity: .6; cursor: wait; }
    body.hce-lock { overflow: hidden !important; }
    @media (max-width: 820px) {
        .hce-btn { top: 12px; right: 16px; height: 38px; font-size: 12px; }
        .hce-off { top: 58px; left: 16px; right: 16px; }
        .hce-panel { width: 100%; border-left: 0; z-index: 310; }
        .hce-foot { padding-bottom: calc(14px + env(safe-area-inset-bottom)); }
    }
</style>

@if (session('coach_saved'))
    <div class="hce-toast" role="status">✓ {{ session('coach_saved') }}</div>
@endif

<button type="button" class="hce-btn" data-hce-open>✎ Edit About Coach</button>
@if ($coach->exists && ! $coach->is_active)
    <div class="hce-off">Bagian ini sedang disembunyikan dari pengunjung. Centang "Tampilkan" untuk menampilkan.</div>
@elseif (! $coach->exists)
    <div class="hce-off">Belum ada profil coach. Klik "Edit About Coach" untuk mengisi.</div>
@endif

<div class="hce {{ $hceOpen ? 'open' : '' }}" id="hce">
    <div class="hce-back" data-hce-close></div>
    <aside class="hce-panel" role="dialog" aria-modal="true" aria-labelledby="hceTitle">
        <div class="hce-top">
            <div>
                <strong id="hceTitle">Edit About Coach</strong>
                <small>Perubahan langsung tampil di halaman Home.</small>
            </div>
            <button type="button" class="hce-x" data-hce-close aria-label="Tutup">✕</button>
        </div>

        <form class="hce-form" method="POST" enctype="multipart/form-data" id="hceForm"
              action="{{ $coach->exists ? route('admin.coaches.update', $coach) : route('admin.coaches.store') }}">
            @csrf
            @if ($coach->exists) @method('PUT') @endif
            <input type="hidden" name="return" value="home">

            <div class="hce-scroll">
                @if ($hceOpen)
                    <div class="hce-err"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @include('admin.coaches._fields', ['coach' => $coach])
            </div>

            <div class="hce-foot">
                <a href="{{ route('admin.coaches.index') }}">Buka di admin</a>
                <button type="submit" id="hceSave">Simpan</button>
            </div>
        </form>
    </aside>
</div>

<script>
(function () {
    var box = document.getElementById('hce');
    if (!box) return;
    function set(open) { box.classList.toggle('open', open); document.body.classList.toggle('hce-lock', open); }
    if (box.classList.contains('open')) document.body.classList.add('hce-lock');
    document.querySelectorAll('[data-hce-open]').forEach(function (b) { b.addEventListener('click', function () { set(true); }); });
    box.querySelectorAll('[data-hce-close]').forEach(function (b) { b.addEventListener('click', function () { set(false); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
    document.getElementById('hceForm').addEventListener('submit', function () {
        var b = document.getElementById('hceSave'); b.disabled = true; b.textContent = 'Menyimpan...';
    });
})();
</script>
