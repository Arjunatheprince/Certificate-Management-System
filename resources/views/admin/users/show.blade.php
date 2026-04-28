@extends('layouts.app')
@section('title', 'Detail User - {{ $user->name }}')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @include('layouts.notification')

        <!-- Header profil -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-5">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold
                        {{ $user->isSuperAdmin() ? 'bg-red-500' : ($user->isAdmin() ? 'bg-blue-600' : 'bg-gray-400') }}">
                        {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                        <p class="text-gray-500 text-sm">{{ $user->email }}</p>
                        <div class="flex items-center space-x-2 mt-1.5">
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $user->role_badge }}">
                                {{ $user->role_label }}
                            </span>
                            @if($user->is_active)
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-green-100 text-green-700">Aktif</span>
                            @else
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-red-100 text-red-700">Nonaktif</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
                        <i class="bi bi-arrow-left"></i><span>Kembali</span>
                    </a>
                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                        <i class="bi bi-pencil mr-1"></i>Edit User
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistik aktivitas -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @php
                $statItems = [
                    ['action' => 'upload', 'label' => 'Upload',  'icon' => 'bi-cloud-upload',   'color' => 'blue'],
                    ['action' => 'edit',   'label' => 'Edit',    'icon' => 'bi-pencil-square',  'color' => 'yellow'],
                    ['action' => 'delete', 'label' => 'Hapus',   'icon' => 'bi-trash',          'color' => 'red'],
                    ['action' => 'login',  'label' => 'Login',   'icon' => 'bi-box-arrow-in-right', 'color' => 'green'],
                ];
            @endphp
            @foreach($statItems as $s)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-xs mb-1">{{ $s['label'] }}</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $stats[$s['action']] ?? 0 }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-{{ $s['color'] }}-100">
                        <i class="bi {{ $s['icon'] }} text-{{ $s['color'] }}-600"></i>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="grid lg:grid-cols-2 gap-6">

            <!-- Sertifikat yang di-upload -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b flex items-center justify-between">
                    <h2 class="font-bold text-gray-900 flex items-center">
                        <i class="bi bi-file-earmark-text text-blue-600 mr-2"></i>
                        Sertifikat Diupload
                        <span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ $certificates->count() }}</span>
                    </h2>
                </div>
                <div class="divide-y max-h-96 overflow-y-auto">
                    @forelse($certificates as $cert)
                    <div class="px-5 py-3 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $cert->nama }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ $cert->nomor_sertifikat }}</p>
                                <span class="text-xs text-gray-400">{{ $cert->jenis_sertifikat }}</span>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <p class="text-xs text-gray-400">{{ $cert->tanggal_terbit }}</p>
                                <a href="/{{ $cert->file_path }}" target="_blank"
                                   class="text-xs text-blue-600 hover:underline">
                                    <i class="bi bi-eye mr-0.5"></i>Lihat
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-gray-400 text-sm">
                        <i class="bi bi-inbox text-3xl block mb-2"></i>
                        Belum ada sertifikat yang diupload
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Log aktivitas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-5 border-b flex items-center justify-between">
                    <h2 class="font-bold text-gray-900 flex items-center">
                        <i class="bi bi-journal-text text-yellow-600 mr-2"></i>
                        Riwayat Aktivitas
                        <span class="ml-2 text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">{{ $logs->total() }}</span>
                    </h2>
                </div>
                <div class="divide-y max-h-96 overflow-y-auto">
                    @forelse($logs as $log)
                    <div class="px-5 py-3 hover:bg-gray-50 flex items-start justify-between space-x-3">
                        <div class="flex items-start space-x-3 min-w-0">
                            <span class="mt-0.5 flex-shrink-0 text-xs px-2 py-0.5 rounded-full font-medium {{ $log->action_badge }}">
                                {{ strtoupper($log->action) }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-700 truncate">{{ $log->description ?? $log->target ?? '-' }}</p>
                                <p class="text-xs text-gray-400">{{ $log->ip_address }}</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap flex-shrink-0">
                            {{ $log->created_at->format('d/m/y H:i') }}
                        </span>
                    </div>
                    @empty
                    <div class="px-5 py-10 text-center text-gray-400 text-sm">
                        <i class="bi bi-journal-x text-3xl block mb-2"></i>
                        Belum ada aktivitas
                    </div>
                    @endforelse
                </div>
                @if($logs->hasPages())
                <div class="p-4 border-t">{{ $logs->links() }}</div>
                @endif
            </div>

        </div>

    </div>
</div>
@endsection
