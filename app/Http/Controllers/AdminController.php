<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\RoleRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'stats' => [
                'dokumen' => Document::count(),
                'publik' => Document::where('visibility', 'public')->count(),
                'views' => Document::sum('views_count'),
                'pengguna' => User::count(),
            ],
            'logs' => ActivityLog::with(['user', 'document'])->latest()->limit(30)->get(),
            'pendingApprovals' => RoleRequest::pending()->count(),
        ]);
    }

    public function categories()
    {
        return view('admin.categories', ['categories' => Category::withCount('documents')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string']]);
        Category::create(['name' => $data['name'], 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)), 'description' => $data['description'] ?? null]);

        return back()->with('ok', 'Kategori ditambahkan.');
    }

    public function destroyCategory(Category $category)
    {
        $category->delete();

        return back()->with('ok', 'Kategori dihapus.');
    }

    public function logs(Request $request)
    {
        $logs = ActivityLog::with(['user', 'document'])
            ->when($request->query('aksi'), fn ($s, $a) => $s->where('action', $a))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.logs', compact('logs'));
    }

    /**
     * Panel pengguna Admin: daftar user (tanpa Super Admin), cari, filter peran.
     * Pencarian memakai FULLTEXT (name,email) — indeks dibuat lewat migrasi — dengan fallback LIKE.
     */
    public function users(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $role = (string) $request->query('role');
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        // Indeks FULLTEXT (name,email) dibuat lewat migrasi; pakai bila sudah ada, kalau belum fallback LIKE.
        $fulltextReady = collect(Schema::getIndexes('users'))->contains(fn ($i) => ($i['name'] ?? null) === 'users_name_email_ft_idx');

        $users = User::withCount(['documents', 'favorites'])
            ->when(! $isSuperAdmin, fn ($s) => $s->where('role', '!=', 'superadmin'))
            ->when(in_array($role, ['anggota', 'staff', 'admin'], true), fn ($s) => $s->where('role', $role))
            ->when($q !== '', function ($s) use ($q, $fulltextReady) {
                if ($fulltextReady && mb_strlen($q) >= 3) {
                    $s->where(fn ($x) => $x
                        ->whereFullText(['name', 'email'], $q)
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%"));
                } else {
                    $s->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
                }
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roleCounts = User::selectRaw('role, COUNT(*) as total')
            ->where('role', '!=', 'superadmin')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('admin.users', compact('users', 'q', 'role', 'roleCounts', 'isSuperAdmin'));
    }

    /**
     * Edit user di panel Admin.
     * Aturan:
     *  - Admin TIDAK boleh menyentuh Super Admin (tak terlihat pun).
     *  - Admin hanya boleh menaikkan peran anggota/staff → admin.
     *  - Hanya Super Admin yang dapat mengubah peran; dan tidak pernah ke "superadmin".
     */
    public function updateUser(Request $request, User $user)
    {
        $editor = auth()->user();

        if ($editor->isAdmin() && ! $editor->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403, 'Anda tidak berhak mengubah akun Super Admin.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai pengguna lain.',
        ]);

        if ($editor->isSuperAdmin()) {
            $role = $request->validate(['role' => ['required', 'in:anggota,staff,admin']])['role'];
            // Konsisten dengan panel SA: akun Super Admin sendiri tidak diubah perannya.
            $data['role'] = $user->isSuperAdmin() ? $user->role : $role;
        } else {
            // Admin: hanya promosi → admin; admin yang sudah admin tidak berubah.
            if ($user->role !== 'admin') {
                $data['role'] = 'admin';
            }
        }

        $user->update($data);
        ActivityLog::record($editor->id, null, 'user_update', ['user' => $user->email, 'role' => $data['role'] ?? $user->role]);

        return back()->with('ok', "Akun {$user->name} diperbarui.");
    }

    public function destroy(User $user)
    {
        $editor = auth()->user();

        if ($user->id === $editor->id) {
            abort(403, 'Tidak dapat menghapus akun sendiri.');
        }
        if ($user->isSuperAdmin()) {
            abort(403, 'Akun Super Admin tidak dapat dihapus.');
        }

        ActivityLog::record($editor->id, null, 'user_delete', ['user' => $user->email]);
        $user->delete();

        return back()->with('ok', 'Akun dihapus.');
    }
}
