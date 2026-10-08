<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Document;
use App\Models\ReadingPosition;
use App\Models\ReadingPref;
use App\Services\RecommendationEngine;

class ReaderController extends Controller
{
    /**
     * Phase 2 — Reader Mode imersif untuk Living Books.
     * Tanpa menghitung ulang views (detail sudah menghitung).
     */
    public function show(Document $document): mixed
    {
        if (! $document->isVisibleTo(auth()->user())) {
            abort(403, 'Dokumen bersifat privat.');
        }
        abort_unless($document->file_path, 404, 'Dokumen belum memiliki berkas.');

        $document->load(['category', 'tags', 'user']);

        $user = auth()->user();
        $pref = $user?->readingPref ?? new ReadingPref(['theme' => 'light', 'zoom' => 100, 'layout' => 'single']);
        $position = $user
            ? ReadingPosition::firstOrNew(
                ['user_id' => $user->id, 'document_id' => $document->id],
                ['page' => 1, 'percent' => 0]
            ) : null;
        $isMarked = $user ? Bookmark::where('user_id', $user->id)->where('document_id', $document->id)->exists() : false;

        // TOC: bab = dokumen lain dalam handbook/koleksi yang sama.
        $chapters = Document::query()->select(['id', 'title', 'slug', 'views_count'])
            ->where('visibility', 'public')
            ->where('category_id', $document->category_id)
            ->where('id', '!=', $document->id)
            ->latest()->limit(20)->get();

        // Rekomendasi di Reader (sidebar kanan)
        $recommendations = (new RecommendationEngine(5))->forDocument($document, auth()->user());

        return view('reader.show', compact('document', 'pref', 'position', 'isMarked', 'chapters', 'recommendations'));
    }
}
