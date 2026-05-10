@extends('layouts.app')
@section('title', 'Verifikasi Sertifikat')

@section('content')
<style nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    .admin-tool-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-tool-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.16;
        background-image:
            linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
        background-size: 74px 74px;
        mask-image: radial-gradient(circle at center, rgba(15, 23, 42, 0.9), transparent 82%);
    }

    .admin-tool-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }
</style>

<div class="admin-tool-shell min-h-screen">
    @include('layouts.navbar')

    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="admin-tool-card rounded-3xl p-8">
            <div class="mx-auto mb-8 max-w-4xl">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Verifikasi Sertifikat</h1>
                        <p class="mt-2 text-sm leading-7 text-gray-500 sm:text-base">Periksa keaslian sertifikat melalui nomor, kamera, atau unggah gambar kode QR.</p>
                    </div>
                    <a href="{{ route('admin.dashboard') }}" class="shrink-0 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">Kembali</a>
                </div>
            </div>

            <div class="mx-auto mb-8 flex max-w-4xl flex-wrap justify-center gap-2 overflow-x-auto border-b border-slate-200 pb-5">
                <button type="button" data-tab="manual" id="tab-manual" class="tab-btn rounded-full bg-blue-600 px-5 py-2.5 font-semibold text-white shadow-sm shadow-blue-200 whitespace-nowrap">Input Manual</button>
                <button type="button" data-tab="scan" id="tab-scan" class="tab-btn rounded-full border border-blue-100 bg-blue-50 px-5 py-2.5 font-semibold text-blue-700 whitespace-nowrap">Scan QR</button>
                <button type="button" data-tab="upload" id="tab-upload" class="tab-btn rounded-full border border-blue-100 bg-blue-50 px-5 py-2.5 font-semibold text-blue-700 whitespace-nowrap">Upload Gambar</button>
            </div>

            <div id="content-manual" class="tab-content">
                <div class="mx-auto max-w-4xl">
                    <div class="flex flex-col gap-4">
                        <input type="text" id="cert_number" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500" placeholder="Masukkan nomor sertifikat...">
                        <button type="button" id="verifyManualBtn" class="w-full rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700">Cari</button>
                    </div>
                </div>
            </div>

            <div id="content-scan" class="tab-content hidden">
                <div class="text-center space-y-4">
                    <div id="scanArea" class="mx-auto max-w-md border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50" style="min-height:280px;">
                        <div id="scanIdle" class="flex flex-col items-center justify-center h-64 px-6 text-center">
                            <p class="text-gray-500">Klik tombol di bawah untuk mulai scan.</p>
                        </div>
                        <video id="scanVideo" class="hidden w-full" autoplay playsinline muted></video>
                    </div>
                    <div class="mx-auto max-w-md text-left">
                        <label for="cameraSelect" class="mb-2 block text-sm font-semibold text-slate-700">Pilih Kamera</label>
                        <select id="cameraSelect" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:ring-2 focus:ring-blue-500">
                            <option value="">Kamera otomatis</option>
                        </select>
                        <p id="cameraHint" class="mt-2 text-xs text-slate-400">Hubungkan kamera eksternal, izinkan akses kamera, lalu pilih perangkat yang tersedia.</p>
                    </div>
                    <button type="button" id="btnStartScan" class="rounded-xl bg-blue-600 px-6 py-2.5 font-semibold text-white hover:bg-blue-700">Mulai Scan Kamera</button>
                    <button type="button" id="btnFocusCamera" class="hidden rounded-xl bg-blue-50 px-6 py-2.5 font-semibold text-blue-700 ring-1 ring-blue-100 hover:bg-blue-100">Fokus</button>
                    <button type="button" id="btnStopScan" class="hidden rounded-xl bg-red-500 px-6 py-2.5 font-semibold text-white hover:bg-red-600">Stop</button>
                </div>
            </div>

            <div id="content-upload" class="tab-content hidden">
                <input type="file" id="fileInput" accept="image/*" style="display:none">
                <div id="uploadZone" class="relative flex min-h-48 w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 transition-all hover:border-blue-400 hover:bg-blue-50">
                    <div id="uploadIdle" class="text-center px-6 py-8">
                        <p class="text-gray-600 font-medium mb-1">Drag and drop atau klik untuk pilih gambar QR</p>
                        <p class="text-gray-400 text-sm">atau tekan <kbd class="px-2 py-0.5 bg-gray-200 rounded text-xs font-mono">Ctrl+V</kbd> untuk paste</p>
                    </div>
                    <div id="uploadPreview" class="hidden w-full p-4">
                        <div class="flex items-center space-x-4">
                            <img id="previewImg" src="" alt="" class="w-24 h-24 object-contain rounded-lg border border-gray-200 bg-white">
                            <div class="flex-1 min-w-0">
                                <p id="previewName" class="text-sm font-medium text-gray-700 truncate"></p>
                                <p id="previewSize" class="text-xs text-gray-400 mt-0.5"></p>
                                <button type="button" id="clearUploadBtn" class="mt-2 text-xs text-red-500 hover:text-red-700">Hapus</button>
                            </div>
                                <button type="button" id="submitUploadBtn" class="flex-shrink-0 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Verifikasi</button>
                        </div>
                    </div>
                    <div id="uploadOverlay" class="hidden absolute inset-0 bg-blue-100/80 border-2 border-blue-500 rounded-xl flex items-center justify-center pointer-events-none">
                        <div class="text-center">
                            <p class="text-blue-700 font-semibold mt-2">Lepaskan untuk verifikasi</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="result" class="hidden mt-8 p-6 rounded-xl"></div>
        </div>
    </div>
