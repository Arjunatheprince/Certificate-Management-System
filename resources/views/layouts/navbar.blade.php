@php
    $currentUser = auth()->user();
    $roleLabel = $currentUser->role_label;
    $displayName = $currentUser->name ?: $currentUser->email;
    $showRoleUnderName = trim(strtolower($displayName)) !== trim(strtolower($roleLabel));
@endphp

<nav class="fixed inset-x-0 top-0 z-50 w-full border-b border-blue-100 bg-white shadow-sm" style="background-color: #ffffff;">
    <div class="grid h-16 w-full grid-cols-[minmax(220px,260px)_minmax(0,1fr)_minmax(210px,260px)] items-center bg-white px-4 sm:px-6 lg:px-8">
        <div class="min-w-0">
            <div>
                <span class="block text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Portal Admin</span>
                <span class="mt-0.5 block text-base font-semibold text-slate-900">Sistem Sertifikat Digital</span>
            </div>
        </div>

        <div class="flex min-w-0 items-center justify-center gap-2 overflow-x-auto px-4">
            <a href="{{ route('admin.dashboard') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Dashboard
            </a>
            <a href="{{ route('admin.certificates') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.certificates') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Daftar Sertifikat
            </a>
            <a href="{{ route('admin.verify') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.verify') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Verifikasi
            </a>
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
            <a href="{{ route('admin.upload') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.upload') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Upload
            </a>
            @endif
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.users.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Kelola User
            </a>
            <a href="{{ route('admin.activity-logs') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.activity-logs') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Log Aktivitas
            </a>
            @endif
        </div>

        <div class="flex min-w-0 items-center justify-end gap-3 border-l border-slate-100 bg-white pl-5">
            <div class="hidden items-center gap-3 sm:flex">
                <div class="text-right">
                    <p class="text-sm font-semibold text-slate-900">{{ $displayName }}</p>
                    @if($showRoleUnderName)
                    <span class="text-xs font-medium text-slate-500">{{ $roleLabel }}</span>
                    @endif
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold text-white shadow-sm {{ auth()->user()->isSuperAdmin() ? 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-200' : 'bg-blue-600 shadow-blue-200' }}">
                    {{ strtoupper(substr($displayName, 0, 1)) }}
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 transition hover:bg-blue-100">
                    Keluar
                </button>
            </form>
        </div>
    </div>
</nav>
<div class="h-16" aria-hidden="true"></div>
