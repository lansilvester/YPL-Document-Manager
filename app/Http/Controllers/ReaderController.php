<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Document;
use App\Models\ReadingNote;
use App\Models\ReadingPosition;
use App\Models\ReadingPref;
use App\Services\HtmlSanitizer;
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
        abort_unless($document->file_path || $document->usesStructuredReader(), 404, 'Dokumen belum memiliki berkas.');

        $document->load(['category', 'tags', 'user']);

        $user = auth()->user();
        $pref = $user?->readingPref ?? new ReadingPref(['theme' => 'light', 'zoom' => 100, 'layout' => 'double']);
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

        // Catatan milik sendiri (BAB 8) — satu query, tanpa N+1.
        $notes = $user
            ? ReadingNote::where('user_id', $user->id)->where('document_id', $document->id)
                ->orderBy('page')->orderBy('id')->get()
            : collect();

        // Structured HTML Reader (BAB 8/16) untuk dokumen bersumber editor.
        $safeContent = $document->usesStructuredReader()
            ? HtmlSanitizer::clean($document->content)
            : null;
        $sections = $safeContent ? $this->extractSections($safeContent) : ['html' => null, 'toc' => []];

        return view('reader.show', [
            'document' => $document,
            'pref' => $pref,
            'position' => $position,
            'isMarked' => $isMarked,
            'chapters' => $chapters,
            'recommendations' => $recommendations,
            'notes' => $notes,
            'safeContent' => $sections['html'],
            'sections' => $sections['toc'],
        ]);
    }

    /**
     * Tambahkan id anchor pada tiap <h2> agar bisa dijadikan daftar isi
     * structured reader, sekaligus mengembalikan daftar seksi (BAB 8).
     *
     * @return array{html: ?string, toc: array<int, array{id: string, label: string}>}
     */
    private function extractSections(string $html): array
    {
        $toc = [];
        $n = 0;
        $withIds = preg_replace_callback('/<h2(\s[^>]*)?>(.*?)<\/h2>/is', function (array $m) use (&$toc, &$n): string {
            $n++;
            $id = 'bagian-'.$n;
            $label = trim(preg_replace('/\s+/', ' ', strip_tags($m[2])));
            if ($label !== '') {
                $toc[] = ['id' => $id, 'label' => $label];
            }

            return '<h2 id="'.$id.'"'.($m[1] ?? '').'>'.$m[2].'</h2>';
        }, $html);

        return ['html' => $withIds, 'toc' => $toc];
    }
}
