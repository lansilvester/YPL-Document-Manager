<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'stats' => [
                'dokumen' => Document::count(),
                'publik' => Document::where('visibility', 'public')->count(),
                'views' => Document::sum('views_count'),
                'pengguna' => \App\Models\User::count(),
            ],
            'logs' => ActivityLog::with(['user', 'document'])->latest()->limit(30)->get(),
            'pendingApprovals' => \App\Models\RoleRequest::pending()->count(),
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
}
