<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $cat = $request->query('kategori');

        $docs = Document::query()->with(['category', 'user', 'tags'])
            ->where('visibility', 'public')
            ->when($q, fn ($s) => $s->search($q))
            ->when($cat, fn ($s) => $s->whereHas('category', fn ($c) => $c->where('slug', $cat)))
            ->latest()->paginate(12)->withQueryString();

        $popular = Document::where('visibility', 'public')
            ->orderByDesc('views_count')->limit(5)->get();

        return view('home', [
            'docs' => $docs,
            'popular' => $popular,
            'categories' => Category::orderBy('name')->get(),
            'q' => $q,
            'activeCat' => $cat,
            'stats' => [
                'dokumen' => Document::where('visibility', 'public')->count(),
                'kategori' => Category::count(),
                'views' => Document::where('visibility', 'public')->sum('views_count'),
            ],
        ]);
    }
}
