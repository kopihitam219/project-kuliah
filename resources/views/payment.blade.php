<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Payment | {{ \App\Support\Brand::name() }}</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                radial-gradient(
                    circle at top left,
                    #173b2d 0%,
                    #0b1712 38%,
                    #070c09 75%
                );

            color: #f4f7f5;

            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }


        /* =====================================================
           PAGE
        ===================================================== */

        .page {
            min-height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    rgba(7, 12, 9, 0.35),
                    rgba(7, 12, 9, 0.95)
                );
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar {
            width: 100%;

            min-height: 68px;

            padding: 0 5%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 25px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.08);

            background:
                rgba(6, 12, 9, 0.88);

            backdrop-filter: blur(12px);

            position: relative;

            z-index: 10;
        }


        .brand {
            font-size: 19px;

            font-weight: 700;

            letter-spacing: -0.4px;

            white-space: nowrap;
        }


        .brand span {
            color: #9ee870;
        }


        .nav-menu {
            display: flex;

            align-items: center;

            justify-content: center;

            gap: 23px;

            font-size: 12px;

            color: #b9c4be;
        }


        .nav-menu a {
            transition:
                color .2s ease;
        }


        .nav-menu a:hover {
            color: #ffffff;
        }


        .nav-menu .booking-btn {
            padding:
                8px 15px;

            border:
                1px solid
                #9ee870;

            color: #9ee870;

            border-radius: 5px;

            font-weight: 700;

            letter-spacing: 0.4px;
        }


        .nav-menu .booking-btn:hover {
            background: #9ee870;

            color: #08100b;
        }


        .user-area {
            display: flex;

            align-items: center;

            gap: 12px;

            white-space: nowrap;
        }


        .user-name {
            font-size: 12px;

            color: #d9e1dc;
        }


        .logout-form {
            margin: 0;
        }


        .logout-btn {
            border: 0;

            background: transparent;

            color: #9ca8a1;

            font-size: 12px;

            cursor: pointer;

            padding: 4px;
        }


        .logout-btn:hover {
            color: #ffffff;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .payment-main {
            width: min(
                1080px,
                92%
            );

            margin: 0 auto;

            padding:
                38px 0 50px;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .page-heading {
            margin-bottom: 26px;

            text-align: center;
        }


        .eyebrow {
            color: #9ee870;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 3px;

            margin-bottom: 7px;
        }


        .page-heading h1 {
            font-size: clamp(
                30px,
                3.5vw,
                42px
            );

            line-height: 1;

            letter-spacing: -1.2px;

            margin-bottom: 10px;
        }


        .page-heading h1 span {
            color: #9ee870;
        }


        .page-heading p {
            max-width: 600px;

            margin: 0 auto;

            color: #9ca8a1;

            font-size: 13px;

            line-height: 1.5;
        }


        /* =====================================================
           PAYMENT GRID
        ===================================================== */

        .payment-grid {
            display: grid;

            grid-template-columns:
                minmax(0, 0.88fr)
                minmax(0, 1.12fr);

            gap: 18px;

            align-items: start;
        }


        .panel {
            border:
                1px solid
                rgba(255,255,255,0.09);

            background:
                rgba(15, 24, 19, 0.82);

            border-radius: 10px;

            overflow: hidden;

            box-shadow:
                0 15px 45px
                rgba(0,0,0,0.22);
        }


        /* =====================================================
           LEFT PANEL
        ===================================================== */

        .event-panel {
            display: flex;

            flex-direction: column;
        }


        .poster-wrap {
            width: 100%;

            background: #050806;

            padding: 14px;

            display: flex;

            justify-content: center;

            align-items: center;
        }


        .poster-wrap img {
            display: block;

            width: min(
                100%,
                280px
            );

            max-height: 390px;

            object-fit: contain;

            border-radius: 6px;

            box-shadow:
                0 12px 35px
                rgba(0,0,0,0.4);
        }


        .order-content {
            padding: 18px;
        }


        .section-label {
            color: #8e9b94;

            text-transform: uppercase;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 1.8px;

            margin-bottom: 7px;
        }


        .event-title {
            font-size: 19px;

            font-weight: 800;

            margin-bottom: 14px;
        }


        .event-info {
            display: grid;

            gap: 8px;

            margin-bottom: 17px;
        }


        .info-row {
            display: flex;

            align-items: flex-start;

            gap: 10px;

            color: #c6d0ca;

            font-size: 12px;

            line-height: 1.45;
        }


        .info-icon {
            width: 21px;

            flex: 0 0 21px;

            color: #9ee870;

            font-size: 12px;

            text-align: center;
        }


        .price-box {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            padding:
                13px 15px;

            border:
                1px solid
                rgba(158,232,112,0.18);

            background:
                rgba(158,232,112,0.06);

            border-radius: 7px;
        }


        .price-label {
            color: #96a39b;

            font-size: 9px;

            text-transform: uppercase;

            letter-spacing: 1px;
        }


        .price {
            color: #9ee870;

            font-size: 20px;

            font-weight: 800;
        }


        /* =====================================================
           RIGHT PANEL
        ===================================================== */

        .payment-panel {
            padding: 22px;
        }


        .panel-heading {
            display: flex;

            align-items: center;

            gap: 10px;

            margin-bottom: 17px;
        }


        .panel-icon {
            width: 34px;

            height: 34px;

            border-radius: 7px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(158,232,112,0.10);

            color: #9ee870;

            font-size: 15px;

            flex: 0 0 34px;
        }


        .panel-heading h2 {
            font-size: 17px;

            font-weight: 700;
        }


        .panel-heading p {
            margin-top: 3px;

            color: #7f8b84;

            font-size: 10px;
        }


        /* =====================================================
           QR SECTION
        ===================================================== */

        .qr-section {
            border:
                1px solid
                rgba(255,255,255,0.08);

            border-radius: 8px;

            background:
                rgba(4,8,6,0.55);

            padding: 15px;

            text-align: center;

            margin-bottom: 15px;
        }


        .qr-title {
            font-size: 12px;

            font-weight: 700;

            margin-bottom: 10px;
        }


        .qr-box {
            width: 155px;

            height: 155px;

            margin:
                0 auto 11px;

            padding: 8px;

            background: #ffffff;

            border-radius: 7px;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .qr-box svg {
            display: block;

            width: 100%;

            height: 100%;
        }


        .dummy-warning {
            display: inline-block;

            padding:
                6px 10px;

            border-radius: 100px;

            background:
                rgba(255,193,7,0.09);

            border:
                1px solid
                rgba(255,193,7,0.2);

            color: #e7c875;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 0.5px;
        }


        /* =====================================================
           OTHER PAYMENT
        ===================================================== */

        .other-title {
            color: #8e9b94;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 1.5px;

            text-transform: uppercase;

            margin-bottom: 8px;
        }


        .payment-methods {
            display: grid;

            gap: 7px;
        }


        .method {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            padding:
                10px 12px;

            border:
                1px solid
                rgba(255,255,255,0.07);

            background:
                rgba(255,255,255,0.025);

            border-radius: 6px;
        }


        .method-left {
            display: flex;

            align-items: center;

            gap: 9px;

            min-width: 0;
        }


        .method-logo {
            width: 36px;

            height: 27px;

            border-radius: 4px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #edf2ee;

            color: #111;

            font-size: 8px;

            font-weight: 900;

            flex: 0 0 36px;
        }


        .method-name {
            font-size: 11px;

            font-weight: 700;

            color: #d9e1dc;
        }


        .method-number {
            font-family:
                Consolas,
                monospace;

            font-size: 10px;

            color: #9ba79f;

            text-align: right;

            white-space: nowrap;
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .pay-button {
            width: 100%;

            margin-top: 15px;

            padding: 12px 15px;

            border: 0;

            border-radius: 6px;

            background: #9ee870;

            color: #08100b;

            font-size: 11px;

            font-weight: 900;

            letter-spacing: 0.7px;

            cursor: pointer;

            transition:
                transform .2s ease,
                background .2s ease;
        }


        .pay-button:hover {
            background: #b0f58a;

            transform:
                translateY(-1px);
        }


        .back-button {
            width: 100%;

            display: block;

            text-align: center;

            margin-top: 8px;

            padding: 10px 15px;

            border:
                1px solid
                rgba(255,255,255,0.10);

            border-radius: 6px;

            color: #9ca8a1;

            font-size: 10px;

            font-weight: 700;
        }


        .back-button:hover {
            color: #ffffff;

            border-color:
                rgba(255,255,255,0.22);
        }


        /* =====================================================
           TRUST
        ===================================================== */

        .trust-row {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 8px;

            margin-top: 14px;

            padding-top: 14px;

            border-top:
                1px solid
                rgba(255,255,255,0.07);
        }


        .trust-item {
            text-align: center;

            color: #718078;

            font-size: 8px;

            line-height: 1.4;
        }


        .trust-item strong {
            display: block;

            color: #aab5af;

            font-size: 9px;

            margin-bottom: 2px;
        }


        /* =====================================================
           TOAST
        ===================================================== */

        .toast {
            position: fixed;

            right: 20px;

            bottom: 20px;

            width: min(
                340px,
                calc(100% - 40px)
            );

            padding:
                13px 16px;

            border-radius: 7px;

            background: #14251b;

            border:
                1px solid
                rgba(158,232,112,0.25);

            color: #dce8df;

            box-shadow:
                0 12px 35px
                rgba(0,0,0,0.35);

            font-size: 11px;

            line-height: 1.5;

            opacity: 0;

            transform:
                translateY(12px);

            pointer-events: none;

            transition:
                .25s ease;

            z-index: 100;
        }


        .toast.show {
            opacity: 1;

            transform:
                translateY(0);
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 980px) {

            .navbar {
                flex-wrap: wrap;

                padding: 16px 5%;
            }


            .nav-menu {
                order: 3;

                width: 100%;

                overflow-x: auto;

                justify-content: flex-start;

                padding-bottom: 2px;
            }


            .payment-grid {
                grid-template-columns: 1fr;
            }


            .poster-wrap img {
                max-height: 420px;
            }

        }


        @media (max-width: 600px) {

            .navbar {
                gap: 13px;
            }


            .brand {
                font-size: 17px;
            }


            .user-area {
                margin-left: auto;
            }


            .user-name {
                display: none;
            }


            .nav-menu {
                gap: 17px;

                font-size: 11px;
            }


            .payment-main {
                width: 94%;

                padding-top: 28px;
            }


            .page-heading {
                margin-bottom: 22px;
            }


            .page-heading h1 {
                font-size: 30px;
            }


            .payment-panel {
                padding: 17px;
            }


            .order-content {
                padding: 16px;
            }


            .poster-wrap {
                padding: 10px;
            }


            .poster-wrap img {
                width: min(
                    100%,
                    270px
                );
            }


            .price-box {
                align-items: flex-start;

                flex-direction: column;
            }


            .trust-row {
                grid-template-columns: 1fr;

                gap: 10px;
            }


            .method {
                align-items: flex-start;

                flex-direction: column;
            }


            .method-number {
                text-align: left;

                margin-left: 45px;
            }

        }

    </style>
    @include('partials.brand-head')

</head>


<body>

<div class="page">


    {{-- =====================================================
         NAVBAR
    ====================================================== --}}

    <nav class="navbar">

        <a
            href="{{ route('dashboard') }}"
            class="brand"
        >
            Golf <span>Booking</span> Lesson
        </a>


        <div class="nav-menu">

            <a href="{{ route('dashboard') }}">
                Home
            </a>

            <a href="{{ route('program') }}">
                Program
            </a>

            <a href="{{ route('galeri') }}">
                Galeri
            </a>

            <a href="{{ route('event') }}">
                Event
            </a>

            <a href="{{ route('contact') }}">
                Contact
            </a>

            <a
                href="{{ route('booking') }}"
                class="booking-btn"
            >
                BOOKING
            </a>

        </div>


        <div class="user-area">

            <span class="user-name">
                Hi, {{ auth()->user()->name }}
            </span>

            <form
                method="POST"
                action="{{ route('logout') }}"
                class="logout-form"
            >

                @csrf

                <button
                    type="submit"
                    class="logout-btn"
                >
                    Logout
                </button>

            </form>

        </div>

    </nav>



    {{-- =====================================================
         MAIN
    ====================================================== --}}

    <main class="payment-main">


        {{-- =================================================
             HEADER
        ================================================== --}}

        <header class="page-heading">

            <div class="eyebrow">
                SECURE PAYMENT
            </div>

            <h1>
                EVENT <span>PAYMENT</span>
            </h1>

            <p>
                Selesaikan pembayaran untuk mengamankan
                pendaftaran Golf Coaching Clinic Anda.
            </p>

        </header>



        {{-- =================================================
             PAYMENT GRID
        ================================================== --}}

        <section class="payment-grid">


            {{-- =================================================
                 LEFT : EVENT SUMMARY
            ================================================== --}}

            <div class="panel event-panel">


                {{-- POSTER --}}

                <div class="poster-wrap">

                    <img
                        src="{{ asset('images/event-poster-1.png') }}"
                        alt="Golf Coaching Clinic"
                    >

                </div>



                {{-- ORDER CONTENT --}}

                <div class="order-content">

                    <div class="section-label">
                        Order Summary
                    </div>


                    <h2 class="event-title">
                        GOLF COACHING CLINIC
                    </h2>


                    <div class="event-info">

                        <div class="info-row">

                            <div class="info-icon">
                                📅
                            </div>

                            <div>
                                <strong>
                                    17 Oktober 2026
                                </strong>
                            </div>

                        </div>


                        <div class="info-row">

                            <div class="info-icon">
                                ◷
                            </div>

                            <div>
                                09:00 – 12:00
                            </div>

                        </div>


                        <div class="info-row">

                            <div class="info-icon">
                                ⌖
                            </div>

                            <div>
                                Padang Golf Modernland,
                                Tangerang
                            </div>

                        </div>


                        <div class="info-row">

                            <div class="info-icon">
                                ♙
                            </div>

                            <div>
                                1 Person
                            </div>

                        </div>

                    </div>


                    <div class="price-box">

                        <div>

                            <div class="price-label">
                                Total Payment
                            </div>

                        </div>

                        <div class="price">
                            Rp500.000
                        </div>

                    </div>

                </div>

            </div>



            {{-- =================================================
                 RIGHT : PAYMENT
            ================================================== --}}

            <div class="panel payment-panel">


                {{-- PAYMENT HEADING --}}

                <div class="panel-heading">

                    <div class="panel-icon">
                        $
                    </div>

                    <div>

                        <h2>
                            Pilih Metode Pembayaran
                        </h2>

                        <p>
                            Gunakan salah satu metode pembayaran
                            dummy di bawah.
                        </p>

                    </div>

                </div>



                {{-- =================================================
                     QRIS
                ================================================== --}}

                <div class="qr-section">

                    <div class="qr-title">
                        Scan QR Code
                    </div>


                    <div class="qr-box">

                        {{-- DUMMY QR CODE --}}

                        <svg
                            viewBox="0 0 210 210"
                            xmlns="http://www.w3.org/2000/svg"
                            aria-label="Dummy QR Code"
                        >

                            <rect
                                width="210"
                                height="210"
                                fill="#ffffff"
                            />


                            {{-- TOP LEFT --}}

                            <rect
                                x="10"
                                y="10"
                                width="55"
                                height="55"
                                fill="#000"
                            />

                            <rect
                                x="18"
                                y="18"
                                width="39"
                                height="39"
                                fill="#fff"
                            />

                            <rect
                                x="27"
                                y="27"
                                width="21"
                                height="21"
                                fill="#000"
                            />


                            {{-- TOP RIGHT --}}

                            <rect
                                x="145"
                                y="10"
                                width="55"
                                height="55"
                                fill="#000"
                            />

                            <rect
                                x="153"
                                y="18"
                                width="39"
                                height="39"
                                fill="#fff"
                            />

                            <rect
                                x="162"
                                y="27"
                                width="21"
                                height="21"
                                fill="#000"
                            />


                            {{-- BOTTOM LEFT --}}

                            <rect
                                x="10"
                                y="145"
                                width="55"
                                height="55"
                                fill="#000"
                            />

                            <rect
                                x="18"
                                y="153"
                                width="39"
                                height="39"
                                fill="#fff"
                            />

                            <rect
                                x="27"
                                y="162"
                                width="21"
                                height="21"
                                fill="#000"
                            />


                            {{-- DUMMY PATTERN --}}

                            <path
                                fill="#000"
                                d="
                                    M75 10h10v10H75z
                                    M95 10h10v10H95z
                                    M115 10h10v10H115z
                                    M75 30h10v10H75z
                                    M105 30h20v10H105z
                                    M75 50h20v10H75z
                                    M110 50h10v10H110z

                                    M75 75h10v10H75z
                                    M95 75h20v10H95z
                                    M125 75h10v10H125z
                                    M145 75h20v10H145z
                                    M175 75h20v10H175z

                                    M75 95h20v10H75z
                                    M105 95h10v10H105z
                                    M125 95h20v10H125z
                                    M155 95h10v10H155z
                                    M175 95h20v10H175z

                                    M75 115h10v10H75z
                                    M95 115h20v10H95z
                                    M125 115h10v10H125z
                                    M145 115h20v10H145z
                                    M175 115h10v10H175z

                                    M75 135h20v10H75z
                                    M105 135h20v10H105z
                                    M135 135h10v10H135z
                                    M155 135h20v10H155z

                                    M75 155h10v10H75z
                                    M95 155h20v10H95z
                                    M125 155h20v10H125z
                                    M155 155h10v10H155z
                                    M175 155h20v10H175z

                                    M75 175h20v10H75z
                                    M105 175h10v10H105z
                                    M125 175h20v10H125z
                                    M155 175h20v10H155z
                                    M185 175h10v10H185z

                                    M75 195h10v5H75z
                                    M95 195h20v5H95z
                                    M135 195h10v5H135z
                                    M165 195h20v5H165z
                                "
                            />

                        </svg>

                    </div>


                    <div class="dummy-warning">
                        QR CODE DUMMY – BELUM AKTIF
                    </div>

                </div>



                {{-- =================================================
                     OTHER PAYMENT
                ================================================== --}}

                <div class="other-title">
                    Pembayaran Lainnya
                </div>


                <div class="payment-methods">


                    {{-- BCA --}}

                    <div class="method">

                        <div class="method-left">

                            <div class="method-logo">
                                BCA
                            </div>

                            <div class="method-name">
                                BCA Virtual Account
                            </div>

                        </div>

                        <div class="method-number">
                            1234567890
                        </div>

                    </div>



                    {{-- MANDIRI --}}

                    <div class="method">

                        <div class="method-left">

                            <div class="method-logo">
                                MANDIRI
                            </div>

                            <div class="method-name">
                                Mandiri Virtual Account
                            </div>

                        </div>

                        <div class="method-number">
                            880012345678
                        </div>

                    </div>



                    {{-- DANA --}}

                    <div class="method">

                        <div class="method-left">

                            <div class="method-logo">
                                DANA
                            </div>

                            <div class="method-name">
                                DANA
                            </div>

                        </div>

                        <div class="method-number">
                            0812-0000-1234
                        </div>

                    </div>

                </div>



                {{-- =================================================
                     CONFIRM PAYMENT
                ================================================== --}}

                <button
                    type="button"
                    class="pay-button"
                    id="paidButton"
                >
                    ✓ &nbsp; SAYA SUDAH BAYAR
                </button>


                <a
                    href="{{ route('event') }}"
                    class="back-button"
                >
                    ← Kembali ke Event
                </a>



                {{-- =================================================
                     TRUST
                ================================================== --}}

                <div class="trust-row">

                    <div class="trust-item">

                        <strong>
                            🔒 Pembayaran Aman
                        </strong>

                        Data pembayaran terlindungi.

                    </div>


                    <div class="trust-item">

                        <strong>
                            ⚡ Proses Cepat
                        </strong>

                        Konfirmasi pembayaran mudah.

                    </div>


                    <div class="trust-item">

                        <strong>
                            ☎ Dukungan Tim
                        </strong>

                        Kami siap membantu.

                    </div>

                </div>

            </div>

        </section>

    </main>


    {{-- =====================================================
         TOAST
    ====================================================== --}}

    <div
        class="toast"
        id="toast"
    ></div>


</div>



<script>

    const paidButton =
        document.getElementById('paidButton');

    const toast =
        document.getElementById('toast');


    if (paidButton && toast) {

        paidButton.addEventListener(
            'click',
            function () {

                toast.textContent =
                    'Pembayaran berhasil dicatat sebagai DEMO. Sistem payment asli belum aktif.';

                toast.classList.add('show');


                setTimeout(
                    function () {

                        toast.classList.remove('show');

                    },
                    4000
                );

            }
        );

    }

</script>

</body>

</html>