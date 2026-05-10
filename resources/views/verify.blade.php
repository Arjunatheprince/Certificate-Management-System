@extends('layouts.app')
@section('title', 'Verifikasi Sertifikat Digital')

@section('content')
<div class="min-h-screen bg-slate-50 text-slate-900">
    <style nonce="{{ request()->attributes->get('csp_nonce', '') }}">
        .public-portal {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(96, 165, 250, 0.14), transparent 24%),
                linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
        }

        .public-ambient {
            position: absolute;
            z-index: 0;
            border-radius: 9999px;
            pointer-events: none;
            opacity: 0.62;
            animation: public-float 18s ease-in-out infinite;
        }

        .public-ambient-one {
            top: 4rem;
            left: -5rem;
            width: 24rem;
            height: 24rem;
            background: radial-gradient(circle, rgba(96, 165, 250, 0.3) 0%, rgba(96, 165, 250, 0.12) 46%, transparent 74%);
        }

        .public-ambient-two {
            right: -6rem;
            top: 8rem;
            width: 26rem;
            height: 26rem;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.24) 0%, rgba(147, 197, 253, 0.1) 50%, transparent 75%);
            animation-delay: -4s;
        }

        .public-ambient-three {
            bottom: 1rem;
            left: 50%;
            width: 28rem;
            height: 28rem;
            transform: translateX(-50%);
            background: radial-gradient(circle, rgba(186, 230, 253, 0.26) 0%, rgba(186, 230, 253, 0.1) 42%, transparent 72%);
            animation-delay: -8s;
        }

        .public-grid {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            opacity: 0.28;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: radial-gradient(circle at center, rgba(15, 23, 42, 0.85), transparent 88%);
        }

        .public-wave {
            position: absolute;
            z-index: 0;
            left: -10%;
            width: 120%;
            height: 300px;
            pointer-events: none;
            opacity: 0.52;
            background-repeat: no-repeat;
            background-size: 100% 100%;
            animation: public-drift 22s ease-in-out infinite;
        }

        .public-wave-one {
            top: 8rem;
            background-image: radial-gradient(ellipse at center, rgba(191, 219, 254, 0.26) 0%, rgba(191, 219, 254, 0.08) 38%, transparent 70%);
        }

        .public-wave-two {
            bottom: 5rem;
            background-image: radial-gradient(ellipse at center, rgba(147, 197, 253, 0.22) 0%, rgba(147, 197, 253, 0.08) 36%, transparent 72%);
            animation-delay: -10s;
        }

        .public-silhouette {
            position: absolute;
            z-index: 0;
            pointer-events: none;
            opacity: 0.44;
            filter: drop-shadow(0 22px 52px rgba(37, 99, 235, 0.14));
            animation: public-silhouette-float 20s ease-in-out infinite;
        }

        .public-silhouette svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .public-silhouette-left {
            left: -1rem;
            bottom: 4.5rem;
            width: 320px;
            height: 408px;
        }

        .public-silhouette-right {
            right: 0;
            bottom: 3.5rem;
            width: 348px;
            height: 420px;
            animation-delay: -7s;
        }

        .public-silhouette-top {
            left: 50%;
            top: 5.25rem;
            width: 300px;
            height: 200px;
            transform: translateX(-50%);
            opacity: 0.3;
            animation-delay: -11s;
        }

        .public-particle {
            position: absolute;
            z-index: 0;
            width: 12px;
            height: 12px;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.9) 0%, rgba(96, 165, 250, 0.4) 55%, transparent 72%);
            box-shadow: 0 0 0 10px rgba(191, 219, 254, 0.22);
            pointer-events: none;
            opacity: 0.68;
            animation: public-particle-float 20s linear infinite;
        }

        .public-particle-one {
            top: 18%;
            left: 16%;
        }

        .public-particle-two {
            top: 26%;
            right: 18%;
            animation-delay: -6s;
        }

        .public-particle-three {
            bottom: 18%;
            left: 26%;
            animation-delay: -12s;
        }

        .public-particle-four {
            bottom: 22%;
            right: 22%;
            animation-delay: -16s;
        }

        .public-shell {
            position: relative;
            z-index: 1;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 28px;
            background: #ffffff;
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.08);
            animation: public-rise 700ms ease-out both;
        }

        .public-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0));
            pointer-events: none;
        }

        .public-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.95rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, rgba(219, 234, 254, 0.98), rgba(191, 219, 254, 0.88));
            color: #1d4ed8;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12);
        }

        .public-badge::before {
            content: "";
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            box-shadow: 0 0 0 5px rgba(96, 165, 250, 0.18);
        }

        .public-tab {
            position: relative;
            border-radius: 9999px;
            border: 1px solid rgba(191, 219, 254, 0.55);
            background: rgba(255, 255, 255, 0.9);
            color: #475569;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
            transition: all 180ms ease;
        }

        .public-tab::after {
            content: "";
            position: absolute;
            inset: auto 18% -0.35rem;
            height: 2px;
            border-radius: 9999px;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.75), transparent);
            transform: scaleX(0.4);
            opacity: 0;
            transition: opacity 180ms ease, transform 180ms ease;
        }

        .public-tab:hover {
            color: #1e3a8a;
            background: rgba(239, 246, 255, 0.98);
            border-color: rgba(147, 197, 253, 0.8);
            box-shadow: 0 14px 28px rgba(59, 130, 246, 0.12);
        }

        .public-tab.is-active {
            color: #1d4ed8;
            background: linear-gradient(135deg, rgba(239, 246, 255, 1), rgba(219, 234, 254, 0.98));
            border-color: rgba(96, 165, 250, 0.85);
            box-shadow: 0 16px 30px rgba(37, 99, 235, 0.12), inset 0 0 0 1px rgba(255, 255, 255, 0.92);
        }

        .public-tab.is-active::after {
            opacity: 1;
            transform: scaleX(1);
        }

        .public-input,
        .public-upload {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
        }

        .public-input:focus,
        .public-upload:focus-within {
            border-color: #60a5fa;
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.16);
            outline: none;
        }

        .public-button {
            border: 1px solid rgba(37, 99, 235, 0.08);
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: #ffffff;
            box-shadow: 0 16px 32px rgba(37, 99, 235, 0.24);
            transition: transform 180ms ease, box-shadow 180ms ease, filter 180ms ease;
        }

        .public-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.28);
            filter: saturate(1.03);
        }

        .public-button-secondary {
            border: 1px solid #93c5fd;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #1d4ed8;
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.1);
            transition: all 180ms ease;
        }

        .public-button-secondary:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-color: #60a5fa;
        }

        .public-card {
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
            border-radius: 22px;
            box-shadow: 0 20px 44px rgba(15, 23, 42, 0.06);
        }

        .public-result {
            border-radius: 22px;
            border-width: 1px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.06);
        }

        .public-content-wrap {
            max-width: 760px;
            margin-left: auto;
            margin-right: auto;
        }

        .public-form-wrap {
            max-width: 860px;
            margin-left: auto;
            margin-right: auto;
        }

        @keyframes public-float {
            0%, 100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(0, -18px, 0);
            }
        }

        @keyframes public-rise {
            from {
                opacity: 0;
                transform: translate3d(0, 18px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        @keyframes public-drift {
            0%, 100% {
                transform: translate3d(0, 0, 0) scaleX(1);
            }
            50% {
                transform: translate3d(0, 16px, 0) scaleX(1.03);
            }
        }

        @keyframes public-particle-float {
            0% {
                transform: translate3d(0, 0, 0) scale(0.9);
                opacity: 0;
            }
            12%,
            88% {
                opacity: 0.5;
            }
            50% {
                transform: translate3d(18px, -22px, 0) scale(1);
            }
            100% {
                transform: translate3d(-10px, -48px, 0) scale(0.92);
                opacity: 0;
            }
        }

        @keyframes public-silhouette-float {
            0%, 100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(0, -12px, 0);
            }
        }
    </style>

<div class="public-portal min-h-screen">
    <div class="public-grid"></div>
    <div class="public-wave public-wave-one"></div>
    <div class="public-wave public-wave-two"></div>
    <div class="public-silhouette public-silhouette-top" aria-hidden="true">
        <svg viewBox="0 0 240 170" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 136C44 100 74 86 102 90C116 62 146 44 180 48C212 52 228 76 220 110C232 116 238 128 236 144H20V136Z" fill="url(#topSilhouette)" />
            <defs>
                <linearGradient id="topSilhouette" x1="24" y1="52" x2="220" y2="152" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#BFDBFE" />
                    <stop offset="1" stop-color="#60A5FA" stop-opacity="0.45" />
                </linearGradient>
            </defs>
        </svg>
    </div>
    <div class="public-ambient public-ambient-one"></div>
    <div class="public-ambient public-ambient-two"></div>
    <div class="public-ambient public-ambient-three"></div>
    <div class="public-silhouette public-silhouette-left" aria-hidden="true">
        <svg viewBox="0 0 260 340" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 340V250C34 214 70 206 96 224C106 178 138 154 176 156C214 158 246 186 260 234V340H0Z" fill="url(#leftSilhouetteMain)" />
            <path d="M28 340V278C58 252 88 248 112 264C122 228 146 208 176 208C208 208 232 228 244 262V340H28Z" fill="url(#leftSilhouetteSoft)" />
            <defs>
                <linearGradient id="leftSilhouetteMain" x1="20" y1="156" x2="230" y2="340" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE" />
                    <stop offset="1" stop-color="#93C5FD" stop-opacity="0.62" />
                </linearGradient>
                <linearGradient id="leftSilhouetteSoft" x1="48" y1="208" x2="214" y2="340" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#EFF6FF" />
                    <stop offset="1" stop-color="#BFDBFE" stop-opacity="0.55" />
                </linearGradient>
            </defs>
        </svg>
    </div>
    <div class="public-silhouette public-silhouette-right" aria-hidden="true">
        <svg viewBox="0 0 300 360" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M26 360V246C52 218 84 206 116 214C132 180 162 160 198 160C242 160 278 192 292 240V360H26Z" fill="url(#rightSilhouetteMain)" />
            <path d="M0 360V292C24 274 50 268 74 276C92 246 120 228 154 226C196 224 232 246 258 286V360H0Z" fill="url(#rightSilhouetteSoft)" />
            <defs>
                <linearGradient id="rightSilhouetteMain" x1="48" y1="166" x2="286" y2="360" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE" />
                    <stop offset="1" stop-color="#60A5FA" stop-opacity="0.56" />
                </linearGradient>
                <linearGradient id="rightSilhouetteSoft" x1="16" y1="230" x2="256" y2="360" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#EFF6FF" />
                    <stop offset="1" stop-color="#BFDBFE" stop-opacity="0.48" />
                </linearGradient>
            </defs>
        </svg>
    </div>
    <div class="public-particle public-particle-one"></div>
    <div class="public-particle public-particle-two"></div>
    <div class="public-particle public-particle-three"></div>
    <div class="public-particle public-particle-four"></div>
    <nav class="border-b border-slate-200/80 bg-white/88 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-blue-700 uppercase tracking-[0.22em]">Verifikasi Publik</p>
                <p class="text-base font-bold text-slate-900">Sistem Sertifikat Digital</p>
            </div>
            <a href="{{ route('login') }}" class="public-button inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold">Portal Admin</a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12 relative z-10">
        <div class="mb-8 text-center sm:mb-10">
            <span class="public-badge">Layanan Verifikasi Resmi</span>
            <h1 class="mx-auto mt-4 max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Verifikasi keaslian sertifikat digital secara cepat, jelas, dan terpercaya.</h1>
            <p class="mx-auto mt-4 max-w-3xl text-base leading-8 text-slate-600 sm:text-lg">Gunakan halaman ini untuk memeriksa keabsahan sertifikat melalui nomor sertifikat, pemindaian kode QR, atau unggah gambar dokumen pendukung.</p>
        </div>

        <div class="public-shell mx-auto max-w-4xl p-5 sm:p-8">
            <div class="public-content-wrap relative z-10">
                <div class="mb-8 flex flex-col items-center gap-4 border-b border-slate-200 pb-6 text-center">
                    <div class="flex flex-wrap justify-center gap-2">
                        <button type="button" data-tab="manual" id="tab-manual" class="tab-btn public-tab is-active px-5 py-2.5 text-sm font-semibold whitespace-nowrap">Input Manual</button>
                        <button type="button" data-tab="scan" id="tab-scan" class="tab-btn public-tab px-5 py-2.5 text-sm font-semibold whitespace-nowrap">Scan QR</button>
                        <button type="button" data-tab="upload" id="tab-upload" class="tab-btn public-tab px-5 py-2.5 text-sm font-semibold whitespace-nowrap">Upload Gambar</button>
                    </div>
                </div>

                <div id="content-manual" class="tab-content">
                    <div class="public-card public-form-wrap p-5 sm:p-6">
                        <div class="mb-4 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">Verifikasi dengan nomor sertifikat</h3>
                            <p class="mt-1 text-sm text-slate-500">Masukkan nomor sertifikat secara lengkap untuk memperoleh hasil verifikasi.</p>
                        </div>
                        <div class="flex flex-col items-center gap-3">
                            <input type="text" id="cert_number" class="public-input w-full rounded-xl px-4 py-3.5 text-slate-900 placeholder:text-slate-400" placeholder="Masukkan nomor sertifikat">
                            <button type="button" id="verifyManualBtn" class="public-button w-full rounded-xl px-6 py-3.5 font-semibold">Verifikasi</button>
                        </div>
                    </div>
                </div>

                <div id="content-scan" class="tab-content hidden">
                    <div class="public-card public-form-wrap p-5 text-center sm:p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-slate-900">Verifikasi melalui kamera</h3>
                            <p class="mt-1 text-sm text-slate-500">Arahkan kamera ke kode QR pada sertifikat untuk memulai pemeriksaan.</p>
                        </div>
                        <div class="space-y-4">
                            <div id="scanArea" class="public-upload mx-auto max-w-md overflow-hidden rounded-2xl border-2 border-dashed bg-slate-50/70" style="min-height:280px;">
                                <div id="scanIdle" class="flex flex-col items-center justify-center h-64 px-6 text-center">
                                    <p class="text-slate-500">Tekan tombol di bawah untuk mengaktifkan kamera.</p>
                                </div>
                                <video id="scanVideo" class="hidden w-full" autoplay playsinline muted></video>
                            </div>
                            <div class="mx-auto max-w-md text-left">
                                <label for="cameraSelect" class="mb-2 block text-sm font-semibold text-slate-700">Pilih Kamera</label>
                                <select id="cameraSelect" class="public-input w-full rounded-xl px-4 py-3 text-sm text-slate-700">
                                    <option value="">Kamera otomatis</option>
                                </select>
                                <p id="cameraHint" class="mt-2 text-xs text-slate-400">Hubungkan kamera eksternal, izinkan akses kamera, lalu pilih perangkat yang tersedia.</p>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-3">
                                <button type="button" id="btnStartScan" class="public-button rounded-xl px-6 py-3 font-semibold">Mulai Scan Kamera</button>
                                <button type="button" id="btnFocusCamera" class="public-button-secondary hidden rounded-xl px-6 py-3 font-semibold">Fokus</button>
                                <button type="button" id="btnStopScan" class="public-button-secondary hidden rounded-xl px-6 py-3 font-semibold">Hentikan</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="content-upload" class="tab-content hidden">
                    <div class="public-card public-form-wrap p-5 sm:p-6">
                        <div class="mb-4 text-center">
                            <h3 class="text-lg font-semibold text-slate-900">Verifikasi melalui unggah gambar</h3>
                            <p class="mt-1 text-sm text-slate-500">Unggah gambar yang memuat kode QR untuk diproses secara otomatis.</p>
                        </div>
                        <input type="file" id="fileInput" accept="image/*" class="hidden">
                        <div id="uploadZone" class="public-upload relative flex min-h-52 w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-slate-50/70 transition-all hover:border-blue-300 hover:bg-blue-50/60">
                            <div id="uploadIdle" class="text-center px-6 py-8">
                                <p class="text-slate-700 font-medium mb-1">Seret file ke sini atau klik untuk memilih gambar kode QR</p>
                                <p class="text-slate-400 text-sm">Gambar juga dapat ditempel langsung dengan <kbd class="rounded bg-slate-200 px-2 py-0.5 text-xs font-mono">Ctrl+V</kbd></p>
                            </div>
                            <div id="uploadPreview" class="hidden w-full p-4">
                                <div class="flex items-center space-x-4">
                                    <img id="previewImg" src="" alt="" class="h-24 w-24 rounded-xl border border-slate-200 bg-white object-contain">
                                    <div class="min-w-0 flex-1">
                                        <p id="previewName" class="truncate text-sm font-medium text-slate-700"></p>
                                        <p id="previewSize" class="mt-0.5 text-xs text-slate-400"></p>
                                        <button type="button" id="clearUploadBtn" class="mt-2 text-xs font-medium text-red-600 hover:text-red-700">Hapus</button>
                                    </div>
                                    <button type="button" id="submitUploadBtn" class="public-button flex-shrink-0 rounded-xl px-5 py-3 text-sm font-semibold">Verifikasi</button>
                                </div>
                            </div>
                            <div id="uploadOverlay" class="hidden absolute inset-0 rounded-2xl border-2 border-blue-500 bg-blue-100/80 items-center justify-center pointer-events-none">
                                <div class="text-center">
                                    <p class="font-semibold text-blue-700">Lepaskan file untuk memulai verifikasi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="result" class="public-result hidden mt-8 p-6"></div>
            </div>
        </div>

        <div id="result-outer" class="hidden mx-auto max-w-4xl mt-6 relative z-10"></div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('csp_nonce', '') }}">
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

document.querySelectorAll('[data-tab]').forEach((button) => {
    button.addEventListener('click', () => switchTab(button.dataset.tab));
});

document.getElementById('verifyManualBtn')?.addEventListener('click', () => verifyManual());
document.getElementById('btnStartScan')?.addEventListener('click', () => startScan());
document.getElementById('btnFocusCamera')?.addEventListener('click', () => focusCamera());
document.getElementById('btnStopScan')?.addEventListener('click', () => stopScan());
document.getElementById('clearUploadBtn')?.addEventListener('click', (event) => clearUpload(event));
document.getElementById('submitUploadBtn')?.addEventListener('click', (event) => submitUpload(event));

const cameraSelect = document.getElementById('cameraSelect');
const cameraHint = document.getElementById('cameraHint');

cameraSelect?.addEventListener('change', () => {
    if (scanStream) startScan();
});

if (navigator.mediaDevices?.enumerateDevices) {
    loadCameraDevices();
    navigator.mediaDevices.addEventListener?.('devicechange', () => loadCameraDevices());
}

function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('is-active');
    });
    document.getElementById('content-' + tab).classList.remove('hidden');
    const btn = document.getElementById('tab-' + tab);
    btn.classList.add('is-active');
    document.getElementById('result').classList.add('hidden');
    if (tab !== 'scan') stopScan();
}

