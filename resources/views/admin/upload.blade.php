@extends('layouts.app')
@section('title', 'Upload Sertifikat')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <form method="POST" action="{{ route('admin.upload.post') }}" enctype="multipart/form-data" id="uploadForm">
            @csrf

            <div class="grid lg:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Data Mahasiswa</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama" value="{{ old('nama') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NPM</label>
                                <input type="text" name="npm" value="{{ old('npm') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Sertifikat</label>
                                <input type="text" name="nomor_sertifikat" value="{{ old('nomor_sertifikat') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Sertifikat</label>
                                <select id="jenisPilihan" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Pilih Jenis</option>
                                    @foreach(['Asisten Dosen','Asisten Praktikum','Pelatihan','Seminar'] as $jenis)
                                    <option value="{{ $jenis }}" {{ old('jenis_sertifikat') == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                                    @endforeach
                                    <option value="__manual__">Ketik Manual</option>
                                </select>
                                <input type="text" name="jenis_sertifikat" id="jenisSertifikat"
                                       value="{{ old('jenis_sertifikat') }}"
                                       placeholder="Ketik jenis sertifikat"
                                       class="w-full mt-2 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 {{ in_array(old('jenis_sertifikat'), ['Asisten Dosen','Asisten Praktikum','Pelatihan','Seminar']) || !old('jenis_sertifikat') ? 'hidden' : '' }}"
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Sampai</label>
                                <input type="date" name="valid_until" value="{{ old('valid_until') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-400">Kosongkan jika masa berlaku tidak ditentukan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">File Sertifikat</h2>
                        <label for="certFile" class="block text-sm font-medium text-gray-700 mb-2">Pilih File (JPG/PNG, maks 5MB)</label>
                        <input type="file" name="certificate_file" id="certFile" accept="image/jpeg,image/png" class="w-full text-sm text-gray-700 border border-gray-300 rounded-lg px-3 py-2 cursor-pointer bg-white focus:ring-2 focus:ring-blue-500" required>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Opsi QR Code</h2>
                        <div class="space-y-3 mb-5">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="attach_qr" id="attachQr" value="true" class="w-5 h-5 text-blue-600 rounded">
                                <span class="text-gray-700 font-medium">Tempel QR Code di sertifikat</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="qr_only" id="qrOnly" value="true" class="w-5 h-5 text-green-600 rounded">
                                <span class="text-gray-700 font-medium">Cetak QR saja <span class="text-xs text-gray-400">(output gambar putih + QR)</span></span>
                            </label>
                        </div>

                        <div id="qrOptions" class="space-y-4 hidden">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="text-sm font-medium text-gray-700">Ukuran QR</label>
                                    <span class="text-sm font-bold text-blue-600" id="qrSizeLabel">150 px</span>
                                </div>
                                <input type="range" name="qr_size" id="qrSize" min="150" max="400" value="150" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi X (px)</label>
                                    <input type="number" name="qr_x" id="qrX" value="50" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi Y (px)</label>
                                    <input type="number" name="qr_y" id="qrY" value="50" min="0" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <p class="text-xs text-blue-500 font-medium">Geser QR di preview untuk atur posisi.</p>
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg">Upload Sertifikat</button>
                        <a href="{{ route('admin.dashboard') }}" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">Batal</a>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 sticky top-20">
                        <h2 class="text-lg font-bold text-gray-900 mb-4">Preview</h2>

                        <div id="previewEmpty" class="flex flex-col items-center justify-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                            <p class="text-sm text-gray-400">Pilih file sertifikat untuk melihat preview.</p>
                        </div>

                        <div id="previewContainer" class="hidden relative rounded-lg overflow-hidden bg-gray-100" style="touch-action: none; user-select: none;">
                            <img id="certPreview" src="" alt="Preview" class="w-full h-auto block" style="pointer-events: none;">
                            <div id="qrOverlay" class="absolute hidden" style="left:50px; top:50px; width:150px; height:150px; cursor:grab; z-index:10;">
                                <div style="width:100%; height:100%; background:white; border:3px solid #3b82f6; border-radius:8px; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.3);">
                                    <span id="qrIcon" style="font-size:40px; color:#1f2937; font-weight:700; letter-spacing:0.2em;">QR</span>
                                </div>
                                <div style="position:absolute; top:-22px; left:0; right:0; text-align:center;">
                                    <span style="background:#3b82f6; color:white; font-size:11px; padding:2px 8px; border-radius:4px;">Geser QR</span>
                                </div>
                            </div>
                        </div>

                        <div id="coordInfo" class="hidden mt-3 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-700">
                                Posisi pada gambar asli:
                                <span class="font-bold">X: <span id="realX">50</span>px, Y: <span id="realY">50</span>px</span>
                                <span class="font-bold">Ukuran: <span id="realSize">150</span>px</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
const certFile = document.getElementById('certFile');
const certPreview = document.getElementById('certPreview');
const previewEmpty = document.getElementById('previewEmpty');
const previewContainer = document.getElementById('previewContainer');
const qrOverlay = document.getElementById('qrOverlay');
const qrIcon = document.getElementById('qrIcon');
const attachQr = document.getElementById('attachQr');
const qrOnly = document.getElementById('qrOnly');
const qrOptions = document.getElementById('qrOptions');
const qrSize = document.getElementById('qrSize');
const qrSizeLabel = document.getElementById('qrSizeLabel');
const qrX = document.getElementById('qrX');
const qrY = document.getElementById('qrY');
const coordInfo = document.getElementById('coordInfo');

let origW = 0;

certFile.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        const img = new Image();
        img.onload = function () {
            origW = img.naturalWidth;
            certPreview.src = e.target.result;
            previewEmpty.classList.add('hidden');
            previewContainer.classList.remove('hidden');
            if (attachQr.checked || qrOnly.checked) {
                qrOverlay.classList.remove('hidden');
                coordInfo.classList.remove('hidden');
                updateOverlay();
            }
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

attachQr.addEventListener('change', function () {
    if (this.checked) qrOnly.checked = false;
    toggleQrOptions();
});
qrOnly.addEventListener('change', function () {
    if (this.checked) attachQr.checked = false;
    toggleQrOptions();
});

function toggleQrOptions() {
    const show = attachQr.checked || qrOnly.checked;
    qrOptions.classList.toggle('hidden', !show);
    if (show && origW) {
        qrOverlay.classList.remove('hidden');
        coordInfo.classList.remove('hidden');
        updateOverlay();
    } else if (!show) {
        qrOverlay.classList.add('hidden');
        coordInfo.classList.add('hidden');
    }
}

qrSize.addEventListener('input', function () {
    qrSizeLabel.textContent = this.value + ' px';
    updateOverlay();
    syncCoords();
});
qrX.addEventListener('input', () => { updateOverlay(); syncCoords(); });
qrY.addEventListener('input', () => { updateOverlay(); syncCoords(); });

function getScale() {
    return origW ? previewContainer.offsetWidth / origW : 1;
}

function updateOverlay() {
    const scale = getScale();
    const size = parseInt(qrSize.value);
    const dispSize = size * scale;
    const dispX = (parseInt(qrX.value) || 0) * scale;
    const dispY = (parseInt(qrY.value) || 0) * scale;
    qrOverlay.style.width = dispSize + 'px';
    qrOverlay.style.height = dispSize + 'px';
    qrOverlay.style.left = dispX + 'px';
    qrOverlay.style.top = dispY + 'px';
    qrIcon.style.fontSize = Math.max(16, dispSize * 0.35) + 'px';
}

function syncCoords() {
    document.getElementById('realX').textContent = qrX.value;
    document.getElementById('realY').textContent = qrY.value;
    document.getElementById('realSize').textContent = qrSize.value;
}

let dragging = false, startMouseX, startMouseY, startLeft, startTop;
qrOverlay.addEventListener('mousedown', function (e) {
    dragging = true;
    startMouseX = e.clientX; startMouseY = e.clientY;
    startLeft = parseInt(qrOverlay.style.left) || 0;
    startTop = parseInt(qrOverlay.style.top) || 0;
    qrOverlay.style.cursor = 'grabbing';
    e.preventDefault();
});
document.addEventListener('mousemove', function (e) { if (dragging) moveOverlay(e.clientX, e.clientY); });
document.addEventListener('mouseup', function () { if (dragging) { dragging = false; qrOverlay.style.cursor = 'grab'; } });
qrOverlay.addEventListener('touchstart', function (e) {
    const t = e.touches[0];
    dragging = true;
    startMouseX = t.clientX; startMouseY = t.clientY;
    startLeft = parseInt(qrOverlay.style.left) || 0;
    startTop = parseInt(qrOverlay.style.top) || 0;
    e.preventDefault();
}, { passive: false });
document.addEventListener('touchmove', function (e) {
    if (dragging) { moveOverlay(e.touches[0].clientX, e.touches[0].clientY); e.preventDefault(); }
}, { passive: false });
document.addEventListener('touchend', () => { dragging = false; });

function moveOverlay(clientX, clientY) {
    const scale = getScale();
    const maxLeft = previewContainer.offsetWidth - qrOverlay.offsetWidth;
    const maxTop = previewContainer.offsetHeight - qrOverlay.offsetHeight;
    const newLeft = Math.max(0, Math.min(startLeft + (clientX - startMouseX), maxLeft));
    const newTop = Math.max(0, Math.min(startTop + (clientY - startMouseY), maxTop));
    qrOverlay.style.left = newLeft + 'px';
    qrOverlay.style.top = newTop + 'px';
    qrX.value = Math.round(newLeft / scale);
    qrY.value = Math.round(newTop / scale);
    syncCoords();
}
window.addEventListener('resize', updateOverlay);

const jenisPilihan = document.getElementById('jenisPilihan');
const jenisSertifikat = document.getElementById('jenisSertifikat');
jenisPilihan.addEventListener('change', function () {
    if (this.value === '__manual__') {
        jenisSertifikat.classList.remove('hidden');
        jenisSertifikat.value = '';
        jenisSertifikat.focus();
    } else {
        jenisSertifikat.classList.add('hidden');
        jenisSertifikat.value = this.value;
    }
});
if (jenisSertifikat.value && !jenisSertifikat.classList.contains('hidden')) {
    jenisPilihan.value = '__manual__';
} else if (jenisSertifikat.value) {
    jenisPilihan.value = jenisSertifikat.value;
}
</script>
@endsection
