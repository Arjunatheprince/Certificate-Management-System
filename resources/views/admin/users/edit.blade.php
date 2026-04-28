@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Edit User</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
                    <i class="bi bi-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-6 space-y-5">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru <span class="text-gray-400 font-normal">(kosongkan jika tidak diubah)</span></label>
                    <input type="password" name="password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    @if($user->role === 'superadmin')
                        <input type="hidden" name="role" value="superadmin">
                        <div class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 flex items-center space-x-2">
                            <i class="bi bi-shield-fill-check text-red-500"></i>
                            <span>Super Admin - tidak dapat diubah</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Role Super Admin bersifat permanen dan hanya ada satu.</p>
                    @else
                        <select name="role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin - Upload & kelola sertifikat</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Role Viewer tidak memiliki akun pada sistem ini.</p>
                    @endif
                </div>
                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="w-5 h-5 text-blue-600 rounded"
                           {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                           {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                    <label for="is_active" class="text-sm font-medium text-gray-700">Akun Aktif</label>
                </div>
                <div class="flex space-x-4 pt-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        <i class="bi bi-check-lg mr-2"></i>Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