async function verifyManual() {
    const num = document.getElementById('cert_number').value.trim();
    if (!num) return;
    showLoading();
    try {
        const res  = await fetch('{{ route("verify") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ cert_number: num })
        });
        const data = await res.json();
        data.success ? showSuccess(data.data) : showError(data.message);
    } catch (err) {
        showError('Terjadi kesalahan koneksi.');
    }
}

document.getElementById('cert_number').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') verifyManual();
});

let scanStream = null, scanInterval = null, scanCanvas = null, scanRequestBusy = false, scanDetectorBusy = false, scanFrameIndex = 0, scanSession = 0;

async function loadCameraDevices() {
    if (!navigator.mediaDevices?.enumerateDevices || !cameraSelect) return [];

    try {
        const previousValue = cameraSelect.value;
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cameras = devices.filter(device => device.kind === 'videoinput');

        cameraSelect.innerHTML = '<option value="">Kamera otomatis</option>';
        cameras.forEach((camera, index) => {
            const option = document.createElement('option');
            option.value = camera.deviceId;
            option.textContent = camera.label || `Kamera ${index + 1}`;
            cameraSelect.appendChild(option);
        });

        if (previousValue && cameras.some(camera => camera.deviceId === previousValue)) {
            cameraSelect.value = previousValue;
        }

        cameraSelect.disabled = cameras.length === 0;
        if (cameraHint) {
            cameraHint.textContent = cameras.length > 0
                ? 'Jika kamera eksternal belum muncul, cabut-pasang kamera lalu izinkan akses kamera lagi.'
                : 'Tidak ada perangkat kamera yang terdeteksi.';
        }

        return cameras;
    } catch (err) {
        if (cameraHint) cameraHint.textContent = 'Daftar kamera belum dapat dibaca. Izinkan akses kamera terlebih dahulu.';
        return [];
    }
}

