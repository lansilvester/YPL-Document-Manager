<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;

class GovernanceController extends Controller
{
    /**
     * Governance Dashboard — statistik & akses cepat.
     */
    public function dashboard(): mixed
    {
        $stats = Document::query()
            ->where('visibility', 'public')
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status = "active" THEN 1 END) as active,
                COUNT(CASE WHEN status = "draft" THEN 1 END) as draft,
                COUNT(CASE WHEN status = "review" THEN 1 END) as review,
                COUNT(CASE WHEN status = "approval" THEN 1 END) as approval,
                COUNT(CASE WHEN status = "review_due" THEN 1 END) as review_due,
                COUNT(CASE WHEN status = "archived" THEN 1 END) as archived,
                COUNT(CASE WHEN review_at IS NOT NULL AND review_at <= CURDATE() AND status = "active" THEN 1 END) as overdue_review
            ')
            ->first();

        $typeStats = Document::query()
            ->where('visibility', 'public')
            ->selectRaw('doc_type, status, COUNT(*) as count')
            ->groupBy('doc_type', 'status')
            ->get()
            ->groupBy('doc_type')
            ->map(fn ($items) => $items->pluck('count', 'status')->toArray());

        $upcomingReviews = Document::query()
            ->where('visibility', 'public')
            ->where('status', 'active')
            ->whereNotNull('review_at')
            ->where('review_at', '>=', now())
            ->where('review_at', '<=', now()->addDays(30))
            ->with(['category', 'user'])
            ->orderBy('review_at')
            ->limit(10)
            ->get();

        $recentChanges = DocumentVersion::query()
            ->with(['document', 'user'])
            ->latest()
            ->limit(10)
            ->get();

        return view('governance.dashboard', [
            'stats' => $stats,
            'typeStats' => $typeStats,
            'upcomingReviews' => $upcomingReviews,
            'recentChanges' => $recentChanges,
        ]);
    }

    /**
     * Daftar dokumen governance dengan filter lengkap.
     */
    public function index(Request $request): mixed
    {
        $filters = $request->only(['tipe', 'status', 'audiens', 'unit', 'q', 'sort']);

        $query = Document::query()
            ->with([
                'category',
                'user:id,name',
                'approver:id,name',
                'pic:id,name',
                'category:id,name,collection',
            ])
            ->where('visibility', 'public')
            ->when($filters['tipe'] ?? null, fn ($q, $v) => $q->where('doc_type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['audiens'] ?? null, fn ($q, $v) => $q->whereJsonContains('audience', $v))
            ->when($filters['unit'] ?? null, fn ($q, $v) => $q->where('unit', 'like', '%'.$v.'%'))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->searchAny($v));

        // Sorting
        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title'),
            'review_date' => $query->orderBy('review_at'),
            'effective_date' => $query->orderBy('effective_at'),
            default => $query->latest(),
        };

        $docs = $query->paginate(20)->withQueryString();

        // Filter options & counts
        $typeCounts = Document::where('visibility', 'public')
            ->selectRaw('doc_type, COUNT(*) as c')
            ->groupBy('doc_type')
            ->pluck('c', 'doc_type');

        $statusCounts = Document::where('visibility', 'public')
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $audienceOptions = Document::AUDIENCES;

        $unitOptions = Document::where('visibility', 'public')
            ->whereNotNull('unit')
            ->distinct('unit')
            ->pluck('unit')
            ->filter()
            ->sort()
            ->values();

        // Ensure all filter keys exist for the view
        $filters += ['q' => '', 'tipe' => '', 'status' => '', 'audiens' => '', 'unit' => '', 'sort' => ''];

        return view('governance.index', [
            'docs' => $docs,
            'filters' => $filters,
            'typeCounts' => $typeCounts,
            'statusCounts' => $statusCounts,
            'audienceOptions' => $audienceOptions,
            'unitOptions' => $unitOptions,
        ]);
    }

    /**
     * Detail dokumen dengan Document Passport lengkap.
     */
    public function show(Document $document): mixed
    {
        if (! $document->isVisibleTo(auth()->user())) {
            abort(403, 'Dokumen bersifat privat.');
        }

        $document->load([
            'category',
            'user:id,name,email,role',
            'approver:id,name,email',
            'pic:id,name,email',
            'tags',
            'outgoingRelations.related:id,title,doc_type,version,status,doc_code',
            'outgoingRelations.system:id,name,slug',
            'incomingRelations.document:id,title,doc_type,version,status,doc_code',
            'versions.user:id,name',
        ]);

        // Transitions allowed for current user
        $transitions = [];
        if (auth()->check()) {
            foreach (Document::TRANSITIONS[$document->status] ?? [] as $next) {
                if (auth()->user()->can('transition', [$document, $next])) {
                    $transitions[] = $next;
                }
            }
        }

        // Impact map: dokumen yang merujuk ke dokumen ini + dokumen yang direferensikan
        $impactMap = [
            'references' => $document->outgoingRelations->map(fn ($r) => [
                'type' => $r->relation_type,
                'target_type' => $r->system ? 'system' : 'document',
                'title' => $r->system?->name ?? $r->related?->title,
                'slug' => $r->system?->slug ?? $r->related?->slug,
                'route' => $r->system ? route('systems.show', $r->system) : route('documents.show', $r->related),
            ]),
            'referenced_by' => $document->incomingRelations->map(fn ($r) => [
                'type' => $r->relation_type,
                'source_title' => $r->document->title,
                'source_type' => $r->document->doc_type,
                'slug' => $r->document->slug,
            ]),
        ];

        // Version timeline
        $versions = $document->versions()
            ->with('user:id,name')
            ->latest()
            ->get();

        // Upcoming review
        $isOverdue = $document->review_at && $document->review_at < now() && $document->status === 'active';

        return view('governance.show', [
            'document' => $document,
            'transitions' => $transitions,
            'impactMap' => $impactMap,
            'versions' => $versions,
            'isOverdue' => $isOverdue,
        ]);
    }

    /**
     * Version history detail.
     */
    public function versions(Document $document): mixed
    {
        $versions = $document->versions()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(20);

        return view('governance.versions', [
            'document' => $document,
            'versions' => $versions,
        ]);
    }

    /**
     * Impact map / related documents visual.
     */
    public function impactMap(Document $document): mixed
    {
        if (! $document->isVisibleTo(auth()->user())) {
            abort(403);
        }

        $document->load([
            'outgoingRelations.related:id,title,doc_type,version,status,doc_code,category:id,name',
            'outgoingRelations.system:id,name,slug',
            'incomingRelations.document:id,title,doc_type,version,status,doc_code',
        ]);

        return view('governance.impact-map', [
            'document' => $document,
        ]);
    }

    /**
     * Export governance report (CSV/Excel placeholder).
     */
    public function export(Request $request): mixed
    {
        // TODO: Implement CSV/Excel export
        return back()->with('info', 'Fitur ekspor akan segera tersedia.');
    }
}
