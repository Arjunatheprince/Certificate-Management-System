@extends('layouts.app')
@section('title', 'Edit Sertifikat')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <form method="POST" action="{{ route('admin.certificates.update', $certificate) }}" enctype="multipart/form-data" id="editForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="qr_x"    id="inputQrX"    value="{{ $certificate->qr_x ?? 50 }}">
            <input type="hidden" name="qr_y"    id="inputQrY"    value="{{ $certificate->qr_y ?? 50 }}">
            <input type="hidden" name="qr_size" id="inputQrSize" value="{{ $certificate->qr_size ?? 150 }}">

            <div class="grid lg:grid-cols-2 gap-6">
                <!-- Kolom Kiri -->
                <div class="space-y-6">
                    <!-- Data Mahasiswa -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="bi bi-person-vcard text-blue-600 mr-2"></i>Data Mahasiswa
                        </h2>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama" value="{{ old('nama', $certificate->nama) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NPM</label>
                                <input type="text" name="npm" value="{{ old('npm', $certificate->npm) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Sertifikat</label>
                                <input type="text" name="nomor_sertifikat" value="{{ old('nomor_sertifikat', $certificate->nomor_sertifikat) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Sertifikat</label>
                                <select id="jenisPilihan" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Pilih Jenis</option>
                                    @foreach(['Asisten Dosen','Asisten Praktikum','Pelatihan','Seminar'] as $jenis)
                                    <option value="{{ $jenis }}" {{ old('jenis_sertifikat', $certificate->jenis_sertifikat) == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                                    @endforeach
                                    <option value="__manual__">Ketik Manual</option>
                                </select>
                                <input type="text" name="jenis_sertifikat" id="jenisSertifikat"
                                       value="{{ old('jenis_sertifikat', $certificate->jenis_sertifikat) }}"
                                       placeholder="Ketik jenis sertifikat"
                                       class="w-full mt-2 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 {{ in_array(old('jenis_sertifikat', $certificate->jenis_sertifikat), ['Asisten Dosen','Asisten Praktikum','Pelatihan','Seminar']) ? 'hidden' : '' }}"
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Sampai</label>
                                <input type="date" name="valid_until" value="{{ old('valid_until', optional($certificate->valid_until)->format('Y-m-d')) }}"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-400">Kosongkan jika masa berlaku tidak ditentukan.</p>
                            </div>
                        </div>
                    </div>

                    <!-- File Sertifikat -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="bi bi-file-earmark-image text-blue-600 mr-2"></i>File Sertifikat
                        </h2>
                        <div class="mb-3 flex items-center space-x-3">
                            <a href="/{{ $certificate->file_path }}" target="_blank"
                               class="flex items-center space-x-1 text-sm text-blue-600 hover:underline">
                                <i class="bi bi-eye"></i><span>Lihat file saat ini</span>
                            </a>
                            <span class="text-xs text-gray-400 font-mono">{{ basename($certificate->file_path) }}</span>
                        </div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ganti File (opsional)</label>
                        <input type="file" name="certificate_file" id="certFileInput" accept="image/jpeg,image/png"
                               class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg px-3 py-2 cursor-pointer bg-white">
                        <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ingin mengganti. JPG, PNG · Maks 5MB</p>
                    </div>

                    <!-- Opsi QR (hanya muncul jika ada file baru) -->
                    <div id="qrOptionsBox" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hidden">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="bi bi-qr-code text-blue-600 mr-2"></i>Posisi QR Code
                        </h2>
                        <label class="flex items-center space-x-3 cursor-pointer mb-4">
                            <input type="checkbox" name="attach_qr" id="attachQr" value="true"
                                   class="w-5 h-5 text-blue-600 rounded" {{ $certificate->qr_attached ? 'checked' : '' }}>
                            <span class="text-gray-700 font-medium">Tempel QR Code di sertifikat</span>
                        </label>
                        <div id="qrControls" class="{{ $certificate->qr_attached ? '' : 'hidden' }} space-y-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="text-sm font-medium text-gray-700">Ukuran QR</label>
                                    <span class="text-sm font-bold text-blue-600" id="qrSizeLabel">{{ $certificate->qr_size ?? 150 }} px</span>
                                </div>
                                <input type="range" id="qrSizeRange" min="150" max="400" value="{{ max(150, $certificate->qr_size ?? 150) }}"
                                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi X (px)</label>
                                    <input type="number" id="qrXInput" value="{{ $certificate->qr_x ?? 50 }}" min="0"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi Y (px)</label>
                                    <input type="number" id="qrYInput" value="{{ $certificate->qr_y ?? 50 }}" min="0"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <p class="text-xs text-blue-500 font-medium"><i class="bi bi-hand-index mr-1"></i>Geser QR di preview untuk atur posisi</p>
                        </div>
                    </div>

                    <!-- Tombol -->
                    <div class="flex space-x-4">
                        <button type="button" id="openWarnModalBtn"
                                class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg">
                            <i class="bi bi-check-lg mr-2"></i>Simpan Perubahan
                        </button>
                        <a href="{{ route('admin.certificates') }}" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                            Batal
                        </a>
                    </div>
                </div>

                <!-- Kolom Kanan: Preview -->
                <div class="space-y-4">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 sticky top-20">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="bi bi-eye text-blue-600 mr-2"></i>Preview
                        </h2>

                        <!-- Placeholder -->
                        <div id="previewEmpty" class="flex flex-col items-center justify-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                            <i class="bi bi-image text-5xl text-gray-300 mb-2"></i>
                            <p class="text-sm text-gray-400">Pilih file baru untuk melihat preview</p>
                        </div>

                        <!-- Preview container -->
                        <div id="previewContainer" class="hidden relative rounded-lg overflow-hidden bg-gray-100" style="touch-action:none; user-select:none;">
                            <img id="certPreview" src="" alt="Preview" class="w-full h-auto block" style="pointer-events:none;">
                            <div id="qrOverlay" class="absolute hidden" style="left:50px;top:50px;width:150px;height:150px;cursor:grab;z-index:10;">
                                <div style="width:100%;height:100%;background:white;border:3px solid #3b82f6;border-radius:8px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.3);">
                                    <i class="bi bi-qr-code" id="qrIcon" style="font-size:80px;color:#1f2937;"></i>
                                </div>
                                <div style="position:absolute;top:-22px;left:0;right:0;text-align:center;">
                                    <span style="background:#3b82f6;color:white;font-size:11px;padding:2px 8px;border-radius:4px;">Geser QR</span>
                                </div>
                            </div>
                        </div>

                        <!-- Info koordinat -->
                        <div id="coordInfo" class="hidden mt-3 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-700">
                                <i class="bi bi-pin-map mr-1"></i>
                                Posisi pada gambar asli:
                                <span class="font-bold">X: <span id="realX">50</span>px, Y: <span id="realY">50</span>px</span>
                                · Ukuran: <span class="font-bold"><span id="realSize">150</span>px</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Modal Peringatan -->
        <div id="warnModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                <div class="flex items-start space-x-4 mb-5">
                    <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-shield-exclamation text-2xl text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Konfirmasi Perubahan Sertifikat</h3>
                        <p class="text-sm text-gray-500">Setiap perubahan akan memperbarui keamanan dokumen secara otomatis.</p>
                    </div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-5 space-y-2">
                    <p class="text-xs font-semibold text-yellow-800 uppercase tracking-wide mb-2">Dampak perubahan ini:</p>
                    <div class="flex items-start space-x-2 text-sm text-yellow-800">
                        <i class="bi bi-arrow-repeat text-yellow-600 mt-0.5 flex-shrink-0"></i>
                        <span><strong>QR Code akan di-regenerate</strong> — QR lama tidak berlaku lagi</span>
                    </div>
                    <div class="flex items-start space-x-2 text-sm text-yellow-800">
                        <i class="bi bi-shield-lock text-yellow-600 mt-0.5 flex-shrink-0"></i>
                        <span><strong>Hash SHA-256 & digital signature</strong> akan dihitung ulang</span>
                    </div>
                    <div class="flex items-start space-x-2 text-sm text-yellow-800">
                        <i class="bi bi-exclamation-triangle text-yellow-600 mt-0.5 flex-shrink-0"></i>
                        <span>Sertifikat yang sudah dibagikan perlu diperbarui</span>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="closeWarnModalBtn"
                            class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition text-sm">
                        <i class="bi bi-arrow-left mr-1"></i>Batal
                    </button>
                    <button type="button" id="confirmBtn" disabled
                            class="flex-1 px-4 py-2.5 bg-gray-300 text-gray-500 rounded-lg font-semibold transition text-sm cursor-not-allowed">
                        <i class="bi bi-hourglass-split mr-1"></i>Simpan (<span id="countdown">3</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
document.getElementById('openWarnModalBtn')?.addEventListener('click', () => openWarnModal());
document.getElementById('closeWarnModalBtn')?.addEventListener('click', () => closeWarnModal());
document.getElementById('confirmBtn')?.addEventListener('click', () => submitEdit());
// ── Preview & QR overlay ──────────────────────────────────────
const certFileInput    = document.getElementById('certFileInput');
const certPreview      = document.getElementById('certPreview');
const previewEmpty     = document.getElementById('previewEmpty');
const previewContainer = document.getElementById('previewContainer');
const qrOverlay        = document.getElementById('qrOverlay');
const qrIcon           = document.getElementById('qrIcon');
const attachQr         = document.getElementById('attachQr');
const qrControls       = document.getElementById('qrControls');
const qrOptionsBox     = document.getElementById('qrOptionsBox');
const qrSizeRange      = document.getElementById('qrSizeRange');
const qrSizeLabel      = document.getElementById('qrSizeLabel');
const qrXInput         = document.getElementById('qrXInput');
const qrYInput         = document.getElementById('qrYInput');
const coordInfo        = document.getElementById('coordInfo');
const jenisPilihan     = document.getElementById('jenisPilihan');
const jenisSertifikat  = document.getElementById('jenisSertifikat');

let origW = 0;

certFileInput.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = new Image();
        img.onload = () => {
            origW = img.naturalWidth;
            certPreview.src = e.target.result;
            previewEmpty.classList.add('hidden');
            previewContainer.classList.remove('hidden');
            qrOptionsBox.classList.remove('hidden');
            if (attachQr.checked) {
                qrOverlay.classList.remove('hidden');
                coordInfo.classList.remove('hidden');
            }
            updateOverlay();
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

attachQr.addEventListener('change', function () {
    qrControls.classList.toggle('hidden', !this.checked);
    if (this.checked && origW) {
        qrOverlay.classList.remove('hidden');
        coordInfo.classList.remove('hidden');
        updateOverlay();
    } else {
        qrOverlay.classList.add('hidden');
        coordInfo.classList.add('hidden');
    }
});

qrSizeRange.addEventListener('input', function () {
    qrSizeLabel.textContent = this.value + ' px';
    document.getElementById('inputQrSize').value = this.value;
    updateOverlay(); syncCoords();
});
qrXInput.addEventListener('input', () => { document.getElementById('inputQrX').value = qrXInput.value; updateOverlay(); syncCoords(); });
qrYInput.addEventListener('input', () => { document.getElementById('inputQrY').value = qrYInput.value; updateOverlay(); syncCoords(); });

function getScale() {
    return origW ? previewContainer.offsetWidth / origW : 1;
}

function updateOverlay() {
    const scale    = getScale();
    const dispSize = parseInt(qrSizeRange.value) * scale;
    qrOverlay.style.width  = dispSize + 'px';
    qrOverlay.style.height = dispSize + 'px';
    qrOverlay.style.left   = ((parseInt(qrXInput.value) || 0) * scale) + 'px';
    qrOverlay.style.top    = ((parseInt(qrYInput.value) || 0) * scale) + 'px';
    qrIcon.style.fontSize  = Math.max(16, dispSize * 0.65) + 'px';
}

function syncCoords() {
    document.getElementById('realX').textContent    = qrXInput.value;
    document.getElementById('realY').textContent    = qrYInput.value;
    document.getElementById('realSize').textContent = qrSizeRange.value;
}

// Drag QR overlay
let dragging = false, startMouseX, startMouseY, startLeft, startTop;

qrOverlay.addEventListener('mousedown', function (e) {
    dragging = true;
    startMouseX = e.clientX; startMouseY = e.clientY;
    startLeft   = parseInt(qrOverlay.style.left) || 0;
    startTop    = parseInt(qrOverlay.style.top)  || 0;
    qrOverlay.style.cursor = 'grabbing';
    e.preventDefault();
});
document.addEventListener('mousemove', function (e) {
    if (!dragging) return;
    moveQr(e.clientX, e.clientY);
});
document.addEventListener('mouseup', () => {
    if (dragging) { dragging = false; qrOverlay.style.cursor = 'grab'; }
});
qrOverlay.addEventListener('touchstart', function (e) {
    dragging = true;
    startMouseX = e.touches[0].clientX; startMouseY = e.touches[0].clientY;
    startLeft   = parseInt(qrOverlay.style.left) || 0;
    startTop    = parseInt(qrOverlay.style.top)  || 0;
    e.preventDefault();
}, { passive: false });
document.addEventListener('touchmove', function (e) {
    if (!dragging) return;
    moveQr(e.touches[0].clientX, e.touches[0].clientY);
    e.preventDefault();
}, { passive: false });
document.addEventListener('touchend', () => { dragging = false; });

function moveQr(clientX, clientY) {
    const scale   = getScale();
    const newLeft = Math.max(0, Math.min(startLeft + (clientX - startMouseX), previewContainer.offsetWidth  - qrOverlay.offsetWidth));
    const newTop  = Math.max(0, Math.min(startTop  + (clientY - startMouseY), previewContainer.offsetHeight - qrOverlay.offsetHeight));
    qrOverlay.style.left = newLeft + 'px';
    qrOverlay.style.top  = newTop  + 'px';
    qrXInput.value = Math.round(newLeft / scale);
    qrYInput.value = Math.round(newTop  / scale);
    document.getElementById('inputQrX').value = qrXInput.value;
    document.getElementById('inputQrY').value = qrYInput.value;
    syncCoords();
}

window.addEventListener('resize', updateOverlay);

jenisPilihan?.addEventListener('change', function () {
    if (this.value === '__manual__') {
        jenisSertifikat.classList.remove('hidden');
        jenisSertifikat.value = '';
        jenisSertifikat.focus();
    } else {
        jenisSertifikat.classList.add('hidden');
        jenisSertifikat.value = this.value;
    }
});

if (jenisSertifikat) {
    if (jenisSertifikat.value && !jenisSertifikat.classList.contains('hidden')) {
        jenisPilihan.value = '__manual__';
    } else if (jenisSertifikat.value) {
        jenisPilihan.value = jenisSertifikat.value;
    }
}

// ── Modal countdown ───────────────────────────────────────────
let countdownTimer = null;

function openWarnModal() {
    document.getElementById('warnModal').classList.remove('hidden');
    startCountdown();
}
function closeWarnModal() {
    document.getElementById('warnModal').classList.add('hidden');
    clearInterval(countdownTimer);
    resetCountdown();
}
function startCountdown() {
    resetCountdown();
    let sisa = 3;
    const btn = document.getElementById('confirmBtn');
    countdownTimer = setInterval(() => {
        sisa--;
        document.getElementById('countdown').textContent = sisa;
        if (sisa <= 0) {
            clearInterval(countdownTimer);
            btn.disabled  = false;
            btn.className = 'flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition text-sm cursor-pointer';
            btn.innerHTML = '<i class="bi bi-check-lg mr-1"></i>Ya, Simpan Perubahan';
        }
    }, 1000);
}
function resetCountdown() {
    const btn = document.getElementById('confirmBtn');
    btn.disabled  = true;
    btn.className = 'flex-1 px-4 py-2.5 bg-gray-300 text-gray-500 rounded-lg font-semibold transition text-sm cursor-not-allowed';
    btn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i>Simpan (<span id="countdown">3</span>)';
}
function submitEdit() {
    document.getElementById('editForm').submit();
}
document.getElementById('warnModal').addEventListener('click', function (e) {
    if (e.target === this) closeWarnModal();
});
</script>
@endsection
