<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\RoleRequestResource;
use App\Http\Resources\UnitResource;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Document;
use App\Models\RoleRequest;
use App\Models\ServiceRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminApiController extends ApiController
{
    public function dashboard(): JsonResponse
    {
        return $this->ok([
            'stats' => [
                'documents' => Document::count(),
                'public' => Document::where('visibility', 'public')->count(),
                'views' => (int) Document::sum('views_count'),
                'users' => User::count(),
            ],
            'pending_approvals' => RoleRequest::pending()->count(),
            'recent_logs' => ActivityLogResource::collection(
                ActivityLog::with(['user', 'document'])->latest()->limit(30)->get()
            ),
        ]);
    }

    public function analytics(): JsonResponse
    {
        $since = now()->subDays(30);

        $byAction = ActivityLog::query()->where('created_at', '>=', $since)
            ->selectRaw('action, COUNT(*) as total')->groupBy('action')->pluck('total', 'action');

        $trend = ActivityLog::query()->where('created_at', '>=', now()->subDays(14))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')->orderBy('day')->get();

        $topDocs = Document::query()->select(['id', 'title', 'slug', 'views_count', 'downloads_count'])
            ->orderByDesc('views_count')->limit(8)->get();

        return $this->ok([
            'window_days' => 30,
            'by_action' => $byAction,
            'trend' => $trend,
            'top_documents' => $topDocs,
            'services' => ServiceRequest::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function logs(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = ActivityLog::with(['user', 'document'])
            ->when($data['action'] ?? null, fn ($s, $a) => $s->where('action', $a))
            ->latest()->paginate($data['per_page'] ?? 30)->withQueryString();

        return $this->ok(ActivityLogResource::collection($logs));
    }

    public function users(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'role' => ['nullable', 'in:anggota,staff,admin'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $q = trim($data['q'] ?? '');
        $isSuperAdmin = $request->user()->isSuperAdmin();

        $fulltextReady = collect(Schema::getIndexes('users'))
            ->contains(fn ($i) => ($i['name'] ?? null) === 'users_name_email_ft_idx');

        $users = User::withCount(['documents', 'bookmarks'])
            ->when(! $isSuperAdmin, fn ($s) => $s->where('role', '!=', 'superadmin'))
            ->when($data['role'] ?? null, fn ($s, $r) => $s->where('role', $r))
            ->when($q !== '', function ($s) use ($q, $fulltextReady) {
                if ($fulltextReady && mb_strlen($q) >= 3) {
                    $s->where(fn ($x) => $x->whereFullText(['name', 'email'], $q)
                        ->orWhere('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
                } else {
                    $s->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
                }
            })
            ->orderBy('name')->paginate($data['per_page'] ?? 15)->withQueryString();

        return $this->ok(UserResource::collection($users));
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $editor = $request->user();

        if ($editor->isAdmin() && ! $editor->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403, 'Anda tidak berhak mengubah akun Super Admin.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'audience' => ['nullable', Rule::in(User::AUDIENCES)],
        ]);
        $data['audience'] = $data['audience'] ?? null;

        if ($editor->isSuperAdmin()) {
            $role = $request->validate(['role' => ['required', 'in:anggota,staff,admin']])['role'];
            $data['role'] = $user->isSuperAdmin() ? $user->role : $role;
        } else {
            if ($user->role !== 'admin') {
                $data['role'] = 'admin';
            }
        }

        $user->update($data);
        ActivityLog::record($editor->id, null, 'user_update', ['user' => $user->email, 'role' => $data['role'] ?? $user->role, 'audience' => $data['audience'], 'via' => 'api']);

        return $this->ok(new UserResource($user->fresh()), "Akun {$user->name} diperbarui.");
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        $editor = $request->user();

        if ($user->id === $editor->id) {
            abort(403, 'Tidak dapat menghapus akun sendiri.');
        }
        if ($user->isSuperAdmin()) {
            abort(403, 'Akun Super Admin tidak dapat dihapus.');
        }

        ActivityLog::record($editor->id, null, 'user_delete', ['user' => $user->email, 'via' => 'api']);
        $user->delete();

        return $this->ok(null, 'Akun dihapus.');
    }

    // ------------------------------------------------------------------
    // Kategori
    // ------------------------------------------------------------------

    public function categories(): JsonResponse
    {
        return $this->ok(CategoryResource::collection(
            Category::withCount('documents')->orderBy('sort_order')->orderBy('name')->get()
        ));
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:10', 'unique:categories,code'],
            'collection' => ['nullable', 'string', Rule::in(['handbook', 'governance', 'academic', 'boarding', 'staff', 'parent', 'public'])],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'code' => $data['code'] ?? null,
            'collection' => $data['collection'] ?? null,
            'color' => $data['color'] ?? null,
            'sort_order' => $data['sort_order'] ?? 99,
        ]);

        return $this->created(new CategoryResource($category), 'Kategori ditambahkan.');
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'code' => ['nullable', 'string', 'max:10', Rule::unique('categories', 'code')->ignore($category->id)],
            'collection' => ['nullable', 'string', Rule::in(['handbook', 'governance', 'academic', 'boarding', 'staff', 'parent', 'public'])],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $category->update($data);
        ActivityLog::record($request->user()->id, null, 'category_update', ['category' => $category->code ?? $category->slug, 'via' => 'api']);

        return $this->ok(new CategoryResource($category), 'Kategori diperbarui.');
    }

    public function destroyCategory(Request $request, Category $category): JsonResponse
    {
        abort_if($category->collection === 'handbook', 422, 'Handbook B01–B10 dilindungi dan tidak dapat dihapus.');
        abort_if($category->documents()->exists(), 422, 'Tidak dapat dihapus: masih ada dokumen di dalamnya.');

        $category->delete();

        return $this->ok(null, 'Kategori dihapus.');
    }

    // ------------------------------------------------------------------
    // Unit
    // ------------------------------------------------------------------

    public function units(): JsonResponse
    {
        return $this->ok(UnitResource::collection(
            Unit::withCount('contacts')->orderBy('sort_order')->orderBy('name')->get()
        ));
    }

    public function storeUnit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $unit = Unit::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        ActivityLog::record($request->user()->id, null, 'unit_create', ['unit' => $unit->name, 'via' => 'api']);

        return $this->created(new UnitResource($unit), "Unit {$unit->name} ditambahkan.");
    }

    public function updateUnit(Request $request, Unit $unit): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $unit->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        ActivityLog::record($request->user()->id, null, 'unit_update', ['unit' => $unit->name, 'via' => 'api']);

        return $this->ok(new UnitResource($unit), "Unit {$unit->name} diperbarui.");
    }

    public function destroyUnit(Request $request, Unit $unit): JsonResponse
    {
        ActivityLog::record($request->user()->id, null, 'unit_delete', ['unit' => $unit->name, 'via' => 'api']);
        $unit->delete();

        return $this->ok(null, 'Unit dihapus.');
    }

    // ------------------------------------------------------------------
    // Kontak PIC
    // ------------------------------------------------------------------

    public function contacts(): JsonResponse
    {
        return $this->ok(ContactResource::collection(
            Contact::with('unit:id,name,slug')->orderBy('sort_order')->orderBy('name')->get()
        ));
    }

    public function storeContact(Request $request): JsonResponse
    {
        $contact = Contact::create($this->validatedContact($request));
        ActivityLog::record($request->user()->id, null, 'contact_create', ['contact' => $contact->name, 'via' => 'api']);

        return $this->created(new ContactResource($contact->load('unit:id,name,slug')), "Kontak {$contact->name} ditambahkan.");
    }

    public function updateContact(Request $request, Contact $contact): JsonResponse
    {
        $contact->update($this->validatedContact($request));
        ActivityLog::record($request->user()->id, null, 'contact_update', ['contact' => $contact->name, 'via' => 'api']);

        return $this->ok(new ContactResource($contact->load('unit:id,name,slug')), "Kontak {$contact->name} diperbarui.");
    }

    public function destroyContact(Request $request, Contact $contact): JsonResponse
    {
        ActivityLog::record($request->user()->id, null, 'contact_delete', ['contact' => $contact->name, 'via' => 'api']);
        $contact->delete();

        return $this->ok(null, 'Kontak dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedContact(Request $request): array
    {
        $data = $request->validate([
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'position' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
        $data['unit_id'] = $data['unit_id'] ?? null;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    // ------------------------------------------------------------------
    // Persetujuan peran
    // ------------------------------------------------------------------

    public function roleRequests(): JsonResponse
    {
        return $this->ok(RoleRequestResource::collection(
            RoleRequest::with(['user', 'reviewer'])->latest()->paginate(20)->withQueryString()
        ));
    }

    public function approveRoleRequest(Request $request, RoleRequest $roleRequest): JsonResponse
    {
        $this->authorizeReview($request, $roleRequest);

        if ($roleRequest->status !== 'pending') {
            abort(422, 'Pengajuan sudah ditinjau.');
        }

        if ($roleRequest->requested_audience) {
            $roleRequest->user->update(['audience' => $roleRequest->requested_audience]);
            $log = ['user' => $roleRequest->user->email, 'audience' => $roleRequest->requested_audience];
        } else {
            $roleRequest->user->update(['role' => $roleRequest->requested_role]);
            $log = ['user' => $roleRequest->user->email, 'role' => $roleRequest->requested_role];
        }

        $roleRequest->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        ActivityLog::record($request->user()->id, null, 'role_approve', $log + ['via' => 'api']);

        return $this->ok(new RoleRequestResource($roleRequest->fresh()->load(['user', 'reviewer'])), 'Pengajuan disetujui.');
    }

    public function rejectRoleRequest(Request $request, RoleRequest $roleRequest): JsonResponse
    {
        $this->authorizeReview($request, $roleRequest);

        if ($roleRequest->status !== 'pending') {
            abort(422, 'Pengajuan sudah ditinjau.');
        }

        $roleRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return $this->ok(new RoleRequestResource($roleRequest->fresh()->load(['user', 'reviewer'])), 'Pengajuan ditolak.');
    }

    private function authorizeReview(Request $request, RoleRequest $roleRequest): void
    {
        if ($roleRequest->requested_role === 'admin' && ! $request->user()->isSuperAdmin()) {
            abort(403, 'Peningkatan ke peran admin hanya boleh disetujui Super Admin.');
        }
    }
}
