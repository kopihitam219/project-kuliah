@php
    $brandHero = \App\Support\Brand::hero();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ \App\Support\Brand::name() }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #071b13;
        }

        /* =========================
           HERO
        ========================= */

        .hero {
            position: relative;

            width: 100%;
            height: 100vh;

            background-image:
                linear-gradient(
                    90deg,
                    rgba(2, 18, 12, 0.88) 0%,
                    rgba(2, 18, 12, 0.68) 40%,
                    rgba(2, 18, 12, 0.30) 75%,
                    rgba(2, 18, 12, 0.20) 100%
                ),
                url('{{ \App\Support\Brand::background('public') }}');

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;

            color: white;
        }

        /* =========================
           NAVBAR
        ========================= */

        .navbar {
            position: relative;
            z-index: 20;

            width: 100%;
            height: 68px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 5%;

            border-bottom: 1px solid rgba(255,255,255,0.10);

            background: rgba(3, 20, 13, 0.45);
        }

        .brand {
            color: white;
            text-decoration: none;

            font-size: 21px;
            font-weight: 800;

            letter-spacing: -1px;

            white-space: nowrap;
        }

        .brand span {
            color: #8cf238;
        }

        /* =========================
           NAV MENU
        ========================= */

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-menu a {
            color: rgba(255,255,255,0.90);

            text-decoration: none;

            font-size: 13px;
            font-weight: 600;

            transition: 0.2s ease;
        }

        .nav-menu a:hover {
            color: #8cf238;
        }

        /* =========================
           BOOKING BUTTON
        ========================= */

        .booking-nav {
            display: inline-flex !important;

            align-items: center;
            justify-content: center;

            height: 34px;

            padding: 0 18px;

            border: 1px solid #8cf238;
            border-radius: 18px;

            background: transparent;

            color: #8cf238 !important;

            font-size: 12px !important;
            font-weight: 800 !important;

            letter-spacing: 0.4px;

            transition: 0.2s ease;
        }

        .booking-nav:hover {
            background: #8cf238;

            color: #07150e !important;
        }

        /* =========================
           USER AREA
        ========================= */

        .user-area {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .user-name {
            display: flex;
            align-items: center;
            gap: 8px;

            color: white;

            font-size: 13px;
            font-weight: 600;

            white-space: nowrap;
        }

        .user-dot {
            width: 16px;
            height: 16px;

            border: 3px solid #8cf238;

            border-radius: 50%;
        }

        .logout-button {
            height: 36px;

            padding: 0 20px;

            border: 1px solid #8cf238;
            border-radius: 20px;

            background: rgba(255,255,255,0.03);

            color: white;

            font-size: 12px;
            font-weight: 700;

            cursor: pointer;

            transition: 0.2s ease;
        }

        .logout-button:hover {
            background: #8cf238;
            color: #07150e;
        }

        /* =========================
           HERO CONTENT
        ========================= */

        .hero-content {
            position: relative;
            z-index: 10;

            width: 90%;
            max-width: 1250px;

            height: calc(100vh - 68px);

            margin: 0 auto;

            display: flex;
            align-items: center;
        }

        .content-box {
            width: 570px;

            margin-top: -20px;
        }

        .small-title {
            margin-bottom: 13px;

            color: #8cf238;

            font-size: 13px;
            font-weight: 700;

            letter-spacing: 3px;
        }

        .main-title {
            font-size: clamp(40px, 4.5vw, 62px);

            line-height: 0.98;

            font-weight: 900;

            letter-spacing: -2px;

            margin-bottom: 8px;
        }

        .main-title .green {
            color: #8cf238;
        }

        .description {
            max-width: 500px;

            margin-top: 18px;
            margin-bottom: 25px;

            color: rgba(255,255,255,0.78);

            font-size: 14px;
            line-height: 1.6;
        }

        /* =========================
           HERO BUTTONS
        ========================= */

        .buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-width: 145px;
            height: 43px;

            padding: 0 20px;

            border-radius: 7px;

            text-decoration: none;

            font-size: 11px;
            font-weight: 800;

            letter-spacing: 0.7px;

            transition: 0.2s ease;
        }

        .btn-primary {
            background: #8cf238;

            border: 1px solid #8cf238;

            color: #07150e;
        }

        .btn-primary:hover {
            background: white;
            border-color: white;

            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.06);

            border: 1px solid rgba(255,255,255,0.45);

            color: white;
        }

        .btn-secondary:hover {
            background: white;

            color: #07150e;

            transform: translateY(-2px);
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 1200px) {

            .navbar {
                padding: 0 3%;
            }

            .nav-menu {
                gap: 16px;
            }

            .main-title {
                font-size: 50px;
            }
        }

        @media (max-width: 950px) {

            .navbar {
                padding: 0 20px;
            }

            .nav-menu {
                gap: 12px;
            }

            .nav-menu a {
                font-size: 11px;
            }

            .booking-nav {
                padding: 0 14px;
            }

            .user-area {
                gap: 8px;
            }

            .logout-button {
                padding: 0 14px;
            }
        }

        @media (max-width: 850px) {

            html,
            body {
                overflow: hidden;
            }

            .navbar {
                height: 64px;

                padding: 0 20px;
            }

            .nav-menu {
                display: none;
            }

            .brand {
                font-size: 18px;
            }

            .user-name {
                display: none;
            }

            .hero-content {
                height: calc(100vh - 64px);

                width: 90%;
            }

            .content-box {
                width: 100%;
                max-width: 520px;
            }

            .main-title {
                font-size: clamp(38px, 9vw, 54px);
            }

            .description {
                font-size: 13px;
            }
        }

        @media (max-width: 520px) {

            .hero {
                background-position: 60% center;
            }

            .logout-button {
                height: 34px;

                padding: 0 15px;
            }

            .small-title {
                font-size: 10px;
                letter-spacing: 2px;
            }

            .main-title {
                font-size: 38px;
            }

            .description {
                font-size: 12px;

                line-height: 1.5;

                margin-top: 15px;
                margin-bottom: 20px;
            }

            .buttons {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn {
                width: 145px;
            }
        }
    </style>
    @include('partials.brand-head')
</head>

<body>

<div class="hero">

    <!-- =========================
         NAVBAR
    ========================== -->

    @include('partials.site-navbar')


    <!-- =========================
         HERO
    ========================== -->

    <main class="hero-content">

        <div class="content-box">

            <div class="small-title">{{ $brandHero['hero_label'] }}</div>

            <h1 class="main-title">{{ $brandHero['hero_title_1'] }}<br>{{ $brandHero['hero_title_2'] }} <span class="green">{{ $brandHero['hero_highlight'] }}</span></h1>

            <p class="description">{{ $brandHero['hero_text'] }}</p>

            <div class="buttons">

                {{-- BOOK LESSON → BOOKING --}}
                <a
                    href="{{ route('booking') }}"
                    class="btn btn-primary"
                >
                    BOOK LESSON
                </a>

                {{-- LIHAT PROGRAM → PROGRAM --}}
                <a
                    href="{{ route('program') }}"
                    class="btn btn-secondary"
                >
                    LIHAT PROGRAM
                </a>

            </div>

        </div>

    </main>

</div>

</body>
</html>