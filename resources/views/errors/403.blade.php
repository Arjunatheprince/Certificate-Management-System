<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center px-4">
    <div class="w-full max-w-lg">

        {{-- Badge status --}}
        <div class="flex justify-center mb-6">
            <span class="inline-flex items-center gap-2 rounded-full bg-red-100 border border-red-200 px-4 py-1.5 text-sm font-bold text-red-700 tracking-wide uppercase">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                HTTP 403 Forbidden
            </span>
        </div>

        {{-- Card utama --}}
        <div class="bg-white rounded-2xl border border-red-200 shadow-lg overflow-hidden">

            {{-- Header merah --}}
            <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-5 flex items-center gap-4">
                <div class="flex-shrink-0 w-14 h-14 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Akses Ditolak</h1>
                    <p class="text-red-100 text-sm mt-0.5">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-4">

                {{-- Info role --}}
                <div class="rounded-xl bg-red-50 border border-red-100 px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm">
                        <p class="font-semibold text-red-800">Role Anda tidak memiliki akses</p>
                        <p class="text-red-600 mt-0.5">
                            Login sebagai
                            <span class="font-bold">{{ auth()->user()?->role_label ?? 'Guest' }}</span>
                            — halaman ini hanya untuk <span class="font-bold">Super Admin</span>.
                        </p>
                    </div>
                </div>

                {{-- Detail teknis --}}
                <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 space-y-2 text-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Detail Teknis</p>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Status Code</span>
                        <span class="font-mono font-bold text-red-600">403 Forbidden</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">URL</span>
                        <span class="font-mono text-xs text-slate-700 truncate max-w-[220px]">{{ request()->path() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Role Aktif</span>
                        <span class="font-semibold text-slate-700">{{ auth()->user()?->role_label ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">User</span>
                        <span class="font-semibold text-slate-700">{{ auth()->user()?->email ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Waktu</span>
                        <span class="text-slate-700">{{ now()->format('d/m/Y H:i:s') }}</span>
                    </div>
                </div>

                <p class="text-xs text-slate-400 text-center">Hubungi Super Admin jika Anda membutuhkan akses ke halaman ini.</p>
            </div>

            {{-- Footer tombol --}}
            <div class="px-6 pb-5">
                <a href="{{ route('admin.dashboard') }}"
                   class="w-full flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 transition px-5 py-3 text-sm font-semibold text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
