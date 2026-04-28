@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $nonceBinding = 'csp_nonce';
    $cspNonce = app()->bound($nonceBinding) ? app()->make($nonceBinding) : '';
@endphp
<style nonce="{{ $cspNonce }}">
    .admin-dashboard-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-dashboard-shell::before {
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

    .admin-dashboard-stage {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }

    .admin-dashboard-stage::before {
        content: none;
    }

    .admin-bg-orb {
        position: absolute;
        border-radius: 9999px;
        pointer-events: none;
        opacity: 0.42;
        filter: blur(10px);
        animation: admin-dashboard-float 14s ease-in-out infinite;
    }

    .admin-bg-orb.orb-left {
        top: 5rem;
        left: -4rem;
        width: 18rem;
        height: 18rem;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.26) 0%, rgba(96, 165, 250, 0.08) 52%, transparent 76%);
    }

    .admin-bg-orb.orb-right {
        top: 6rem;
        right: -4rem;
        width: 22rem;
        height: 22rem;
        background: radial-gradient(circle, rgba(147, 197, 253, 0.32) 0%, rgba(191, 219, 254, 0.1) 48%, transparent 74%);
        animation-delay: -4s;
    }

    .admin-bg-orb.orb-bottom {
        bottom: 2rem;
        left: 50%;
        width: 24rem;
        height: 24rem;
        transform: translateX(-50%);
        background: radial-gradient(circle, rgba(125, 211, 252, 0.2) 0%, rgba(186, 230, 253, 0.08) 46%, transparent 72%);
        animation-delay: -8s;
    }

    .admin-hero-panel {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.98) 0%, rgba(255, 255, 255, 0.98) 52%, rgba(239, 246, 255, 0.94) 100%);
        box-shadow: 0 20px 40px -34px rgba(15, 23, 42, 0.18);
    }

    .admin-hero-panel::after {
        content: "";
        position: absolute;
        right: -3rem;
        bottom: -4rem;
        width: 14rem;
        height: 14rem;
        border-radius: 9999px;
        background: radial-gradient(circle, rgba(96, 165, 250, 0.2), transparent 72%);
        pointer-events: none;
    }

    .admin-section-chip {
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
        box-shadow: 0 12px 28px -22px rgba(37, 99, 235, 0.8);
    }

    .admin-section-chip::before {
        content: "";
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 9999px;
        background: linear-gradient(135deg, #2563eb, #60a5fa);
        box-shadow: 0 0 0 5px rgba(191, 219, 254, 0.65);
    }

    .admin-status-card {
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 16px 36px -30px rgba(15, 23, 42, 0.14);
    }

    .admin-stat-card-local {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
        box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.16);
    }

    .admin-stat-card-local::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(219, 234, 254, 0.24), transparent 48%);
        pointer-events: none;
    }

    .admin-stat-card-local::after {
        content: "";
        position: absolute;
        left: 1.5rem;
        right: 1.5rem;
        bottom: 0;
        height: 3px;
        border-radius: 9999px;
        background: linear-gradient(90deg, rgba(147, 197, 253, 0.55), rgba(37, 99, 235, 0.9), rgba(125, 211, 252, 0.55));
    }

    .admin-status-card.role-superadmin {
        border-color: rgba(251, 191, 36, 0.45);
        background: linear-gradient(135deg, rgba(255, 251, 235, 0.97), rgba(255, 255, 255, 0.96));
    }

    .admin-status-card.role-superadmin .role-kicker {
        color: #b45309;
    }

    .admin-status-card.role-superadmin .role-dot {
        background: linear-gradient(135deg, #f59e0b, #f97316);
        box-shadow: 0 0 0 5px rgba(254, 243, 199, 0.95);
    }

    .admin-stat-card-local.stat-superadmin {
        border-color: rgba(251, 191, 36, 0.4);
        background: linear-gradient(180deg, rgba(255, 251, 235, 0.98), rgba(255, 255, 255, 0.98));
    }

    .admin-stat-card-local.stat-superadmin::before {
        background: linear-gradient(135deg, rgba(253, 230, 138, 0.22), transparent 48%);
    }

    .admin-stat-card-local.stat-superadmin::after {
        background: linear-gradient(90deg, rgba(252, 211, 77, 0.55), rgba(245, 158, 11, 0.92), rgba(249, 115, 22, 0.55));
    }

    @keyframes admin-dashboard-float {
        0%, 100% {
            transform: translate3d(0, 0, 0);
        }
        50% {
            transform: translate3d(0, -18px, 0);
        }
    }
</style>

<div class="admin-dashboard-shell min-h-screen">
    <div class="admin-bg-orb orb-left"></div>
    <div class="admin-bg-orb orb-right"></div>
    <div class="admin-bg-orb orb-bottom"></div>
    @include('layouts.navbar')

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="admin-dashboard-stage rounded-[2rem] p-4 sm:p-5 lg:p-6">
            <div class="admin-hero-panel rounded-3xl p-6 portal-panel sm:p-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <span class="admin-section-chip">Area Administrasi</span>
                        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">Dashboard Admin</h1>
                        <p class="mt-2 text-sm text-slate-500">Ringkasan utama sistem ditampilkan sesuai hak akses pengguna.</p>
                    </div>
                    <div class="admin-status-card rounded-2xl px-5 py-4 {{ auth()->user()->isSuperAdmin() ? 'role-superadmin' : '' }}">
                        <div class="flex items-center gap-2">
                            <span class="role-dot inline-flex h-2.5 w-2.5 rounded-full bg-gradient-to-br from-blue-500 to-sky-400 shadow-[0_0_0_5px_rgba(219,234,254,0.9)]"></span>
                            <p class="role-kicker text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">Status Akses</p>
                        </div>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ auth()->user()->role_label }}</p>
                        <p class="mt-1 text-sm text-slate-500">Masuk sebagai administrator aktif.</p>
                    </div>
                </div>
                <div class="pulse-line mt-5 h-1 rounded-full bg-gradient-to-r from-blue-200 via-blue-600 to-sky-200"></div>
            </div>

            <section class="mt-6">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <span class="admin-section-chip">Ringkasan</span>
                        <h2 class="text-lg font-bold text-slate-900">Ringkasan Sistem</h2>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="admin-stat-card-local rounded-3xl p-6 portal-panel" data-delay="1">
                        <p class="text-sm font-medium text-blue-700">Total Sertifikat</p>
                        <p class="mt-4 text-4xl font-bold tracking-tight text-slate-900">{{ $totalCerts }}</p>
                        <p class="mt-3 text-sm text-slate-500">Dokumen sertifikat yang sudah tersimpan.</p>
                    </div>
                    <div class="admin-stat-card-local rounded-3xl p-6 portal-panel" data-delay="1">
                        <p class="text-sm font-medium text-blue-700">Upload Hari Ini</p>
                        <p class="mt-4 text-4xl font-bold tracking-tight text-slate-900">{{ $todayUploads }}</p>
                        <p class="mt-3 text-sm text-slate-500">Penambahan sertifikat yang masuk hari ini.</p>
                    </div>
                    @if(auth()->user()->isSuperAdmin())
                    <div class="admin-stat-card-local stat-superadmin rounded-3xl p-6 portal-panel" data-delay="1">
                        <p class="text-sm font-medium text-amber-700">Total Admin</p>
                        <p class="mt-4 text-4xl font-bold tracking-tight text-slate-900">{{ $totalAdmins }}</p>
                        <p class="mt-3 text-sm text-slate-500">Akun administrator yang saat ini aktif.</p>
                    </div>
                    <div class="admin-stat-card-local stat-superadmin rounded-3xl p-6 portal-panel" data-delay="1">
                        <p class="text-sm font-medium text-amber-700">Aktivitas Hari Ini</p>
                        <p class="mt-4 text-4xl font-bold tracking-tight text-slate-900">{{ $todayLogs }}</p>
                        <p class="mt-3 text-sm text-slate-500">Peristiwa sistem yang tercatat untuk audit.</p>
                    </div>
                    @else
                    <div class="admin-stat-card-local rounded-3xl p-6 portal-panel sm:col-span-2 xl:col-span-2" data-delay="1">
                        <p class="text-sm font-medium text-blue-700">Peran Akun</p>
                        <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ auth()->user()->role_label }}</p>
                        <p class="mt-3 text-sm text-slate-500">Hak akses saat ini mengikuti peran administrator yang digunakan.</p>
                    </div>
                    @endif
                </div>
            </section>
        </div>

    </div>
</div>
@endsection
