@extends('layouts.app')
@section('title', 'Daftar Sertifikat')

@section('content')
<style nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    .admin-page-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 10% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-page-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.18;
        background-image:
            linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
            linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
        background-size: 74px 74px;
        mask-image: radial-gradient(circle at center, rgba(15, 23, 42, 0.9), transparent 82%);
    }

    .admin-page-orb {
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        opacity: 0.54;
        filter: blur(10px);
        animation: admin-page-float 14s ease-in-out infinite;
    }

    .admin-page-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }

    .admin-page-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.98) 0%, rgba(255, 255, 255, 0.98) 50%, rgba(239, 246, 255, 0.94) 100%);
        box-shadow: 0 26px 58px -40px rgba(15, 23, 42, 0.24);
    }

    .admin-page-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        border-radius: 9999px;
        padding: 0.42rem 0.95rem;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .admin-page-chip::before {
        content: "";
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 9999px;
        background: linear-gradient(135deg, #2563eb, #60a5fa);
        box-shadow: 0 0 0 5px rgba(191, 219, 254, 0.65);
    }

    @keyframes admin-page-float {
        0%, 100% { transform: translate3d(0, 0, 0); }
        50% { transform: translate3d(0, -18px, 0); }
    }
</style>

<div class="admin-page-shell min-h-screen">
    <div class="admin-page-orb left-[-4rem] top-24 h-64 w-64 bg-[radial-gradient(circle,_rgba(59,130,246,0.22)_0%,_rgba(96,165,250,0.08)_52%,_transparent_76%)]"></div>
    <div class="admin-page-orb right-[-5rem] top-28 h-72 w-72 bg-[radial-gradient(circle,_rgba(147,197,253,0.3)_0%,_rgba(191,219,254,0.08)_48%,_transparent_74%)]" style="animation-delay:-4s;"></div>
    @include('layouts.navbar')

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        @php
            $sortIcon = function (string $column) use ($sortBy, $sortDir) {
                if ($sortBy !== $column) {
                    return 'bi-arrow-down-up';
                }

                return $sortDir === 'asc' ? 'bi-sort-down' : 'bi-sort-up';
            };

            $sortUrl = function (string $column) use ($sortBy, $sortDir) {
                $nextDir = $sortBy === $column && $sortDir === 'asc' ? 'desc' : 'asc';

                return route('admin.certificates', array_merge(request()->query(), [
                    'sort_by' => $column,
                    'sort_dir' => $nextDir,
                ]));
            };
        @endphp

        <div class="admin-page-hero rounded-3xl p-6 sm:p-7">
            <div class="flex flex-col items-start gap-6">
                <div class="max-w-2xl">
                    <span class="admin-page-chip">Data Sertifikat</span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Daftar Sertifikat</h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 sm:text-[15px]">Kelola pencarian, penyaringan, dan peninjauan seluruh data sertifikat dari satu halaman kerja.</p>
                </div>
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                <div class="flex items-center">
                    <a href="{{ route('admin.upload') }}" class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-blue-200 transition hover:bg-blue-700">
                        <i class="bi bi-upload"></i>
                        <span>Upload Baru</span>
                    </a>
                </div>
                @endif
            </div>
        </div>

        <div class="admin-page-card mt-6 rounded-3xl">
            <div class="border-b border-slate-100 p-6 flex justify-between items-center">
                <div>
                    <h2 id="certificatesTotal" class="text-xl font-bold text-slate-900">Total: {{ $certificates->total() }} Sertifikat</h2>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ auth()->user()->isSuperAdmin() ? 'SuperAdmin dapat mengelola seluruh sertifikat.' : 'Admin hanya dapat mengedit sertifikat yang diupload sendiri.' }}
                    </p>
                </div>
            </div>

            <div class="border-b border-slate-100 bg-slate-50/80 p-6">
                <form method="GET" action="{{ route('admin.certificates') }}" id="certificateFiltersForm" class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                        <div>
                            <label for="search" class="block text-sm font-semibold text-slate-900">Cari Sertifikat</label>
                            <p class="mt-1 text-xs text-slate-500">Temukan data berdasarkan nama, NPM, nomor sertifikat, atau jenis.</p>
                            <div class="relative mt-3">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Masukkan kata kunci pencarian" class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="flex flex-wrap items-end gap-2 lg:justify-end">
                            <button type="button" id="toggleCertificateFilters" aria-expanded="{{ $owner !== 'all' || $dateFrom !== '' || $dateTo !== '' ? 'true' : 'false' }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                <i class="bi bi-funnel"></i>
                                <span>Filter</span>
                            </button>
                            <span id="activeFilterIndicator" class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 {{ $search !== '' || $type !== '' || $owner !== 'all' || $dateFrom !== '' || $dateTo !== '' ? '' : 'hidden' }}">Filter aktif</span>
                            <div class="flex gap-2">
                                <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                                    Cari
                                </button>
                                <a href="{{ route('admin.certificates') }}" id="resetCertificateFilters" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>

                    <div id="certificateAdvancedFilters" class="mt-4 border-t border-slate-200 pt-4 {{ $owner !== 'all' || $dateFrom !== '' || $dateTo !== '' ? '' : 'hidden' }}">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-7">
                            <div class="xl:col-span-2">
                                <label for="owner" class="mb-1.5 block text-sm font-medium text-slate-700">Kepemilikan</label>
                                <select name="owner" id="owner" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                    <option value="all" {{ $owner === 'all' ? 'selected' : '' }}>Semua Data</option>
                                    <option value="mine" {{ $owner === 'mine' ? 'selected' : '' }}>Data Saya</option>
                                </select>
                            </div>

                            <div class="xl:col-span-3">
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Rentang Tanggal</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" aria-label="Tanggal Mulai" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                    <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" aria-label="Tanggal Akhir" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="xl:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Urutan Data</label>
                                <div class="grid grid-cols-2 gap-3 xl:grid-cols-1">
                                    <select name="sort_by" id="sort_by" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        <option value="created_at" {{ $sortBy === 'created_at' ? 'selected' : '' }}>Tanggal</option>
                                        <option value="name" {{ $sortBy === 'name' ? 'selected' : '' }}>Nama</option>
                                        <option value="npm" {{ $sortBy === 'npm' ? 'selected' : '' }}>NPM</option>
                                        <option value="certificate_number" {{ $sortBy === 'certificate_number' ? 'selected' : '' }}>Nomor Sertifikat</option>
                                        <option value="certificate_type" {{ $sortBy === 'certificate_type' ? 'selected' : '' }}>Jenis</option>
                                    </select>
                                    <select name="sort_dir" id="sort_dir" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500">
                                        <option value="desc" {{ $sortDir === 'desc' ? 'selected' : '' }}>Terbaru / Z-A</option>
                                        <option value="asc" {{ $sortDir === 'asc' ? 'selected' : '' }}>Terlama / A-Z</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div id="certificatesResults" class="relative transition-opacity duration-200">
                <div id="certificatesLoadingOverlay" class="hidden absolute inset-0 z-10 bg-white/70 backdrop-blur-[1px] flex items-center justify-center">
                    <div class="flex items-center gap-3 rounded-full border border-blue-100 bg-white px-4 py-2 shadow-sm">
                        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-blue-200 border-t-blue-600"></span>
                        <span class="text-sm font-medium text-gray-600">Memuat hasil...</span>
                    </div>
                </div>

                @include('admin.partials.certificates-results')
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
const certificateFiltersForm = document.getElementById('certificateFiltersForm');
const certificatesResults = document.getElementById('certificatesResults');
const certificatesLoadingOverlay = document.getElementById('certificatesLoadingOverlay');
const certificatesTotal = document.getElementById('certificatesTotal');
const activeFilterIndicator = document.getElementById('activeFilterIndicator');
const resetCertificateFilters = document.getElementById('resetCertificateFilters');
const searchInput = document.getElementById('search');
const toggleCertificateFilters = document.getElementById('toggleCertificateFilters');
const certificateAdvancedFilters = document.getElementById('certificateAdvancedFilters');
let liveSearchTimer = null;
let liveSearchController = null;

