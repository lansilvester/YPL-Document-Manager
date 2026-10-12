<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Role;
use App\Models\RoleRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

        Setting::set('site_name', $data['site_name']);
        Setting::set('foundation_name', $data['foundation_name']);
        Setting::set('reg_domains', trim($data['reg_domains'] ?? ''));

        if ($request->boolean('logo_reset')) {
            $old = Setting::get('logo_path');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            Setting::set('logo_path', null);
        } elseif ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $old = Setting::get('logo_path');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            Setting::set('logo_path', $request->file('logo')->store('logos', 'public'));
        }

        foreach (array_keys(Setting::FEATURES) as $f) {
            Setting::set($f.'_enabled', $request->boolean('feat_'.$f) ? '1' : '0');
        }

        ActivityLog::record(auth()->id(), null, 'settings_update');

        return back()->with('ok', 'Pengaturan web diperbarui.');
    }

    public function updateRole(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Akun Super Admin tidak dapat diubah. Hanya ada 1.');
        }

        $data = $request->validate([
            'role' => ['required', 'in:anggota,staff,admin'],
            'audience' => ['nullable', Rule::in(User::AUDIENCES)],
        ]);
        $user->update(['role' => $data['role'], 'audience' => $data['audience'] ?? null]);
        ActivityLog::record(auth()->id(), null, 'role_approve', ['user' => $user->email, 'role' => $data['role'], 'audience' => $data['audience'] ?? null]);

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

    /**
     * Role-aware BAB 11 (khusus Super Admin): definisi peran,
     * scope koleksi, dan prioritas homepage.
     */
    public function roles()
    {
        $userCounts = User::selectRaw('role, COUNT(*) as c')->groupBy('role')->pluck('c', 'role');
        $roles = Role::orderBy('priority')->orderBy('name')->get();

        return view('sa.roles', [
            'roles' => $roles,
            'userCounts' => $userCounts,
            'stats' => [
                'total' => $roles->count(),
                'system' => $roles->where('is_system')->count(),
                'audience' => $roles->where('is_system', false)->count(),
                'mapped' => $userCounts->sum(),
            ],
        ]);
    }

    public function storeRole(Request $request)
    {
        $data = $this->validateRole($request);

        $role = Role::create($data);
        ActivityLog::record(auth()->id(), null, 'role_create', ['role' => $role->slug]);

        return back()->with('ok', "Peran {$role->name} dibuat.");
    }

    public function updateRoleDefinition(Request $request, Role $role)
    {
        $data = $this->validateRole($request, $role);

        // Slug peran sistem dikunci agar rujukan kode stabil.
        if ($role->is_system) {
            unset($data['slug']);
        }
        $role->update($data);
        ActivityLog::record(auth()->id(), null, 'role_update', ['role' => $role->slug]);

        return back()->with('ok', "Peran {$role->name} diperbarui.");
    }

    public function destroyRole(Role $role)
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'Peran sistem tidak dapat dihapus.']);
        }
        if (User::where('role', $role->slug)->exists()) {
            return back()->withErrors(['role' => 'Masih ada pengguna dengan peran ini. Pindahkan dulu penggunanya.']);
        }
        $role->delete();

        return back()->with('ok', 'Peran dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9_]+$/', $role ? Rule::unique('roles', 'slug')->ignore($role->id) : 'unique:roles,slug'],
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'integer', 'min:0', 'max:999'],
            'collections' => ['nullable', 'array'],
            'collections.*' => [Rule::in(Role::COLLECTIONS)],
            'quick' => ['nullable', 'array'],
            'quick.*' => [Rule::in(Role::QUICK_ACTIONS)],
            'explore' => ['nullable', 'array'],
            'explore.*' => [Rule::in(Role::EXPLORE)],
        ]);

        return [
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'scopes' => ['collections' => array_values($data['collections'] ?? Role::COLLECTIONS)],
            'homepage' => empty($data['quick']) && empty($data['explore']) ? null
                : ['quick' => array_values($data['quick'] ?? []), 'explore' => array_values($data['explore'] ?? [])],
        ];
    }
}
