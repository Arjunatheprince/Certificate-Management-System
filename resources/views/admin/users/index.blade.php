@extends('layouts.app')
@section('title', 'Kelola User')

@section('content')
<style nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
    .admin-users-shell {
        position: relative;
        overflow: hidden;
        min-height: 100vh;
        background:
            radial-gradient(circle at 12% 14%, rgba(96, 165, 250, 0.16), transparent 18%),
            radial-gradient(circle at 88% 18%, rgba(59, 130, 246, 0.12), transparent 16%),
            linear-gradient(180deg, #f7fbff 0%, #eef5ff 46%, #f8fbff 100%);
    }

    .admin-users-card {
        border: 1px solid rgba(191, 219, 254, 0.95);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 56px -40px rgba(15, 23, 42, 0.24);
    }
</style>

<div class="admin-users-shell min-h-screen">
    @include('layouts.navbar')

    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="admin-users-card rounded-3xl overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Kelola User</h2>
                    <p class="text-sm text-gray-500 mt-1">Atur akun, peran, dan status pengguna sistem.</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center space-x-1 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100">
                        <i class="bi bi-arrow-left"></i><span>Kembali</span>
                    </a>
                    <a href="{{ route('admin.users.create') }}"
                       class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        <i class="bi bi-person-plus mr-1"></i>Tambah User
                    </a>
                </div>
            </div>

            <!-- Role Legend -->
            <div class="px-6 py-3 bg-slate-50 border-b flex flex-wrap gap-4 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    <strong>Super Admin</strong> - Full access (upload, delete, kelola user)
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <strong>Admin</strong> - Upload & lihat sertifikat
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                    <strong>Viewer</strong> - Hanya lihat sertifikat (read-only)
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($users as $user)
                        <tr class="hover:bg-gray-50 {{ $user->id === auth()->id() ? 'bg-blue-50/30' : '' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm {{ $user->isSuperAdmin() ? 'bg-gradient-to-br from-amber-400 to-orange-500' : 'bg-blue-600' }}">
                                        {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $user->name ?? '-' }}
                                            @if($user->id === auth()->id())
                                            <span class="text-xs text-blue-500">(Anda)</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $user->role_badge }}">
                                    {{ $user->role_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($user->is_active)
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-green-100 text-green-700">Aktif</span>
                                @else
                                <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-red-100 text-red-700">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.users.show', $user) }}"
                                       class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition" title="Detail">
                                        <i class="bi bi-person-lines-fill"></i>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="delete-user-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="delete-user-btn p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus"
                                                data-name="{{ $user->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ app()->bound('csp_nonce') ? app('csp_nonce') : '' }}">
document.querySelectorAll('.delete-user-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        if (confirm('Hapus user ' + this.dataset.name + '?')) {
            this.closest('form').submit();
        }
    });
});
</script>
@endpush
@endsection
