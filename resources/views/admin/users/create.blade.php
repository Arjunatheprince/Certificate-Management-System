@extends('layouts.app')
@section('title', 'Tambah User')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('layouts.navbar')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('layouts.notification')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Tambah User Baru</h2>
                    <p class="text-sm text-gray-500 mt-1">Tentukan role sesuai hak akses yang dibutuhkan</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-1 text-sm text-gray-500 hover:text-gray-700">
                    <i class="bi bi-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" class="p-6 space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Pilih Role</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin - Upload & kelola sertifikat</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Super Admin hanya ada satu. Viewer tidak memiliki akun.</p>
                </div>
                <div class="flex space-x-4 pt-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        <i class="bi bi-person-plus mr-2"></i>Buat User
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
