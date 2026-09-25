<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Khusus Super Admin (developer): kelola akun & peran.
 * Akun superadmin sendiri tidak dapat diubah/dihapus dari sini.
 */
class SuperAdminController extends Controller
{
    public function users()
    {
        $users = User::orderBy('role')->orderBy('name')->paginate(20);

        return view('sa.users', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Akun Super Admin tidak dapat diubah. Hanya ada 1.');
        }

        $data = $request->validate(['role' => ['required', 'in:anggota,staff,admin']]);
        $user->update(['role' => $data['role']]);
        ActivityLog::record(auth()->id(), null, 'role_approve', ['user' => $user->email, 'role' => $data['role']]);

        return back()->with('ok', "Peran {$user->name} diubah menjadi {$data['role']}.");
    }

    public function destroy(User $user)
    {
        if ($user->isSuperAdmin() || $user->id === auth()->id()) {
            abort(403, 'Akun ini tidak dapat dihapus.');
        }

        ActivityLog::record(auth()->id(), null, 'user_delete', ['user' => $user->email]);
        $user->delete();

        return back()->with('ok', 'Akun dihapus.');
    }
}
