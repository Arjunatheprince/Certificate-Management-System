<nav class="sticky top-0 z-50 border-b border-blue-100 bg-white/95 backdrop-blur-sm">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
        <div class="shrink-0">
            <div>
                <span class="block text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Portal Admin</span>
                <div class="mt-0.5 flex items-center gap-2">
                    <span class="block text-base font-semibold text-slate-900">Sistem Sertifikat Digital</span>
                    <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-blue-700">
                        {{ auth()->user()->role_label }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex min-w-0 flex-1 items-center justify-center gap-2 overflow-x-auto px-2">
            <a href="{{ route('admin.dashboard') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Dashboard
            </a>
            <a href="{{ route('admin.certificates') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.certificates') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Daftar Sertifikat
            </a>
            <a href="{{ route('admin.verify') }}" class="rounded-full px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('admin.verify') ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'border border-blue-100 bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Verifikasi
            </a>
            @if(auth()->user()->isAdmin())
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

        <div class="ml-auto flex shrink-0 items-center gap-3">
            <div class="hidden items-center gap-3 sm:flex">
                <div class="text-right">
                    <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name ?? auth()->user()->email }}</p>
                    <span class="text-xs font-medium text-slate-500">{{ auth()->user()->role_label }}</span>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold text-white shadow-sm {{ auth()->user()->isSuperAdmin() ? 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-200' : 'bg-blue-600 shadow-blue-200' }}">
                    {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
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
