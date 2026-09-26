<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\RoleRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Khusus Super Admin (developer): kelola akun & peran.
 * Akun superadmin sendiri tidak dapat diubah/dihapus dari sini.
 */
class SuperAdminController extends Controller
{
    // Pusat kendali Super Admin: ringkasan + pintu ke semua fitur
    public function dashboard()
    {
        $roles = User::selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('sa.dashboard', [
            'stats' => [
                'pengguna' => User::count(),
                'anggota' => $roles['anggota'] ?? 0,
                'staff' => $roles['staff'] ?? 0,
                'admin' => $roles['admin'] ?? 0,
                'dokumen' => Document::count(),
                'views' => Document::sum('views_count'),
                'storage' => Document::sum('file_size') ?? 0,
                'pending' => RoleRequest::pending()->count(),
            ],
            'features' => Setting::FEATURES,
            'logs' => ActivityLog::with(['user', 'document'])->latest()->limit(8)->get(),
        ]);
    }

    public function users()
    {
        $users = User::orderBy('role')->orderBy('name')->paginate(20);

        return view('sa.users', compact('users'));
    }

    // Pusat pengaturan web: identitas + sakelar fitur
    public function settings()
    {
        return view('sa.settings');
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'foundation_name' => ['required', 'string', 'max:80'],
            'reg_domains' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'logo_reset' => ['nullable', 'boolean'],
        ], [
            'site_name.required' => 'Nama situs wajib diisi.',
            'foundation_name.required' => 'Nama yayasan wajib diisi.',
            'logo.image' => 'Logo wajib berupa file gambar.',
            'logo.mimes' => 'Logo harus berformat JPG, PNG, GIF, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
        ]);

        \App\Models\Setting::set('site_name', $data['site_name']);
        \App\Models\Setting::set('foundation_name', $data['foundation_name']);
        \App\Models\Setting::set('reg_domains', trim($data['reg_domains'] ?? ''));

        if ($request->boolean('logo_reset')) {
            $old = \App\Models\Setting::get('logo_path');
            if ($old) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
            }
            \App\Models\Setting::set('logo_path', null);
        } elseif ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $old = \App\Models\Setting::get('logo_path');
            if ($old) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
            }
            \App\Models\Setting::set('logo_path', $request->file('logo')->store('logos', 'public'));
        }

        foreach (array_keys(\App\Models\Setting::FEATURES) as $f) {
            \App\Models\Setting::set($f.'_enabled', $request->boolean('feat_'.$f) ? '1' : '0');
        }

        ActivityLog::record(auth()->id(), null, 'settings_update');

        return back()->with('ok', 'Pengaturan web diperbarui.');
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
