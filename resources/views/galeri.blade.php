@php
    use App\Models\Gallery;

    // Hanya media berstatus "active" yang tampil ke customer & visitor
    $photos = Gallery::active()->images()->ordered()->get();
    $videos = Gallery::active()->videos()->ordered()->get();

    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
    $homeUrl    = $isCustomer ? route('dashboard') : route('home');
    $bookingUrl = $isCustomer ? route('booking') : route('login');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Galeri | {{ \App\Support\Brand::name() }}</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            width: 100%;
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
            background: #07130d;
            color: #ffffff;
        }

        body { overflow-x: hidden; }

        .page {
            min-height: 100vh;
            background:
                linear-gradient(rgba(3, 14, 8, 0.82), rgba(3, 14, 8, 0.94)),
                url('{{ \App\Support\Brand::background('public') }}') center center / cover fixed;
        }

        /* ================= NAVBAR ================= */
        .navbar {
            width: 100%;
            height: 68px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            padding: 0 42px;
            background: rgba(4, 15, 9, 0.94);
            border-bottom: 1px solid rgba(184, 255, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .brand {
            justify-self: start;
            text-decoration: none;
            color: #ffffff;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -0.5px;
            white-space: nowrap;
        }

        .brand span { color: #b8ff00; }

        .nav-menu {
            justify-self: center;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-menu > a {
            position: relative;
            color: rgba(255, 255, 255, 0.82);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.25s ease;
        }

        .nav-menu > a:hover,
        .nav-menu > a.active { color: #b8ff00; }

        .nav-menu > a.active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -10px;
            height: 2px;
            border-radius: 10px;
            background: #b8ff00;
        }

        .booking-btn {
            padding: 10px 18px;
            border: 1px solid #b8ff00;
            border-radius: 5px;
            color: #b8ff00 !important;
            font-weight: 800 !important;
            transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
        }

        .booking-btn:hover {
            background: #b8ff00;
            color: #07130d !important;
            transform: translateY(-1px);
        }

        .user-area {
            justify-self: end;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .user-name {
            color: #b8ff00;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            text-decoration: none;
        }

        .logout-form { margin: 0; }

        .logout-btn {
            border: 1px solid #b8ff00;
            border-radius: 22px;
            background: transparent;
            color: rgba(255, 255, 255, 0.82);
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
        }

        .logout-btn:hover {
            background: #b8ff00;
            color: #07130d;
            transform: translateY(-1px);
        }

        /* ================= CONTENT ================= */
        .content {
            width: min(1400px, 94%);
            margin: 0 auto;
            padding: 34px 0 50px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .eyebrow {
            color: #b8ff00;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 3px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .page-header h1 {
            font-size: clamp(30px, 3vw, 44px);
            font-weight: 900;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .page-header p {
            max-width: 650px;
            margin: 0 auto;
            color: rgba(255, 255, 255, 0.64);
            font-size: 13px;
            line-height: 1.6;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 15px;
        }

        .section-line {
            width: 34px;
            height: 3px;
            background: #b8ff00;
            border-radius: 10px;
        }

        .section-title h2 {
            font-size: 19px;
            font-weight: 800;
        }

        .section-title h2 span { color: #b8ff00; }

        .empty-box {
            margin-bottom: 34px;
            padding: 36px 20px;
            text-align: center;
            border: 1px dashed rgba(184, 255, 0, 0.25);
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.55);
            font-size: 13px;
        }

        /* ================= FOTO ================= */
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 34px;
        }

        .photo-card {
            height: 190px;
            position: relative;
            overflow: hidden;
            padding: 0;
            border-radius: 9px;
            border: 1px solid rgba(255, 255, 255, 0.10);
            background: rgba(255, 255, 255, 0.04);
            color: inherit;
            text-align: left;
            cursor: zoom-in;
        }

        .photo-card:focus-visible { outline: 2px solid #b8ff00; outline-offset: 3px; }

        .photo-card img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .photo-card:hover img { transform: scale(1.06); }

        .photo-overlay {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 40px 15px 14px;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.82));
        }

        .photo-overlay h3 {
            font-size: 14px;
            font-weight: 800;
        }

        .photo-overlay p {
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.62);
            font-size: 11px;
        }

        /* ================= VIDEO ================= */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .video-card {
            overflow: hidden;
            border-radius: 10px;
            background: rgba(5, 17, 10, 0.90);
            border: 1px solid rgba(255, 255, 255, 0.10);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.28);
        }

        .video-wrapper {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000000;
        }

        .video-wrapper iframe,
        .video-wrapper video {
            width: 100%;
            height: 100%;
            display: block;
            border: 0;
            object-fit: cover;
        }

        .video-info { padding: 14px 16px 16px; }

        .video-category {
            color: #b8ff00;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .video-info h3 {
            font-size: 15px;
            line-height: 1.35;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .video-info p {
            color: rgba(255, 255, 255, 0.58);
            font-size: 11px;
            line-height: 1.5;
        }

        /* ================= LIGHTBOX ================= */
        .lightbox {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 30px;
            background: rgba(0, 0, 0, 0.9);
        }

        .lightbox.open { display: flex; }

        .lightbox img {
            max-width: 100%;
            max-height: calc(100vh - 120px);
            border-radius: 10px;
        }

        .lightbox p {
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
        }

        .lightbox-close {
            position: absolute;
            top: 16px;
            right: 24px;
            border: 0;
            background: transparent;
            color: #ffffff;
            font-size: 34px;
            cursor: pointer;
        }

        /* ================= FOOTER ================= */
        .footer {
            text-align: center;
            padding: 35px 0 10px;
            color: rgba(255, 255, 255, 0.38);
            font-size: 11px;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 1100px) {
            .navbar { padding: 0 25px; }
            .nav-menu { gap: 16px; }
            .user-area { gap: 15px; }
            .photo-card { height: 165px; }
        }

        @media (max-width: 850px) {
            .navbar {
                height: auto;
                min-height: 68px;
                display: flex;
                padding: 14px 20px;
                flex-wrap: wrap;
                gap: 12px;
            }

            .nav-menu {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
                gap: 12px 18px;
            }

            .user-area {
                width: 100%;
                justify-content: center;
                gap: 18px;
            }

            .photo-grid { grid-template-columns: repeat(2, 1fr); }
            .video-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .content { width: 92%; padding-top: 25px; }
            .photo-grid { grid-template-columns: 1fr; }
            .photo-card { height: 210px; }
            .page-header h1 { font-size: 28px; }
            .brand { font-size: 18px; }
            .user-area { justify-content: flex-start; }
            .user-name { display: none; }
        }
    </style>
    @include('partials.brand-head')
</head>

<body>

<div class="page">

    {{-- ================= NAVBAR ================= --}}
    @include('partials.site-navbar')

    {{-- ================= MAIN ================= --}}
    <main class="content">

        <header class="page-header">
            <div class="eyebrow">Golf Booking Lesson</div>
            <h1>GALERI GOLF</h1>
            <p>
                Lihat aktivitas latihan, suasana golf course,
                dan video rekomendasi untuk membantu meningkatkan
                permainan golf Anda.
            </p>
        </header>

        {{-- ================= FOTO GALERI (dari database) ================= --}}
        <section>
            <div class="section-title">
                <div class="section-line"></div>
                <h2><span>FOTO</span> GALERI</h2>
            </div>

            @if ($photos->isEmpty())
                <div class="empty-box">Belum ada foto di galeri.</div>
            @else
                <div class="photo-grid">
                    @foreach ($photos as $photo)
                        <button type="button" class="photo-card"
                                data-src="{{ $photo->image_url }}" data-title="{{ $photo->title }}">
                            <img src="{{ $photo->image_url }}" alt="{{ $photo->title }}" loading="lazy">

                            <div class="photo-overlay">
                                <h3>{{ $photo->title }}</h3>

                                @if ($photo->category || $photo->description)
                                    <p>{{ $photo->category ?: \Illuminate\Support\Str::limit($photo->description, 60) }}</p>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ================= VIDEO REKOMENDASI (dari database) ================= --}}
        <section>
            <div class="section-title">
                <div class="section-line"></div>
                <h2><span>VIDEO</span> REKOMENDASI</h2>
            </div>

            @if ($videos->isEmpty())
                <div class="empty-box">Belum ada video rekomendasi.</div>
            @else
                <div class="video-grid">
                    @foreach ($videos as $video)
                        <article class="video-card">
                            <div class="video-wrapper">
                                @if ($video->embed_url)
                                    <iframe
                                        src="{{ $video->embed_url }}"
                                        title="{{ $video->title }}"
                                        loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen>
                                    </iframe>
                                @elseif ($video->video_file_url)
                                    <video controls preload="metadata"
                                           @if ($video->image_url) poster="{{ $video->image_url }}" @endif>
                                        <source src="{{ $video->video_file_url }}">
                                    </video>
                                @endif
                            </div>

                            <div class="video-info">
                                @if ($video->category)
                                    <div class="video-category">{{ $video->category }}</div>
                                @endif

                                <h3>{{ $video->title }}</h3>

                                @if ($video->description)
                                    <p>{{ $video->description }}</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="footer">
            &copy; {{ date('Y') }} Golf Booking Lesson. All rights reserved.
        </div>

    </main>

</div>

{{-- ================= LIGHTBOX FOTO ================= --}}
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true">
    <button type="button" class="lightbox-close" aria-label="Tutup">×</button>
    <img src="" alt="">
    <p></p>
</div>

<script>
    (() => {
        const box = document.getElementById('lightbox');
        const img = box.querySelector('img');
        const caption = box.querySelector('p');
        const close = () => box.classList.remove('open');

        document.querySelectorAll('.photo-card').forEach((card) => {
            card.addEventListener('click', () => {
                img.src = card.dataset.src;
                img.alt = card.dataset.title;
                caption.textContent = card.dataset.title;
                box.classList.add('open');
            });
        });

        box.addEventListener('click', (event) => {
            if (event.target !== img) close();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });
    })();
</script>

</body>

</html>