function bindDeleteButtons(scope = document) {
    scope.querySelectorAll('.delete-cert-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (confirm('Hapus sertifikat ' + this.dataset.name + '? Tindakan ini tidak dapat dibatalkan.')) {
                this.closest('form').submit();
            }
        });
    });
}

function updateFilterIndicator(hasActiveFilters) {
    if (!activeFilterIndicator) return;
    activeFilterIndicator.classList.toggle('hidden', !hasActiveFilters);
}

function setAdvancedFiltersVisibility(isVisible) {
    if (!certificateAdvancedFilters || !toggleCertificateFilters) return;
    certificateAdvancedFilters.classList.toggle('hidden', !isVisible);
    toggleCertificateFilters.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
    toggleCertificateFilters.classList.toggle('bg-slate-100', isVisible);
}

function hasAdvancedFiltersApplied() {
    const ownerField = document.getElementById('owner');
    const dateFromField = document.getElementById('date_from');
    const dateToField = document.getElementById('date_to');

    return (ownerField && ownerField.value !== 'all')
        || (dateFromField && dateFromField.value !== '')
        || (dateToField && dateToField.value !== '');
}

function syncFormWithUrl(url) {
    if (!certificateFiltersForm) return;
    const parsedUrl = new URL(url, window.location.origin);
    const params = parsedUrl.searchParams;
    const setValue = (id, fallback = '') => {
        const field = document.getElementById(id);
        if (field) field.value = params.get(id) ?? fallback;
    };
    setValue('search');
    setValue('owner', 'all');
    setValue('date_from');
    setValue('date_to');
    setValue('sort_by', 'created_at');
    setValue('sort_dir', 'desc');
}