function cameraVideoConstraints(extra = {}) {
    return {
        width: { ideal: 1920 },
        height: { ideal: 1080 },
        frameRate: { ideal: 30, max: 60 },
        ...extra
    };
}

async function openCameraStream() {
    if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error('MEDIA_DEVICES_UNSUPPORTED');
    }

    const selectedDeviceId = cameraSelect?.value;
    if (selectedDeviceId) {
        try {
            return await navigator.mediaDevices.getUserMedia({
                video: cameraVideoConstraints({ deviceId: { exact: selectedDeviceId } }),
                audio: false
            });
        } catch (err) {
            return navigator.mediaDevices.getUserMedia({
                video: { deviceId: { exact: selectedDeviceId } },
                audio: false
            });
        }
    }

    try {
        return await navigator.mediaDevices.getUserMedia({
            video: cameraVideoConstraints({ facingMode: { ideal: 'environment' } }),
            audio: false
        });
    } catch (err) {
        try {
            return await navigator.mediaDevices.getUserMedia({
                video: cameraVideoConstraints(),
                audio: false
            });
        } catch (fallbackErr) {
            return navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        }
    }
}

async function enhanceCameraTrack(stream) {
    const track = stream?.getVideoTracks?.()[0];
    const capabilities = track?.getCapabilities?.();
    if (!track || !capabilities) return;

    const advanced = [];
    if (Array.isArray(capabilities.focusMode) && capabilities.focusMode.includes('continuous')) {
        advanced.push({ focusMode: 'continuous' });
    }
    if (Array.isArray(capabilities.exposureMode) && capabilities.exposureMode.includes('continuous')) {
        advanced.push({ exposureMode: 'continuous' });
    }

    if (advanced.length > 0) {
        try {
            await track.applyConstraints({ advanced });
        } catch (_) {}
    }
}

