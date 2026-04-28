<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id')->get();
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $logs = ActivityLog::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Statistik per aksi
        $stats = ActivityLog::where('user_id', $user->id)
            ->selectRaw('action, count(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action');

        // Sertifikat yang di-upload oleh user ini (via created_by)
        $certificates = \App\Models\Certificate::with('student')
            ->where('created_by', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.users.show', compact('user', 'logs', 'stats', 'certificates'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|in:admin',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => true,
        ]);

        ActivityLog::record('create_user', $user->email, "Membuat user baru dengan role {$user->role_label}");

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} berhasil dibuat.");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required|in:superadmin,admin',
        ]);

        // Kunci role superadmin — tidak bisa diubah
        if ($user->role === 'superadmin') {
            $request->merge(['role' => 'superadmin']);
        }

        // Prevent demoting self
        if ($user->id === auth()->id() && $request->role !== 'superadmin') {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah role diri sendiri.']);
        }

        $oldRole = $user->role_label;
        $user->update([
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
            'is_active' => $request->has('is_active'),
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6|confirmed']);
            $user->update(['password' => Hash::make($request->password)]);
        }

        ActivityLog::record('update_user', $user->email, "Update user dari {$oldRole} ke {$user->role_label}");

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        ActivityLog::record('delete_user', $user->email, "Menghapus user {$user->name} ({$user->role_label})");
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "User berhasil dihapus.");
    }
}
