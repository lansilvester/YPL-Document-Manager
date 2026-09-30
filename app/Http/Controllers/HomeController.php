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

        $latest = Document::with(['category'])->where('visibility', 'public')
            ->latest()->limit(3)->get();

        // YPL Today: dokumen aktif dengan review terdekat (deadline nyata).
        $todayDocs = Document::with(['category'])->where('visibility', 'public')
            ->where('status', 'active')->whereNotNull('review_at')
            ->orderBy('review_at')->limit(3)->get();

        return view('home', [
            'latestDocs' => $latest,
            'todayDocs' => $todayDocs,
            'q' => $q,
            'stats' => [
                'dokumen' => Document::where('visibility', 'public')->count(),
                'kategori' => Category::count(),
                'views' => Document::where('visibility', 'public')->sum('views_count'),
            ],
        ]);
    }
}