function syncActiveCamera(stream) {
    const activeDeviceId = stream?.getVideoTracks?.()[0]?.getSettings?.().deviceId;
    if (activeDeviceId && cameraSelect) {
        cameraSelect.value = activeDeviceId;
    }
}

function cameraErrorMessage(error) {
    if (error?.message === 'MEDIA_DEVICES_UNSUPPORTED') {
        return 'Browser tidak mendukung akses kamera. Gunakan tab Upload Gambar.';
    }

    if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
        return 'Izin kamera ditolak. Izinkan akses kamera pada browser lalu coba lagi.';
    }

    if (error?.name === 'NotFoundError' || error?.name === 'DevicesNotFoundError') {
        return 'Kamera tidak ditemukan. Pastikan kamera eksternal sudah terhubung.';
    }

    if (error?.name === 'NotReadableError' || error?.name === 'TrackStartError') {
        return 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
    }

    if (error?.name === 'OverconstrainedError' || error?.name === 'ConstraintNotSatisfiedError') {
        return 'Kamera yang dipilih tidak tersedia. Pilih kamera lain lalu mulai scan kembali.';
    }

    return 'Tidak dapat mengakses kamera.';
}

function updateCameraHint(message) {
    if (cameraHint) cameraHint.textContent = message;
}

async function focusCamera() {
    const track = scanStream?.getVideoTracks?.()[0];
    if (!track) {
        updateCameraHint('Aktifkan kamera terlebih dahulu sebelum menggunakan fokus.');
        return;
    }

    const capabilities = track.getCapabilities?.() || {};
    const focusModes = Array.isArray(capabilities.focusMode) ? capabilities.focusMode : [];

    if (focusModes.includes('single-shot')) {
        try {
            await track.applyConstraints({ advanced: [{ focusMode: 'single-shot' }] });
            updateCameraHint('Fokus kamera dijalankan. Tahan QR tetap di tengah kamera.');

            if (focusModes.includes('continuous')) {
                setTimeout(() => {
                    track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }).catch(() => {});
                }, 900);
            }
            return;
        } catch (_) {}
    }

    if (focusModes.includes('continuous')) {
        try {
            await track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] });
            updateCameraHint('Fokus otomatis aktif. Tahan QR tetap di tengah kamera.');
            return;
        } catch (_) {}
    }

    updateCameraHint('Kamera/browser ini belum mendukung tombol fokus. Coba dekatkan atau jauhkan QR perlahan sampai tajam.');
}

