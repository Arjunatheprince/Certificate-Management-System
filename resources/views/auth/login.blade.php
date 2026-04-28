@extends('layouts.app')
@section('title', 'Masuk - Sistem Sertifikat Digital')

@section('content')
<style nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    .admin-portal {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 18%, rgba(96, 165, 250, 0.30), transparent 18%),
            radial-gradient(circle at 88% 16%, rgba(59, 130, 246, 0.18), transparent 16%),
            radial-gradient(circle at 75% 72%, rgba(125, 211, 252, 0.22), transparent 18%),
            linear-gradient(135deg, #eff6ff 0%, #ffffff 42%, #f0f7ff 100%);
    }

    .admin-portal .orb {
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        opacity: 0.75;
        filter: blur(6px);
        animation: portal-float 7s ease-in-out infinite;
    }

    .admin-portal .orb.orb-a {
        top: 90px;
        left: 7%;
        width: 160px;
        height: 160px;
        background: rgba(59, 130, 246, 0.20);
    }

    .admin-portal .orb.orb-b {
        top: 150px;
        right: 9%;
        width: 220px;
        height: 220px;
        background: rgba(147, 197, 253, 0.28);
        animation-duration: 9s;
        animation-direction: reverse;
    }

    .admin-portal .orb.orb-c {
        bottom: 70px;
        left: 18%;
        width: 120px;
        height: 120px;
        background: rgba(14, 165, 233, 0.18);
        animation-duration: 6.5s;
    }

    .admin-hero,
    .admin-login-card,
    .admin-feature {
        animation: portal-rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .admin-login-card {
        animation-delay: 0.1s;
        position: relative;
        z-index: 30;
        isolation: isolate;
        transform: perspective(1400px) rotateX(0.9deg) rotateY(-1.2deg);
        transform-origin: center center;
        overflow: hidden;
    }

    .admin-login-card::before {
        content: "";
        position: absolute;
        inset: 22px 22px -8px 22px;
        z-index: -2;
        border-radius: 28px;
        background: rgba(15, 23, 42, 0.08);
        filter: blur(20px);
        opacity: 0.22;
        transform: translateZ(-1px);
    }

    .admin-login-card::after {
        display: none;
    }

    .admin-input {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        box-shadow:
            inset 0 1px 2px rgba(15, 23, 42, 0.03),
            0 1px 0 rgba(255, 255, 255, 0.7);
        transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
    }

    .admin-input:focus {
        border-color: rgba(59, 130, 246, 0.45);
        box-shadow:
            0 0 0 4px rgba(59, 130, 246, 0.10),
            inset 0 1px 2px rgba(15, 23, 42, 0.03);
        transform: translateY(-1px);
    }

    .admin-input:-webkit-autofill,
    .admin-input:-webkit-autofill:hover,
    .admin-input:-webkit-autofill:focus,
    .admin-input:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
        box-shadow: 0 0 0 1000px #ffffff inset !important;
        -webkit-text-fill-color: #111827 !important;
        caret-color: #111827;
        border: 1px solid #cbd5e1 !important;
        transition: background-color 99999s ease-in-out 0s;
    }

    .admin-submit {
        background: #2563eb;
        background-image: none;
        color: #ffffff;
        box-shadow: 0 16px 30px -20px rgba(37, 99, 235, 0.65);
        transition: transform 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
    }

    .admin-submit:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 20px 36px -22px rgba(37, 99, 235, 0.72);
    }

    .admin-submit:focus-visible {
        outline: none;
        box-shadow:
            0 0 0 4px rgba(59, 130, 246, 0.16),
            0 20px 36px -22px rgba(37, 99, 235, 0.72);
    }

    .admin-feature {
        animation-delay: 0.2s;
    }

    .admin-password-wrap {
        position: relative;
    }

    .admin-password-input {
        padding-right: 3.25rem;
    }

    .admin-password-toggle {
        position: absolute;
        top: 50%;
        right: 0.85rem;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 0;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        transition: color 180ms ease, transform 180ms ease;
    }

    .admin-password-toggle:hover {
        color: #2563eb;
    }

    .admin-password-toggle:focus-visible {
        outline: none;
        color: #2563eb;
    }

    .admin-line {
        background: linear-gradient(90deg, #93c5fd 0%, #2563eb 50%, #7dd3fc 100%);
        background-size: 200% 100%;
        animation: portal-line 4s linear infinite;
    }

    @keyframes portal-rise {
        from {
            opacity: 0;
            transform: translateY(28px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes portal-float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-18px);
        }
    }

    @keyframes portal-line {
        0% {
            background-position: 0% 50%;
        }
        100% {
            background-position: 200% 50%;
        }
    }
</style>

<div class="admin-portal flex flex-col items-center justify-center px-4 py-10" style="min-height:100vh;">
    <div class="orb orb-a"></div>
    <div class="orb orb-b"></div>
    <div class="orb orb-c"></div>

    <div class="relative z-10 w-full max-w-xl flex flex-col items-center gap-8">
        <div class="admin-hero text-center w-full">
            <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">Portal Admin</span>
            <h1 class="mt-4 text-3xl font-bold leading-tight text-gray-900 sm:text-4xl">Portal administrasi resmi untuk pengelolaan dan verifikasi sertifikat digital.</h1>
            <p class="mt-3 text-sm leading-7 text-gray-500">Area ini digunakan untuk mendukung proses administrasi sertifikat, verifikasi dokumen, dan pemantauan aktivitas sistem secara terpusat sesuai kebutuhan institusi.</p>
        </div>

        <div class="admin-login-card relative w-full rounded-3xl border border-slate-200/90 bg-white p-8 shadow-[0_24px_56px_-30px_rgba(15,23,42,0.24)] sm:p-10">
            <div class="admin-line absolute inset-x-8 top-0 h-1 rounded-full"></div>
            <h2 class="text-center text-2xl font-bold text-gray-900">Masuk</h2>
            <p class="mt-2 text-center text-sm text-gray-500">Gunakan akun administrator yang terdaftar.</p>

            @if($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf
                <div class="admin-feature">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="admin-input w-full rounded-xl px-4 py-3 outline-none" placeholder="nama@instansi.ac.id" required autofocus>
                </div>
                <div class="admin-feature">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi</label>
                    <div class="admin-password-wrap">
                        <input type="password" name="password" id="passwordInput" class="admin-input admin-password-input w-full rounded-xl px-4 py-3 outline-none" placeholder="Masukkan kata sandi" required>
                        <button type="button" id="passwordToggle" class="admin-password-toggle" aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg id="passwordEyeOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="passwordEyeClosed" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.6 10.7a3 3 0 0 0 4.2 4.2"></path>
                                <path d="M9.9 5.2A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-4 4.8"></path>
                                <path d="M6.6 6.7C3.9 8.5 2 12 2 12a17.7 17.7 0 0 0 10 7 9.8 9.8 0 0 0 4-.8"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="admin-submit w-full rounded-xl py-3 font-semibold" style="background:#2563eb;background-image:none;">Masuk</button>
            </form>

            <div class="mt-6 pt-5 border-t text-center">
                <a href="{{ route('home') }}" class="text-sm text-blue-600 hover:text-blue-700">Kembali ke halaman verifikasi publik</a>
            </div>
        </div>
    </div>
</div>
<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    const passwordInput = document.getElementById('passwordInput');
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordEyeOpen = document.getElementById('passwordEyeOpen');
    const passwordEyeClosed = document.getElementById('passwordEyeClosed');

    passwordToggle?.addEventListener('click', () => {
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        passwordToggle.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        passwordEyeOpen.classList.toggle('hidden', isHidden);
        passwordEyeClosed.classList.toggle('hidden', !isHidden);
    });
</script>
@endsection
