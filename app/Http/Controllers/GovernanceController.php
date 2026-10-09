<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\ImpactMap;
use Illuminate\Http\Request;

class GovernanceController extends Controller
{
    /**
     * Daftar dokumen governance dengan filter lengkap.
     */
    public function index(Request $request): mixed
    {
        $filters = $request->only(['tipe', 'status', 'audiens', 'unit', 'q', 'sort']);

        $query = Document::query()
            ->with([
                'category:id,name,collection',
                'user:id,name',
                'approver:id,name',
                'pic:id,name',
                'tags:id,name',
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

        $overdueCount = Document::where('visibility', 'public')
            ->where('status', 'active')
            ->whereNotNull('review_at')
            ->whereDate('review_at', '<', now()->toDateString())
            ->count();

        // Ensure all filter keys exist for the view
        $filters += ['q' => '', 'tipe' => '', 'status' => '', 'audiens' => '', 'unit' => '', 'sort' => ''];

        return view('governance.index', [
            'docs' => $docs,
            'filters' => $filters,
            'typeCounts' => $typeCounts,
            'statusCounts' => $statusCounts,
            'audienceOptions' => $audienceOptions,
            'unitOptions' => $unitOptions,
            'overdueCount' => $overdueCount,
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
            'outgoingRelations.related:id,title,slug,doc_type,version,status,doc_code',
            'outgoingRelations.system:id,name,slug',
            'incomingRelations.document:id,title,slug,doc_type,version,status,doc_code',
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

        // Impact map: dokumen yang merujuk ke dokumen ini + dokumen yang direferensikan.
        // Lewati relasi yatim (target terhapus) agar route() tak error.
        $impactMap = ImpactMap::for($document);

        // Upcoming review
        $isOverdue = $document->review_at && $document->review_at < now() && $document->status === 'active';

        return view('governance.show', [
            'document' => $document,
            'transitions' => $transitions,
            'impactMap' => $impactMap,
            'isOverdue' => $isOverdue,
        ]);
    }

    /**
     * Version history detail.
     */
    public function versions(Document $document): mixed
    {
        if (! $document->isVisibleTo(auth()->user())) {
            abort(403, 'Dokumen bersifat privat.');
        }

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
            'outgoingRelations.related:id,title,slug,doc_type,version,status,doc_code',
            'outgoingRelations.system:id,name,slug',
            'incomingRelations.document:id,title,slug,doc_type,version,status,doc_code',
        ]);

        return view('governance.impact-map', [
            'document' => $document,
            'impactMap' => ImpactMap::for($document),
        ]);
    }

    /**
     * Export governance report (CSV streaming, chunked, permission-filtered).
     */
    public function export(Request $request): mixed
    {
        $data = $request->validate([
            'tipe' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'max:20'],
            'audiens' => ['nullable', 'string', 'max:30'],
            'unit' => ['nullable', 'string', 'max:60'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $user = $request->user();
        $filename = 'governance-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data, $user) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel baca UTF-8
            fputcsv($out, ['Doc ID', 'Judul', 'Tipe', 'Versi', 'Status', 'Klasifikasi', 'Owner', 'Approver', 'PIC', 'Unit', 'Effective', 'Review', 'Kategori']);

            Document::query()->with(['category:id,name', 'user:id,name', 'approver:id,name', 'pic:id,name'])
                ->visibleTo($user)
                ->when($data['tipe'] ?? null, fn ($q, $v) => $q->where('doc_type', $v))
                ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
                ->when($data['audiens'] ?? null, fn ($q, $v) => $q->whereJsonContains('audience', $v))
                ->when($data['unit'] ?? null, fn ($q, $v) => $q->where('unit', 'like', '%'.$v.'%'))
                ->when($data['q'] ?? null, fn ($q, $v) => $q->searchAny($v))
                ->orderBy('id')
                ->chunk(500, function ($docs) use ($out) {
                    foreach ($docs as $d) {
                        fputcsv($out, [
                            $d->doc_code,
                            $d->title,
                            $d->doc_type,
                            $d->version,
                            $d->status,
                            $d->classification,
                            $d->user?->name,
                            $d->approver?->name,
                            $d->pic?->name ?? $d->pic_name,
                            $d->unit,
                            $d->effective_at?->format('Y-m-d'),
                            $d->review_at?->format('Y-m-d'),
                            $d->category?->name,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