function isTransientQrMessage(message) {
    return !message
        || message.includes('tidak dapat dibaca')
        || message.includes('gagal diproses');
}

function captureVideoFrame(video, mode = 'full') {
    const sourceWidth = video.videoWidth;
    const sourceHeight = video.videoHeight;
    if (!sourceWidth || !sourceHeight) return Promise.resolve(null);

    let sourceX = 0;
    let sourceY = 0;
    let captureWidth = sourceWidth;
    let captureHeight = sourceHeight;

    if (mode === 'center') {
        const cropSize = Math.round(Math.min(sourceWidth, sourceHeight) * 0.82);
        sourceX = Math.round((sourceWidth - cropSize) / 2);
        sourceY = Math.round((sourceHeight - cropSize) / 2);
        captureWidth = cropSize;
        captureHeight = cropSize;
    }

    const maxDimension = mode === 'center' ? 1150 : 1400;
    const scale = Math.min(1, maxDimension / Math.max(captureWidth, captureHeight));
    const targetWidth = Math.round(captureWidth * scale);
    const targetHeight = Math.round(captureHeight * scale);

    scanCanvas = scanCanvas || document.createElement('canvas');
    scanCanvas.width = targetWidth;
    scanCanvas.height = targetHeight;
    scanCanvas.getContext('2d').drawImage(video, sourceX, sourceY, captureWidth, captureHeight, 0, 0, targetWidth, targetHeight);

    return new Promise(resolve => scanCanvas.toBlob(resolve, 'image/jpeg', 0.95));
}

async function scanFrameWithServer(video, sessionId, endpoint) {
    if (scanRequestBusy || sessionId !== scanSession || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) return;

    scanRequestBusy = true;

    try {
        const frameMode = scanFrameIndex % 2 === 0 ? 'center' : 'full';
        scanFrameIndex += 1;

        const blob = await captureVideoFrame(video, frameMode);
        if (!blob) return;

        const formData = new FormData();
        formData.append('qr_image', blob, 'kamera.jpg');

        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            body: formData
        });

        if (sessionId !== scanSession) return;

        if (res.status === 429) {
            updateCameraHint('Pemindaian terlalu cepat. Tahan posisi QR sebentar lalu coba lagi.');
            return;
        }

        const data = await res.json();
        if (data.success) {
            stopScan();
            showSuccess(data.data);
            return;
        }

        if (isTransientQrMessage(data.message || '')) {
            updateCameraHint('Mencari QR... Pastikan QR terlihat jelas dan tidak terlalu jauh dari kamera.');
            return;
        }

        stopScan();
        showError(data.message || 'Format QR tidak valid.');
    } catch (err) {
        if (sessionId === scanSession) {
            updateCameraHint('Belum dapat membaca frame kamera. Pastikan koneksi aplikasi tetap aktif.');
        }
    } finally {
        if (sessionId === scanSession) {
            scanRequestBusy = false;
        }
    }
}

function startServerFrameScanner(video, sessionId, endpoint) {
    scanFrameIndex = 0;
    updateCameraHint('Mode kompatibel aktif. Posisikan QR di tengah kamera dan tahan sebentar.');
    scanFrameWithServer(video, sessionId, endpoint);
    scanInterval = setInterval(() => scanFrameWithServer(video, sessionId, endpoint), 1200);
}

async function startScan() {
    stopScan();
    const sessionId = scanSession;
    const frameEndpoint = '{{ route("verify.qr.frame") }}';

    try {
        scanStream = await openCameraStream();
        await enhanceCameraTrack(scanStream);
        await loadCameraDevices();
        syncActiveCamera(scanStream);

        const video = document.getElementById('scanVideo');
        video.srcObject = scanStream;
        video.classList.remove('hidden');
        await video.play();

        document.getElementById('scanIdle').classList.add('hidden');
        document.getElementById('btnStartScan').classList.add('hidden');
        document.getElementById('btnFocusCamera').classList.remove('hidden');
        document.getElementById('btnStopScan').classList.remove('hidden');

        if ('BarcodeDetector' in window) {
            try {
                const detector = new BarcodeDetector({ formats: ['qr_code'] });
                updateCameraHint('Kamera aktif. Arahkan QR ke area kamera.');
                scanInterval = setInterval(async () => {
                    if (scanDetectorBusy || sessionId !== scanSession || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) return;
                    scanDetectorBusy = true;
                    try {
                        const codes = await detector.detect(video);
                        if (codes.length > 0) { stopScan(); handleQrText(codes[0].rawValue); }
                    } catch (_) {
                        if (sessionId === scanSession) {
                            if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
                            startServerFrameScanner(video, sessionId, frameEndpoint);
                        }
                    } finally {
                        if (sessionId === scanSession) scanDetectorBusy = false;
                    }
                }, 250);
                setTimeout(() => {
                    if (sessionId !== scanSession || !scanStream) return;
                    if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
                    startServerFrameScanner(video, sessionId, frameEndpoint);
                }, 1800);
                return;
            } catch (err) {
                startServerFrameScanner(video, sessionId, frameEndpoint);
                return;
            }
        }

        startServerFrameScanner(video, sessionId, frameEndpoint);
    } catch (err) {
        stopScan();
        showError(cameraErrorMessage(err));
    }
}

