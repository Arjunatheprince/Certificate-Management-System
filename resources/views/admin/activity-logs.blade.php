@extends('layouts.app')
@section('title', 'Log Aktivitas')

@section('content')
<style>
    .admin-logs-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-logs-card {
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }
</style>

<div class="admin-logs-shell min-h-screen">
    @include('layouts.navbar')

    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="admin-logs-card rounded-3xl overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Log Aktivitas</h2>
                    <p class="text-sm text-gray-500 mt-1">Audit trail semua aksi pengguna - SHA-256 + RBAC</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center space-x-1 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                    <i class="bi bi-arrow-left"></i><span>Kembali</span>
                </a>
            </div>

            <div class="p-6 space-y-3">
                @forelse($logs as $date => $entries)
                @php
                    $carbon = \Carbon\Carbon::parse($date);
                    $label  = $carbon->translatedFormat('l, d F Y');
                @endphp
                <details class="border border-gray-200 rounded-lg overflow-hidden group">
                    <summary class="w-full flex items-center justify-between px-5 py-4 bg-gray-50 hover:bg-gray-100 transition text-left cursor-pointer list-none">
                        <div class="flex items-center space-x-3">
                            <i class="bi bi-folder-fill text-yellow-400 group-open:hidden"></i>
                            <i class="bi bi-folder2-open text-yellow-500 hidden group-open:inline-block"></i>
                            <span class="font-semibold text-gray-800">{{ $label }}</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-medium">
                                {{ $entries->count() }} aktivitas
                            </span>
                            <i class="bi bi-chevron-down text-gray-400 text-sm group-open:hidden"></i>
                            <i class="bi bi-chevron-up text-gray-400 text-sm hidden group-open:inline-block"></i>
                        </div>
                    </summary>

                    <div class="divide-y divide-gray-100">
                        @foreach($entries as $log)
                        <div class="px-5 py-3 flex items-start justify-between hover:bg-gray-50">
                            <div class="flex items-start space-x-3">
                                <span class="mt-0.5 text-xs px-2.5 py-1 rounded-full font-medium {{ $log->action_badge }}">
                                    {{ strtoupper($log->action) }}
                                </span>
                                <div>
                                    <p class="text-sm text-gray-700">{{ $log->description ?? $log->target ?? '-' }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $log->user?->name ?? 'System' }}
                                        @if($log->user) • <span class="text-xs px-1.5 py-0.5 rounded-full {{ $log->user->role_badge }}">{{ $log->user->role_label }}</span> @endif
                                        • {{ $log->ip_address }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap ml-4">
                                {{ $log->created_at->format('H:i:s') }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </details>
                @empty
                <div class="py-12 text-center text-gray-400">
                    <i class="bi bi-journal-x text-4xl block mb-2"></i>
                    Belum ada log aktivitas
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
