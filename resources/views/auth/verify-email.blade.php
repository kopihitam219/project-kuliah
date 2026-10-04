<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verifikasi Email - {{ \App\Support\Brand::name() }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            width: 100%;
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* =========================================================
           PAGE
        ========================================================= */

        .verify-page {
            position: relative;

            width: 100%;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px 18px;

            background-image:
                linear-gradient(
                    rgba(0, 0, 0, 0.58),
                    rgba(0, 0, 0, 0.68)
                ),
                url('{{ \App\Support\Brand::background('auth') }}');

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* =========================================================
           CARD
        ========================================================= */

        .verify-card {
            width: 100%;
            max-width: 460px;

            padding: 42px 40px;

            background: rgba(10, 15, 12, 0.90);

            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;

            box-shadow:
                0 25px 70px rgba(0, 0, 0, 0.55),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);

            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);

            color: white;
        }

        /* =========================================================
           LOGO
        ========================================================= */

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-title {
            margin: 0;

            color: #ffffff;

            font-size: 16px;
            font-weight: 700;

            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .logo-title span {
            color: #8bc34a;
        }

        .logo-subtitle {
            margin-top: 6px;

            color: rgba(255, 255, 255, 0.55);

            font-size: 11px;
            letter-spacing: 2px;
        }

        /* =========================================================
           TITLE
        ========================================================= */

        .verify-title {
            margin: 0;

            color: #ffffff;

            text-align: center;

            font-size: 34px;
            line-height: 1.15;
            font-weight: 800;

            letter-spacing: 0.5px;
        }

        .title-line {
            width: 65px;
            height: 4px;

            margin: 15px auto 25px;

            background: #8bc34a;

            border-radius: 999px;
        }

        /* =========================================================
           DESCRIPTION
        ========================================================= */

        .verify-description {
            margin: 0 auto 24px;

            max-width: 370px;

            color: rgba(255, 255, 255, 0.68);

            text-align: center;

            font-size: 14px;
            line-height: 1.65;
        }

        /* =========================================================
           SUCCESS MESSAGE
        ========================================================= */

        .success-message {
            margin-bottom: 20px;

            padding: 12px 14px;

            border-radius: 8px;

            background: rgba(139, 195, 74, 0.12);

            border: 1px solid rgba(139, 195, 74, 0.35);

            color: #b8e986;

            font-size: 13px;
            line-height: 1.5;

            text-align: center;
        }

        /* =========================================================
           BUTTON
        ========================================================= */

        .verify-button {
            width: 100%;
            min-height: 52px;

            padding: 13px 18px;

            border: 0;
            border-radius: 8px;

            background: #8bc34a;

            color: #101510;

            font-size: 13px;
            font-weight: 800;

            letter-spacing: 0.7px;
            text-transform: uppercase;

            cursor: pointer;

            transition:
                background 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .verify-button:hover {
            background: #9ccc65;

            transform: translateY(-1px);

            box-shadow:
                0 8px 25px rgba(139, 195, 74, 0.25);
        }

        .verify-button:active {
            transform: translateY(0);
        }

        /* =========================================================
           LOGOUT
        ========================================================= */

        .logout-area {
            margin-top: 22px;

            text-align: center;

            color: rgba(255, 255, 255, 0.50);

            font-size: 12px;
        }

        .logout-button {
            margin-left: 4px;

            padding: 0;

            border: 0;

            background: transparent;

            color: #8bc34a;

            font-size: 12px;
            font-weight: 700;

            cursor: pointer;
        }

        .logout-button:hover {
            text-decoration: underline;
        }

        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 600px) {

            .verify-page {
                padding: 18px;
            }

            .verify-card {
                padding: 34px 25px;

                border-radius: 14px;
            }

            .verify-title {
                font-size: 30px;
            }

            .verify-description {
                font-size: 13px;
            }

            .verify-button {
                font-size: 12px;
                padding: 13px 14px;
            }
        }

        /* =========================================================
           SHORT SCREEN
        ========================================================= */

        @media (max-height: 700px) {

            .verify-page {
                padding-top: 20px;
                padding-bottom: 20px;
            }

            .verify-card {
                padding-top: 28px;
                padding-bottom: 28px;
            }

            .logo {
                margin-bottom: 18px;
            }

            .verify-title {
                font-size: 30px;
            }

            .title-line {
                margin-bottom: 20px;
            }

            .verify-description {
                margin-bottom: 18px;
            }

            .logout-area {
                margin-top: 16px;
            }
        }
    </style>
    @include('partials.brand-head')
</head>

<body>

<div class="verify-page">

    <div class="verify-card">

        <!-- =====================================================
             LOGO
        ====================================================== -->

        <div class="logo">

            <div class="logo-title">
                Golf Booking Lesson
            </div>

            <div class="logo-subtitle">
                
            </div>

        </div>


        <!-- =====================================================
             TITLE
        ====================================================== -->

        <h1 class="verify-title">
            VERIFIKASI EMAIL
        </h1>

        <div class="title-line"></div>


        <!-- =====================================================
             DESCRIPTION
        ====================================================== -->

        <p class="verify-description">
            Terima kasih sudah membuat akun Golf Booking Lesson.
            Silakan verifikasi alamat email Anda untuk
            melanjutkan ke akun Anda.
        </p>


        <!-- =====================================================
             SUCCESS MESSAGE
        ====================================================== -->

        @if (session('status') === 'verification-link-sent')

            <div class="success-message">
                Link verifikasi baru telah dikirim ke email Anda.
                Silakan periksa inbox email Anda.
            </div>

        @endif


        <!-- =====================================================
             RESEND VERIFICATION EMAIL
        ====================================================== -->

        <form
            method="POST"
            action="{{ route('verification.send') }}"
        >

            @csrf

            <button
                type="submit"
                class="verify-button"
            >
                KIRIM ULANG EMAIL VERIFIKASI
            </button>

        </form>


        <!-- =====================================================
             LOGOUT
        ====================================================== -->

        <div class="logout-area">

            Ingin menggunakan akun lain?

            <form
                method="POST"
                action="{{ route('logout') }}"
                style="display:inline;"
            >

                @csrf

                <button
                    type="submit"
                    class="logout-button"
                >
                    Keluar
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>

