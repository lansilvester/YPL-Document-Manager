<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentRelation;
use App\Services\ImpactMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $docs = Document::query()->forCard()
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
        $docs = Document::query()
            ->where('visibility', 'public')->whereIn('status', Document::LIVE_STATUSES)
            ->where('category_id', $category->id)
            ->latest()->paginate(12);

        // Status bookmark untuk bab pertama — dipindah dari view (hindari query saat render).
        $first = $docs->first();
        $firstMarked = auth()->check() && $first
            ? Bookmark::where('user_id', auth()->id())->where('document_id', $first->id)->exists()
            : false;

        return view('library.show', [
            'category' => $category,
            'docs' => $docs,
            'firstMarked' => $firstMarked,
        ]);
    }

    /**
     * Peta Pengetahuan (BAB 12): eksplorasi graf knowledge lintas koleksi.
     * Bagian dari Library; halaman publik namun permission-aware —
     * dokumen privat tidak pernah bocor.
     */
    public function graph(Request $request): mixed
    {
        $user = auth()->user();
        $q = trim((string) $request->query('q', ''));
        $focusSlug = $request->query('dokumen');
        $depth = max(1, min(2, (int) $request->query('kedalaman', 2)));

        $focus = null;
        $graph = null;
        if ($focusSlug) {
            $focus = Document::query()->visibleTo($user)->where('slug', $focusSlug)->first();
            if ($focus) {
                $graph = ImpactMap::graph($focus, $user, $depth);
            }
        }

        // Pusat graf yang disarankan: dokumen dengan relasi terbanyak & terlihat.
        $centers = Document::query()
            ->forCard()
            ->visibleTo($user)
            ->whereIn('status', Document::LIVE_STATUSES)
            ->when($q !== '', fn ($query) => $query->searchAny($q))
            ->withCount(['outgoingRelations', 'incomingRelations'])
            ->orderByRaw('(outgoing_relations_count + incoming_relations_count) desc')
            ->orderByDesc('views_count')
            ->limit(12)
            ->get();

        return view('library.graph', [
            'q' => $q,
            'focus' => $focus,
            'focusSlug' => $focusSlug,
            'depth' => $depth,
            'graph' => $graph,
            'centers' => $centers,
            'stats' => $this->graphStats($user),
        ]);
    }

    /**
     * Statistik ringkas graf untuk tamu/pengguna yang berhak.
     *
     * @return array{dokumen: int, relasi: int, sistem: int}
     */
    private function graphStats(?object $user): array
    {
        $visibleIds = Document::query()->visibleTo($user)
            ->whereIn('status', Document::LIVE_STATUSES)
            ->pluck('id');

        $relations = DocumentRelation::query()
            ->where(fn ($query) => $query
                ->whereIn('document_id', $visibleIds)
                ->orWhereIn('related_id', $visibleIds))
            ->count();

        return [
            'dokumen' => $visibleIds->count(),
            'relasi' => $relations,
            'sistem' => DB::table('document_relations')->whereNotNull('system_id')->count(),
        ];
    }
}
