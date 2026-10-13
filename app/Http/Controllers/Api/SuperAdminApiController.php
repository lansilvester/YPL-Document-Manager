<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Role;
use App\Models\RoleRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuperAdminApiController extends ApiController
{
    public function dashboard(): JsonResponse
    {
        $roles = User::selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        return $this->ok([
            'stats' => [
                'users' => User::count(),
                'anggota' => (int) ($roles['anggota'] ?? 0),
                'staff' => (int) ($roles['staff'] ?? 0),
                'admin' => (int) ($roles['admin'] ?? 0),
                'documents' => Document::count(),
                'views' => (int) Document::sum('views_count'),
                'storage' => (int) Document::sum('file_size'),
                'pending' => RoleRequest::pending()->count(),
            ],
            'features' => Setting::FEATURES,
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $data = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $users = User::orderBy('role')->orderBy('name')->paginate($data['per_page'] ?? 20)->withQueryString();

        return $this->ok(UserResource::collection($users));
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Akun Super Admin tidak dapat diubah. Hanya ada 1.');
        }

        $data = $request->validate([
            'role' => ['required', 'in:anggota,staff,admin'],
            'audience' => ['nullable', Rule::in(User::AUDIENCES)],
        ]);
        $user->update(['role' => $data['role'], 'audience' => $data['audience'] ?? null]);
        ActivityLog::record($request->user()->id, null, 'role_approve', ['user' => $user->email, 'role' => $data['role'], 'audience' => $data['audience'] ?? null, 'via' => 'api']);

        return $this->ok(new UserResource($user->fresh()), "Peran {$user->name} diubah menjadi {$data['role']}.");
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        if ($user->isSuperAdmin() || $user->id === $request->user()->id) {
            abort(403, 'Akun ini tidak dapat dihapus.');
        }

        ActivityLog::record($request->user()->id, null, 'user_delete', ['user' => $user->email, 'via' => 'api']);
        $user->delete();

        return $this->ok(null, 'Akun dihapus.');
    }

    public function settings(): JsonResponse
    {
        return $this->ok($this->settingsPayload());
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsPayload(): array
    {
        return [
            'site_name' => Setting::get('site_name'),
            'foundation_name' => Setting::get('foundation_name'),
            'reg_domains' => Setting::get('reg_domains'),
            'retention_days' => (int) Setting::get('retention_days', Document::RETENTION_DEFAULT_DAYS),
            'logo_url' => Setting::logoUrl(),
            'features' => collect(Setting::FEATURES)->mapWithKeys(fn ($label, $key) => [
                $key => ['label' => $label, 'enabled' => Setting::feature($key)],
            ]),
        ];
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'foundation_name' => ['required', 'string', 'max:80'],
            'reg_domains' => ['nullable', 'string', 'max:255'],
            'retention_days' => ['nullable', 'integer', 'min:30', 'max:3650'],
            'features' => ['nullable', 'array'],
            'features.*' => ['boolean'],
        ]);

        Setting::set('site_name', $data['site_name']);
        Setting::set('foundation_name', $data['foundation_name']);
        Setting::set('reg_domains', trim($data['reg_domains'] ?? ''));
        Setting::set('retention_days', (string) ($data['retention_days'] ?? Document::RETENTION_DEFAULT_DAYS));

        foreach (array_keys(Setting::FEATURES) as $f) {
            if (array_key_exists($f, $data['features'] ?? [])) {
                Setting::set($f.'_enabled', $data['features'][$f] ? '1' : '0');
            }
        }

        ActivityLog::record($request->user()->id, null, 'settings_update', ['via' => 'api']);

        return $this->ok($this->settingsPayload(), 'Pengaturan web diperbarui.');
    }

    public function roles(): JsonResponse
    {
        return $this->ok(RoleResource::collection(Role::orderBy('priority')->orderBy('name')->get()));
    }

    public function storeRole(Request $request): JsonResponse
    {
        $data = $this->validateRole($request);

        $role = Role::create($data);
        ActivityLog::record($request->user()->id, null, 'role_create', ['role' => $role->slug, 'via' => 'api']);

        return $this->created(new RoleResource($role), "Peran {$role->name} dibuat.");
    }

    public function updateRoleDefinition(Request $request, Role $role): JsonResponse
    {
        $data = $this->validateRole($request, $role);

        if ($role->is_system) {
            unset($data['slug']);
        }
        $role->update($data);
        ActivityLog::record($request->user()->id, null, 'role_update', ['role' => $role->slug, 'via' => 'api']);

        return $this->ok(new RoleResource($role), "Peran {$role->name} diperbarui.");
    }

    public function destroyRole(Role $role): JsonResponse
    {
        abort_if($role->is_system, 422, 'Peran sistem tidak dapat dihapus.');
        abort_if(User::where('role', $role->slug)->exists(), 422, 'Masih ada pengguna dengan peran ini.');

        $role->delete();

        return $this->ok(null, 'Peran dihapus.');
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