</div>

<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
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
        el.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600');
        el.classList.add('text-gray-500');
    });
    document.getElementById('content-' + tab).classList.remove('hidden');
    const btn = document.getElementById('tab-' + tab);
    btn.classList.add('border-b-2', 'border-blue-600', 'text-blue-600');
    btn.classList.remove('text-gray-500');
    document.getElementById('result').classList.add('hidden');
    if (tab !== 'scan') stopScan();
}

async function verifyManual() {
    const num = document.getElementById('cert_number').value.trim();
    if (!num) return;
    showLoading();
    try {
        const res  = await fetch('{{ route("admin.verify.search") }}', {
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
    const frameEndpoint = '{{ route("admin.verify.qr") }}';

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
    switchTab('scan');
    showLoading();
    const match = text.match(/nomor_sertifikat=([^&]+)/);
    if (match) {
        fetch('{{ route("admin.verify.search") }}', {
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
    uploadedFile = null; fileInput.value = '';
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
        const res = await fetch('{{ route("admin.verify.qr") }}', {
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
    el.className = 'mt-8 p-6 rounded-xl bg-blue-50 border border-blue-200';
    el.innerHTML = '<div class="text-center"><p class="text-blue-700">Memverifikasi...</p></div>';
    el.classList.remove('hidden');
}

function buildHashBlock(hash) {
    if (!hash) return '';

    const shortHash = `${hash.slice(0, 12)}...${hash.slice(-12)}`;

    return `
        <div class="mb-5 rounded-2xl border border-green-200 bg-white p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">SHA-256 Dokumen</p>
                    <p class="mt-1 break-all font-mono text-sm font-semibold text-gray-800">${shortHash}</p>
                    <details class="mt-3">
                        <summary class="cursor-pointer text-sm font-medium text-green-700 hover:text-green-800">Lihat hash lengkap</summary>
                        <p class="mt-2 break-all rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-700">${hash}</p>
                    </details>
                </div>
                <button type="button" data-hash="${hash}" class="js-copy-hash inline-flex items-center justify-center rounded-lg border border-green-200 bg-white px-3 py-2 text-sm font-medium text-green-700 transition hover:bg-green-50">
                    Salin SHA-256
                </button>
            </div>
        </div>
    `;
}

async function copyHash(button) {
    const hash = button?.dataset?.hash;
    if (!hash) return;

    const originalText = button.textContent;

    try {
        await navigator.clipboard.writeText(hash);
        button.textContent = 'Tersalin';
        setTimeout(() => {
            button.textContent = originalText;
        }, 1800);
    } catch (_) {
        button.textContent = 'Gagal salin';
        setTimeout(() => {
            button.textContent = originalText;
        }, 1800);
    }
}

function buildHistoryBlock(history) {
    if (!Array.isArray(history) || history.length === 0) return '';

    const items = history.map(item => `
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-slate-800">${item.label}</p>
                <span class="text-xs text-slate-500">${item.created_at}</span>
            </div>
            <p class="mt-1 text-xs text-slate-600">${item.description}</p>
            <p class="mt-1 text-[11px] uppercase tracking-wide text-slate-400">Oleh ${item.actor}</p>
        </div>
    `).join('');

    return `
        <details class="mb-5">
            <summary class="inline-flex cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 transition hover:bg-slate-50">
                Catatan
            </summary>
            <div class="mt-3 space-y-2">${items}</div>
        </details>
    `;
}

function showSuccess(data) {
    const editUrl = '{{ url("/admin/certificates") }}/' + data.id + '/edit';
    const deleteUrl = '{{ url("/admin/certificates") }}/' + data.id;
    const csrfToken = CSRF;

    let actionButtons = '';
    if (data.can_edit) {
        actionButtons += `<a href="${editUrl}" class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-sm font-medium">Edit Sertifikat</a>`;
    }
    if (data.can_delete) {
        actionButtons += `
            <form method="POST" action="${deleteUrl}" class="js-delete-certificate inline">
                <input type="hidden" name="_token" value="${csrfToken}">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">Hapus</button>
            </form>`;
    }

    const el = document.getElementById('result');
    el.className = 'mt-8 p-6 rounded-xl bg-green-50 border border-green-200';
    el.innerHTML = `
        <div class="mb-5">
            <h3 class="text-xl font-bold text-green-900">Sertifikat Valid</h3>
            <p class="text-sm text-green-700 mt-1">Data sertifikat berhasil ditemukan.</p>
        </div>
        <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm text-gray-700 mb-5">
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Nama</span><p class="font-semibold mt-0.5">${data.nama}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">NPM</span><p class="font-semibold mt-0.5">${data.npm}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Nomor Sertifikat</span><p class="font-semibold font-mono mt-0.5">${data.nomor_sertifikat}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Jenis</span><p class="font-semibold mt-0.5">${data.jenis_sertifikat}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Tanggal Terbit</span><p class="font-semibold mt-0.5">${data.tanggal_terbit}</p></div>
            <div><span class="text-gray-500 text-xs uppercase tracking-wide">Berlaku Sampai</span><p class="font-semibold mt-0.5">${data.masa_berlaku}</p></div>
        </div>
        ${buildHashBlock(data.hash_sha256)}
        ${buildHistoryBlock(data.history_notes)}
        ${actionButtons ? `<div class="flex flex-wrap gap-3 mb-5">${actionButtons}</div>` : ''}
        ${data.file_path ? `
        <div class="border-t border-green-200 pt-4">
            <p class="text-xs text-gray-500 mb-2 font-medium uppercase tracking-wide">Dokumen Sertifikat</p>
            <img src="/${data.file_path}" alt="Sertifikat" class="w-full rounded-xl border border-green-200 shadow-md">
        </div>` : ''}
    `;
    el.classList.remove('hidden');
    el.querySelectorAll('.js-copy-hash').forEach((button) => {
        button.addEventListener('click', () => copyHash(button));
    });
    el.querySelectorAll('.js-delete-certificate').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!confirm('Hapus sertifikat ini?')) {
                event.preventDefault();
            }
        });
    });
}

function showError(message) {
    const el = document.getElementById('result');
    el.className = 'mt-8 p-6 rounded-xl bg-red-50 border border-red-200';
    el.innerHTML = `<div><h3 class="text-xl font-bold text-red-900 mb-1">Verifikasi Gagal</h3><p class="text-red-700 text-sm">${message}</p></div>`;
    el.classList.remove('hidden');
}
</script>
@endsection