function stopScan() {
    scanSession += 1;
    scanRequestBusy = false;
    scanDetectorBusy = false;
    if (scanStream) { scanStream.getTracks().forEach(t => t.stop()); scanStream = null; }
    if (scanInterval) { clearInterval(scanInterval); scanInterval = null; }
    const video = document.getElementById('scanVideo');
    if (video) { video.srcObject = null; video.classList.add('hidden'); }
    document.getElementById('scanIdle')?.classList.remove('hidden');
    document.getElementById('btnStartScan')?.classList.remove('hidden');
    document.getElementById('btnFocusCamera')?.classList.add('hidden');
    document.getElementById('btnStopScan')?.classList.add('hidden');
}

function handleQrText(text) {
    showLoading();
    const match = text.match(/nomor_sertifikat=([^&]+)/);
    if (match) {
        fetch('{{ route("verify") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ cert_number: decodeURIComponent(match[1]) })
        }).then(r => r.json()).then(data => {
            data.success ? showSuccess(data.data) : showError(data.message);
        });
    } else {
        showError('Format QR tidak valid.');
    }
}

let uploadedFile = null;
const uploadZone = document.getElementById('uploadZone');
const uploadOverlay = document.getElementById('uploadOverlay');
const fileInput = document.getElementById('fileInput');

uploadZone.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });
uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadOverlay.classList.remove('hidden'); });
uploadZone.addEventListener('dragleave', e => { if (!uploadZone.contains(e.relatedTarget)) uploadOverlay.classList.add('hidden'); });
uploadZone.addEventListener('drop', e => {
    e.preventDefault(); uploadOverlay.classList.add('hidden');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) handleFile(file);
    else showError('File harus berupa gambar.');
});
document.addEventListener('paste', e => {
    const items = e.clipboardData?.items;
    if (!items) return;
    for (const item of items) {
        if (item.type.startsWith('image/')) { switchTab('upload'); handleFile(item.getAsFile()); break; }
    }
});

function handleFile(file) {
    uploadedFile = file;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name || 'Gambar dari clipboard';
        document.getElementById('previewSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
        document.getElementById('uploadIdle').classList.add('hidden');
        document.getElementById('uploadPreview').classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}

function clearUpload(e) {
    if (e) e.stopPropagation();
    uploadedFile = null;
    fileInput.value = '';
    document.getElementById('uploadPreview').classList.add('hidden');
    document.getElementById('uploadIdle').classList.remove('hidden');
    document.getElementById('result').classList.add('hidden');
}

async function submitUpload(e) {
    if (e) e.stopPropagation();
    if (!uploadedFile) return;
    showLoading();
    const formData = new FormData();
    formData.append('qr_image', uploadedFile);
    try {
        const res = await fetch('{{ route("verify.qr") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            body: formData
        });
        const data = await res.json();
        data.success ? showSuccess(data.data) : showError(data.message);
    } catch (err) {
        showError('Terjadi kesalahan koneksi.');
    }
}

function showLoading() {
    const el = document.getElementById('result');
    el.className = 'public-result mt-8 p-6 bg-blue-50 border border-blue-200';
    el.innerHTML = '<div class="text-center"><p class="text-blue-700">Memverifikasi...</p></div>';
    el.classList.remove('hidden');
}

