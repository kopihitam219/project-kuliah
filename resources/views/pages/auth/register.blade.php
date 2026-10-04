<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register - GolfSwing</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.brand-head')
</head>

<body>

<div class="login-page">

    {{-- ========================================
         LEFT HERO
    ========================================= --}}
    <section class="hero">

        <div class="hero-content">

            {{-- Brand --}}
            <div class="brand">

                <div class="brand-icon">
                    +
                </div>

                <div>
                    <div class="brand-name">
                        GolfSwing
                    </div>

                    <div class="brand-subtitle">
                        Analysis & Replay
                    </div>
                </div>

            </div>


            {{-- Hero Content --}}
            <div class="hero-middle">

                <h1 class="hero-title">
                    Tingkatkan<br>
                    Permainan Golf Anda<br>
                    <span>dengan Analisis yang Tepat</span>
                </h1>

                <p class="hero-description">
                    Rekam, analisis, dan bandingkan swing Anda
                    untuk hasil latihan yang lebih maksimal.
                </p>

            </div>


            {{-- Features --}}
            <div class="features">

                <div>
                    <div class="feature-icon">◉</div>

                    <div class="feature-title">
                        Rekam
                    </div>

                    <div class="feature-text">
                        Simpan video swing Anda.
                    </div>
                </div>


                <div>
                    <div class="feature-icon">↗</div>

                    <div class="feature-title">
                        Analisis
                    </div>

                    <div class="feature-text">
                        Analisis gerakan swing.
                    </div>
                </div>


                <div>
                    <div class="feature-icon">◌</div>

                    <div class="feature-title">
                        Bandingkan
                    </div>

                    <div class="feature-text">
                        Bandingkan hasil latihan.
                    </div>
                </div>


                <div>
                    <div class="feature-icon">↑</div>

                    <div class="feature-title">
                        Tingkatkan
                    </div>

                    <div class="feature-text">
                        Tingkatkan performa Anda.
                    </div>
                </div>

            </div>

        </div>

    </section>


    {{-- ========================================
         RIGHT REGISTER
    ========================================= --}}
    <section class="login-section">

        <div class="login-container">

            {{-- Login Brand --}}
            <div class="login-brand">

                <div class="login-brand-icon">
                    +
                </div>

                <div class="login-brand-name">
                    Golf<span>Swing</span>
                </div>

                <div class="login-brand-subtitle">
                    Analysis & Replay
                </div>

            </div>


            {{-- Title --}}
            <h1 class="welcome-title">
                Buat Akun
            </h1>

            <p class="welcome-description">
                Daftarkan akun Anda untuk mulai menggunakan
                GolfSwing dan menganalisis permainan golf Anda.
            </p>


            {{-- Validation Errors --}}
            @if ($errors->any())

                <div class="error-message">

                    @foreach ($errors->all() as $error)

                        <div>
                            {{ $error }}
                        </div>

                    @endforeach

                </div>

            @endif


            {{-- Register Form --}}
            <form
                method="POST"
                action="{{ route('register') }}"
            >

                @csrf


                {{-- Name --}}
                <div class="form-group">

                    <label
                        for="name"
                        class="form-label"
                    >
                        Nama
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-input"
                        placeholder="Masukkan nama Anda"
                        required
                        autofocus
                        autocomplete="name"
                    >

                </div>


                {{-- Email --}}
                <div class="form-group">

                    <label
                        for="email"
                        class="form-label"
                    >
                        Email
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-input"
                        placeholder="Masukkan email Anda"
                        required
                        autocomplete="email"
                    >

                </div>


                {{-- Password --}}
                <div class="form-group">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Password
                    </label>

                    <div class="input-wrapper">

                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-input password-input"
                            placeholder="Masukkan password"
                            required
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            class="password-button"
                            onclick="togglePassword('password', this)"
                            aria-label="Tampilkan password"
                        >
                            ◉
                        </button>

                    </div>

                </div>


                {{-- Confirm Password --}}
                <div class="form-group">

                    <label
                        for="password_confirmation"
                        class="form-label"
                    >
                        Konfirmasi Password
                    </label>

                    <div class="input-wrapper">

                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            class="form-input password-input"
                            placeholder="Ulangi password"
                            required
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            class="password-button"
                            onclick="togglePassword('password_confirmation', this)"
                            aria-label="Tampilkan password"
                        >
                            ◉
                        </button>

                    </div>

                </div>


                {{-- Register Button --}}
                <button
                    type="submit"
                    class="login-button"
                >
                    Buat Akun
                </button>

            </form>


            {{-- Back To Login --}}
            <div class="register">

                Sudah punya akun?

                <a
                    href="{{ route('login') }}"
                    class="back-login"
                >
                    ← Kembali ke Login
                </a>

            </div>

        </div>

    </section>

</div>


{{-- ========================================
     SHOW / HIDE PASSWORD
========================================= --}}
<script>

function togglePassword(inputId, button)
{
    const input = document.getElementById(inputId);

    if (input.type === 'password') {

        input.type = 'text';

        button.textContent = '◉';

    } else {

        input.type = 'password';

        button.textContent = '◉';

    }
}

</script>

</body>
</html>