async function fetchCertificates(url) {
    if (!certificatesResults || !certificateFiltersForm) return;
    if (liveSearchController) liveSearchController.abort();
    liveSearchController = new AbortController();

    try {
        certificatesResults.classList.add('opacity-70');
        certificatesLoadingOverlay?.classList.remove('hidden');

        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            signal: liveSearchController.signal,
        });

        if (!response.ok) throw new Error('Gagal memuat data sertifikat.');

        const data = await response.json();
        certificatesResults.innerHTML = data.html;

        if (certificatesTotal) {
            certificatesTotal.textContent = `Total: ${data.total} Sertifikat`;
        }

        syncFormWithUrl(url);
        updateFilterIndicator(Boolean(data.hasActiveFilters));
        setAdvancedFiltersVisibility(hasAdvancedFiltersApplied());
        bindDeleteButtons(certificatesResults);
        window.history.replaceState({}, '', url);
    } catch (error) {
        if (error.name !== 'AbortError') console.error(error);
    } finally {
        certificatesResults.classList.remove('opacity-70');
        certificatesLoadingOverlay?.classList.add('hidden');
    }
}

function buildFilterUrl(url = certificateFiltersForm.action) {
    const query = new URLSearchParams(new FormData(certificateFiltersForm)).toString();
    return query ? `${url}?${query}` : url;
}

function submitFiltersWithDelay() {
    clearTimeout(liveSearchTimer);
    liveSearchTimer = setTimeout(() => {
        fetchCertificates(buildFilterUrl());
    }, 300);
}

if (certificateFiltersForm) {
    certificateFiltersForm.addEventListener('submit', function (event) {
        event.preventDefault();
        fetchCertificates(buildFilterUrl());
    });

    certificateFiltersForm.querySelectorAll('select, input[type="date"]').forEach(field => {
        field.addEventListener('change', submitFiltersWithDelay);
    });
}

if (searchInput) searchInput.addEventListener('input', submitFiltersWithDelay);

if (toggleCertificateFilters) {
    toggleCertificateFilters.addEventListener('click', function () {
        setAdvancedFiltersVisibility(certificateAdvancedFilters?.classList.contains('hidden'));
    });
}

if (resetCertificateFilters && certificateFiltersForm) {
    resetCertificateFilters.addEventListener('click', function (event) {
        event.preventDefault();
        certificateFiltersForm.reset();
        setAdvancedFiltersVisibility(false);
        fetchCertificates(this.href);
    });
}

if (certificatesResults) {
    certificatesResults.addEventListener('click', function (event) {
        const link = event.target.closest('a');
        if (!link) return;
        const href = link.getAttribute('href') ?? '';
        const certificatesUrl = @json(route('admin.certificates'));
        const url = new URL(href, window.location.origin);
        if (url.pathname !== new URL(certificatesUrl, window.location.origin).pathname) return;
        event.preventDefault();
        fetchCertificates(href);
    });
}

bindDeleteButtons();
setAdvancedFiltersVisibility(hasAdvancedFiltersApplied());

if (window.location.search) {
    fetchCertificates(window.location.href);
}
</script>
@endpush
@endsection