function escapeHtml(value) {
    return String(value ?? '-')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function certificateInfoItems(data) {
    return [
        ['Nama', data.nama, 'text'],
        ['NPM', data.npm, 'mono'],
        ['Nomor Sertifikat', data.nomor_sertifikat, 'mono'],
        ['Jenis Sertifikat', data.jenis_sertifikat, 'text'],
        ['Tanggal Terbit', data.tanggal_terbit, 'text'],
        ['Berlaku Sampai', data.masa_berlaku, 'text'],
    ];
}

function successResultFont() {
    return "'Trebuchet MS', Arial, system-ui, sans-serif";
}

function buildDetailPanel(data) {
    const shortHash = data.hash_sha256 ? `${data.hash_sha256.slice(0, 12)}...${data.hash_sha256.slice(-12)}` : null;

    const hashSection = shortHash ? `
        <div class="border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">SHA-256 Dokumen</p>
            <p class="font-mono text-sm font-semibold text-slate-800 break-all">${shortHash}</p>
            <details class="mt-2">
                <summary class="cursor-pointer text-xs font-medium text-green-700 hover:text-green-800">Lihat hash lengkap</summary>
                <p class="mt-2 break-all rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-600">${data.hash_sha256}</p>
            </details>
            <button id="copySha256Btn" data-hash="${data.hash_sha256}" class="mt-3 inline-flex items-center rounded-lg border border-green-200 bg-white px-3 py-1.5 text-xs font-medium text-green-700 transition hover:bg-green-50">Salin SHA-256</button>
        </div>` : '';

    const historyItems = Array.isArray(data.history_notes) && data.history_notes.length > 0
        ? data.history_notes.map(item => {
            let descHtml = '';
            if (item.description && !item.description.startsWith('Edit sertifikat')) {
                const parts = item.description.split(';').map(s => s.trim()).filter(Boolean);
                descHtml = `<ul class="mt-1.5 space-y-0.5">${parts.map(p => `<li class="text-xs text-slate-600">• ${p}</li>`).join('')}</ul>`;
            } else {
                descHtml = `<p class="mt-1 text-xs text-slate-500">Tidak ada perubahan data tercatat</p>`;
            }
            return `<div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                <div class="flex items-center justify-between gap-3">
                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 border-amber-200">✏ ${item.label}</span>
                    <span class="text-xs text-slate-400">${item.created_at}</span>
                </div>
                ${descHtml}
            </div>`;
        }).join('')
        : `<p class="text-xs text-slate-400">Tidak ada perubahan tercatat.</p>`;

    const docSection = data.file_path ? `
        <div class="border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Dokumen Sertifikat</p>
            <img src="/${data.file_path}" alt="Sertifikat" class="w-full rounded-xl border border-slate-200 shadow-sm">
            <a href="/${data.file_path}" target="_blank" download class="mt-3 inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">&#8595; Unduh Sertifikat</a>
        </div>` : '';

    const infoItems = [
        ['Nama', data.nama], ['NPM', data.npm],
        ['Nomor Sertifikat', data.nomor_sertifikat], ['Jenis', data.jenis_sertifikat],
        ['Tanggal Terbit', data.tanggal_terbit], ['Berlaku Sampai', data.masa_berlaku],
    ].map(([label, value]) => `
        <div class="border-b border-slate-100 pb-2.5">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">${label}</p>
            <p class="text-sm font-normal text-slate-800 mt-0.5 break-words leading-snug">${value ?? '-'}</p>
        </div>`).join('');

    return `
        <div class="public-result bg-white border border-slate-200 rounded-2xl overflow-hidden flex">
            <div class="w-44 flex-shrink-0 bg-slate-50 border-r border-slate-100 p-4 space-y-3">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Info Sertifikat</p>
                ${infoItems}
            </div>
            <div class="flex-1 min-w-0 flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-bold text-slate-900">Detail Sertifikat</h2>
                    <button id="backFromDetailBtn" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">&#8592; Kembali</button>
                </div>
                <div class="overflow-y-auto space-y-4 p-5">
                    ${docSection}
                    ${hashSection}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Riwayat Perubahan</p>
                        <div class="space-y-2">${historyItems}</div>
                    </div>
                </div>
            </div>
        </div>`;
}

function closeCertDetail() {
    document.getElementById('certDetailModal')?.remove();
}

function buildDetailPanelV2(data) {
    const shortHash = data.hash_sha256 ? `${data.hash_sha256.slice(0, 12)}...${data.hash_sha256.slice(-12)}` : null;
    const filePath = data.file_path ? `/${escapeHtml(data.file_path)}` : null;

    const infoItems = certificateInfoItems(data).map(([label, value, type]) => `
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">${escapeHtml(label)}</p>
            <p class="mt-2 break-words text-[15px] font-normal leading-6 text-slate-800 ${type === 'mono' ? 'font-mono' : ''}">${escapeHtml(value)}</p>
        </div>`).join('');

    const docSection = filePath ? `
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Dokumen Sertifikat</p>
                    <p class="mt-1 text-sm text-slate-500">Pratinjau dokumen yang berhasil diverifikasi.</p>
                </div>
                <a href="${filePath}" target="_blank" download class="inline-flex items-center justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-green-700">Unduh Sertifikat</a>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                <img src="${filePath}" alt="Sertifikat" class="max-h-[620px] w-full object-contain">
            </div>
        </section>` : `
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm font-semibold text-amber-800">Dokumen sertifikat belum tersedia untuk pratinjau.</p>
        </section>`;

    const hashSection = shortHash ? `
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">SHA-256 Dokumen</p>
                    <p class="mt-2 break-all font-mono text-sm font-bold text-slate-900">${escapeHtml(shortHash)}</p>
                </div>
                <button id="copySha256Btn" data-hash="${escapeHtml(data.hash_sha256)}" class="inline-flex items-center justify-center rounded-xl border border-green-200 bg-green-50 px-4 py-2 text-sm font-semibold text-green-700 transition hover:bg-green-100">Salin SHA-256</button>
            </div>
            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-semibold text-green-700 hover:text-green-800">Lihat hash lengkap</summary>
                <p class="mt-3 break-all rounded-xl border border-slate-200 bg-slate-50 p-4 font-mono text-xs leading-6 text-slate-700">${escapeHtml(data.hash_sha256)}</p>
            </details>
        </section>` : '';

    const historyItems = Array.isArray(data.history_notes) && data.history_notes.length > 0
        ? data.history_notes.map(item => {
            let descHtml = '';
            if (item.description && !item.description.startsWith('Edit sertifikat')) {
                const parts = item.description.split(';').map(s => s.trim()).filter(Boolean);
                descHtml = `<ul class="mt-3 space-y-1.5">${parts.map(part => `<li class="text-sm text-slate-600">- ${escapeHtml(part)}</li>`).join('')}</ul>`;
            } else {
                descHtml = `<p class="mt-2 text-sm text-slate-500">Tidak ada perubahan data tercatat</p>`;
            }

            return `<div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">${escapeHtml(item.label || 'Perubahan')}</span>
                    <span class="text-xs font-medium text-slate-400">${escapeHtml(item.created_at || '-')}</span>
                </div>
                ${descHtml}
            </div>`;
        }).join('')
        : `<p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">Tidak ada perubahan tercatat.</p>`;

    return `
        <div class="public-result overflow-hidden rounded-3xl border border-green-200 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.10)]">
            <div class="border-b border-green-100 bg-white px-5 py-5 sm:px-7">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <span class="inline-flex items-center rounded-full border border-green-200 bg-green-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-green-700">Sertifikat Valid</span>
                        <h2 class="mt-3 text-2xl font-bold text-slate-950">Detail Sertifikat</h2>
                        <p class="mt-1 text-sm text-slate-600">Data berikut cocok dengan catatan verifikasi sistem.</p>
                    </div>
                    <button id="backFromDetailBtn" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Kembali</button>
                </div>
            </div>

            <div class="space-y-5 bg-slate-50/70 p-5 sm:p-7">
                <section class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Info Sertifikat</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">Ringkasan Data</h3>
                        </div>
                        <p class="rounded-full border border-green-200 bg-green-50 px-3 py-1 text-sm font-medium text-green-700">Nomor: <span class="font-mono font-normal">${escapeHtml(data.nomor_sertifikat)}</span></p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">${infoItems}</div>
                </section>
                ${docSection}
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                    ${hashSection}
                    <section class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Riwayat Perubahan</p>
                        <div class="mt-3 space-y-3">${historyItems}</div>
                    </section>
                </div>
            </div>
        </div>`;
}

function openCertDetail(data) {
    const outer = document.getElementById('result-outer');
    outer.className = 'mx-auto max-w-6xl mt-6 relative z-10';
    outer.style.fontFamily = successResultFont();
    outer.innerHTML = buildDetailPanelV2(data);
    outer.querySelector('#backFromDetailBtn').addEventListener('click', () => showSuccessViewV2(data));
    const copyBtn = outer.querySelector('#copySha256Btn');
    if (copyBtn) copyBtn.addEventListener('click', () => copyHash(copyBtn));
}

async function copyHash(button) {
    const hash = button?.dataset?.hash;
    if (!hash) return;
    const originalText = button.textContent;
    try {
        await navigator.clipboard.writeText(hash);
        button.textContent = 'Tersalin';
        setTimeout(() => { button.textContent = originalText; }, 1800);
    } catch (_) {
        button.textContent = 'Gagal salin';
        setTimeout(() => { button.textContent = originalText; }, 1800);
    }
}

function showSuccessView(data) {
    const outer = document.getElementById('result-outer');
    outer.className = 'public-result mx-auto max-w-4xl mt-6 p-6 bg-green-50 border border-green-200';
    outer.innerHTML = `
        <div class="mb-5">
            <h3 class="text-xl font-bold text-green-900">Sertifikat Valid</h3>
            <p class="text-sm text-green-700 mt-1">Data sertifikat berhasil ditemukan.</p>
        </div>
        <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm text-gray-700 mb-5">
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Nama</span><p class="font-normal mt-0.5">${data.nama}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">NPM</span><p class="font-normal mt-0.5">${data.npm}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Nomor Sertifikat</span><p class="font-normal font-mono mt-0.5">${data.nomor_sertifikat}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Jenis</span><p class="font-normal mt-0.5">${data.jenis_sertifikat}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Tanggal Terbit</span><p class="font-normal mt-0.5">${data.tanggal_terbit}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Berlaku Sampai</span><p class="font-normal mt-0.5">${data.masa_berlaku}</p></div>
        </div>
        <div class="border-t border-green-200 pt-4 flex items-center justify-between">
            <button id="resetBtn" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">&#8592; Verifikasi Lagi</button>
            <button id="openDetailBtn" class="inline-flex items-center gap-2 rounded-xl border border-green-300 bg-white px-4 py-2 text-sm font-semibold text-green-700 transition hover:bg-green-50">&#9776; Lihat Detail</button>
        </div>
    `;
    outer.classList.remove('hidden');
    outer.querySelector('#openDetailBtn').addEventListener('click', () => openCertDetail(data));
    outer.querySelector('#resetBtn').addEventListener('click', resetVerification);
}

function showSuccessViewV2(data) {
    const outer = document.getElementById('result-outer');
    outer.style.fontFamily = successResultFont();
    const infoItems = certificateInfoItems(data).slice(0, 4).map(([label, value, type]) => `
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">${escapeHtml(label)}</p>
            <p class="mt-2 break-words text-[15px] font-normal leading-6 text-slate-800 ${type === 'mono' ? 'font-mono' : ''}">${escapeHtml(value)}</p>
        </div>`).join('');

    outer.className = 'public-result mx-auto max-w-4xl mt-6 overflow-hidden rounded-3xl border border-green-200 bg-white shadow-[0_24px_70px_rgba(22,101,52,0.12)]';
    outer.innerHTML = `
        <div class="border-b border-green-200 bg-white px-6 py-5 text-slate-950 shadow-[inset_0_0_0_1px_rgba(34,197,94,0.16)]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="inline-flex rounded-full border border-green-300 bg-green-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-green-800">Status Verifikasi</p>
                    <h3 class="mt-3 text-2xl font-bold leading-tight text-slate-950">Sertifikat Valid</h3>
                    <p class="mt-1 max-w-2xl text-sm font-normal leading-6 text-slate-700">Data sertifikat berhasil ditemukan dan dokumen lolos pemeriksaan integritas.</p>
                </div>
                <span class="inline-flex items-center justify-center rounded-2xl border border-green-300 bg-green-100 px-4 py-2 text-sm font-bold text-green-800">VALID</span>
            </div>
        </div>
        <div class="bg-slate-50/70 p-6">
            <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">${infoItems}</div>
            <div class="flex flex-col gap-3 border-t border-green-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <button id="resetBtn" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Verifikasi Lagi</button>
                <button id="openDetailBtn" class="inline-flex items-center justify-center rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700">Lihat Detail Sertifikat</button>
            </div>
        </div>
    `;
    outer.classList.remove('hidden');
    outer.querySelector('#openDetailBtn').addEventListener('click', () => openCertDetail(data));
    outer.querySelector('#resetBtn').addEventListener('click', resetVerification);
}

function showSuccess(data) {
    window._certDetailData = data;
    document.querySelector('.public-shell').classList.add('hidden');
    showSuccessViewV2(data);
}

function resetVerification() {
    stopScan();
    document.querySelector('.public-shell').classList.remove('hidden');
    const outer = document.getElementById('result-outer');
    outer.classList.add('hidden');
    outer.innerHTML = '';
    document.getElementById('cert_number').value = '';
    clearUpload(null);
}

function showError(message) {
    const el = document.getElementById('result');
    el.className = 'public-result mt-8 p-6 bg-red-50 border border-red-200';
    el.innerHTML = `
        <div>
            <h3 class="text-xl font-bold text-red-900 mb-1">Verifikasi Gagal</h3>
            <p class="text-red-700 text-sm">${message}</p>
        </div>`;
    el.classList.remove('hidden');
}
</script>
@endsection
