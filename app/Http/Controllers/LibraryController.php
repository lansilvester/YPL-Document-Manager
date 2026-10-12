<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function index(Request $request): mixed
    {
        $filter = $request->query('koleksi', 'semua');

        $handbooks = Category::query()
            ->where('collection', 'handbook')
            ->orderBy('sort_order')
            ->get();

        // Fallback bila migrasi taxonomy belum jalan / seed belum ada.
        if ($handbooks->isEmpty()) {
            $handbooks = Category::orderBy('name')->limit(10)->get();
        }

        $collections = Category::query()
            ->whereIn('collection', ['governance', 'academic', 'boarding', 'staff', 'parent', 'public'])
            ->orderBy('sort_order')
            ->get();

        $docs = Document::query()->with(['category', 'user', 'tags'])
            ->where('visibility', 'public')->whereIn('status', Document::LIVE_STATUSES)
            ->when($filter !== 'semua', fn ($q) => $q->whereHas('category', fn ($c) => $c->where('collection', $filter)->orWhere('slug', $filter)))
            ->latest()->paginate(12)->withQueryString();

        return view('library.index', [
            'handbooks' => $handbooks,
            'collections' => $collections,
            'docs' => $docs,
            'filter' => $filter,
        ]);
    }

    public function show(Category $category): mixed
    {
        $docs = Document::query()->with(['user', 'tags'])
            ->where('visibility', 'public')->whereIn('status', Document::LIVE_STATUSES)
            ->where('category_id', $category->id)
            ->latest()->paginate(12);

        return view('library.show', [
            'category' => $category,
            'docs' => $docs,
        ]);
    }
}
