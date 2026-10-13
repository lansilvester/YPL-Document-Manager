<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DocumentResource;
use App\Http\Resources\DocumentVersionResource;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\DocumentVersion;
use App\Models\Event;
use App\Models\Faq;
use App\Models\System;
use App\Services\ImpactMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class GovernanceApiController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'tipe' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'max:20'],
            'audiens' => ['nullable', 'string', 'max:30'],
            'unit' => ['nullable', 'string', 'max:60'],
            'q' => ['nullable', 'string', 'max:200'],
            'sort' => ['nullable', 'in:latest,oldest,title,review_date,effective_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Document::query()->visibleTo($request->user())
            ->with(['category:id,name,collection', 'user:id,name'])
            ->when($data['tipe'] ?? null, fn ($q, $v) => $q->where('doc_type', $v))
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($data['audiens'] ?? null, fn ($q, $v) => $q->whereJsonContains('audience', $v))
            ->when($data['unit'] ?? null, fn ($q, $v) => $q->where('unit', 'like', '%'.$v.'%'))
            ->when($data['q'] ?? null, fn ($q, $v) => $q->searchAny($v));

        match ($data['sort'] ?? 'latest') {
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title'),
            'review_date' => $query->orderBy('review_at'),
            'effective_date' => $query->orderBy('effective_at'),
            default => $query->latest(),
        };

        return DocumentResource::collection($query->paginate($data['per_page'] ?? 20)->withQueryString());
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403, 'Dokumen bersifat privat.');

        $document->load([
            'category',
            'user:id,name,email,role',
            'approver:id,name,email',
            'pic:id,name,email',
            'tags',
            'outgoingRelations.related:id,title,slug,doc_type,version,status,doc_code,visibility,user_id',
            'outgoingRelations.system:id,name,slug',
            'outgoingRelations.event:id,title,slug,visibility',
            'outgoingRelations.faq:id,question,slug,visibility',
            'incomingRelations.document:id,title,slug,doc_type,version,status,doc_code,visibility,user_id',
        ]);

        return $this->ok([
            'document' => new DocumentResource($document),
            'relations' => [
                'outgoing' => $document->outgoingRelations->map(fn ($r) => [
                    'id' => $r->id,
                    'type' => $r->relation_type,
                    'related_document' => $r->related ? [
                        'title' => $r->related->title,
                        'slug' => $r->related->slug,
                        'doc_type' => $r->related->doc_type,
                    ] : null,
                    'system' => $r->system ? ['name' => $r->system->name, 'slug' => $r->system->slug] : null,
                    'event' => $r->event ? ['title' => $r->event->title, 'slug' => $r->event->slug] : null,
                    'faq' => $r->faq ? ['question' => $r->faq->question, 'slug' => $r->faq->slug] : null,
                ])->values(),
                'incoming' => $document->incomingRelations->map(fn ($r) => [
                    'id' => $r->id,
                    'type' => $r->relation_type,
                    'document' => $r->document ? [
                        'title' => $r->document->title,
                        'slug' => $r->document->slug,
                        'doc_type' => $r->document->doc_type,
                    ] : null,
                ])->values(),
            ],
        ]);
    }

    public function versions(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403, 'Dokumen bersifat privat.');

        $versions = $document->versions()->with('user:id,name')->latest()->paginate(20);

        return $this->ok(DocumentVersionResource::collection($versions));
    }

    public function impactMap(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403, 'Dokumen bersifat privat.');

        $depth = (int) $request->query('kedalaman', 2);

        return $this->ok([
            'impact_map' => ImpactMap::for($document, $request->user()),
            'graph' => ImpactMap::graph($document, $request->user(), max(1, min(2, $depth))),
            'depth' => max(1, min(2, $depth)),
        ]);
    }

    public function transition(Request $request, Document $document): JsonResponse
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'in:'.implode(',', Document::STATUSES)],
            'change_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->authorize('transition', [$document, $data['to']]);

        $from = $document->status;
        DB::transaction(function () use ($document, $data, $from, $request) {
            DocumentVersion::create([
                'document_id' => $document->id,
                'user_id' => $request->user()->id,
                'version' => $document->version,
                'from_status' => $from,
                'to_status' => $data['to'],
                'change_note' => $data['change_note'] ?? null,
                'snapshot' => $document->snapshot(),
            ]);
            $document->update(array_filter([
                'status' => $data['to'],
                'approver_id' => $data['to'] === 'active' ? $request->user()->id : $document->approver_id,
            ]));
        });
        ActivityLog::record($request->user()->id, $document->id, 'transition', ['from' => $from, 'to' => $data['to']]);

        return $this->ok(new DocumentResource($document->fresh()), "Status berubah: {$from} → {$data['to']}.");
    }

    public function attachRelation(Request $request, Document $document): JsonResponse
    {
        $this->authorize('relate', $document);

        $data = $request->validate([
            'related_slug' => ['nullable', 'string', 'max:255', 'required_without_all:system_slug,event_slug,faq_slug', 'exists:documents,slug'],
            'system_slug' => ['nullable', 'string', 'max:60', 'required_without_all:related_slug,event_slug,faq_slug', 'exists:systems,slug'],
            'event_slug' => ['nullable', 'string', 'max:120', 'required_without_all:related_slug,system_slug,faq_slug', 'exists:events,slug'],
            'faq_slug' => ['nullable', 'string', 'max:120', 'required_without_all:related_slug,system_slug,event_slug', 'exists:faqs,slug'],
            'relation_type' => ['nullable', 'string', 'in:'.implode(',', DocumentRelation::TYPES)],
        ]);
        $data['relation_type'] ??= 'related';

        $targets = array_filter([
            $data['related_slug'] ?? null,
            $data['system_slug'] ?? null,
            $data['event_slug'] ?? null,
            $data['faq_slug'] ?? null,
        ]);
        abort_if(count($targets) !== 1, 422, 'Pilih tepat satu target relasi.');

        if (! empty($data['related_slug'])) {
            $related = Document::where('slug', $data['related_slug'])->firstOrFail();
            abort_if($related->id === $document->id, 422, 'Dokumen tidak dapat direlasikan ke dirinya sendiri.');
            $relation = DocumentRelation::firstOrCreate(
                ['document_id' => $document->id, 'related_id' => $related->id, 'system_id' => null, 'event_id' => null, 'faq_id' => null],
                ['relation_type' => $data['relation_type']]
            );
        } elseif (! empty($data['system_slug'])) {
            $system = System::where('slug', $data['system_slug'])->firstOrFail();
            $relation = DocumentRelation::firstOrCreate(
                ['document_id' => $document->id, 'related_id' => null, 'system_id' => $system->id, 'event_id' => null, 'faq_id' => null],
                ['relation_type' => 'system']
            );
        } elseif (! empty($data['event_slug'])) {
            $event = Event::where('slug', $data['event_slug'])->firstOrFail();
            $relation = DocumentRelation::firstOrCreate(
                ['document_id' => $document->id, 'related_id' => null, 'system_id' => null, 'event_id' => $event->id, 'faq_id' => null],
                ['relation_type' => 'calendar']
            );
        } else {
            $faq = Faq::where('slug', $data['faq_slug'])->firstOrFail();
            $relation = DocumentRelation::firstOrCreate(
                ['document_id' => $document->id, 'related_id' => null, 'system_id' => null, 'event_id' => null, 'faq_id' => $faq->id],
                ['relation_type' => 'faq']
            );
        }

        return $this->created(['id' => $relation->id], 'Relasi pengetahuan ditambahkan.');
    }

    public function detachRelation(Request $request, Document $document, DocumentRelation $relation): JsonResponse
    {
        abort_unless($relation->document_id === $document->id, 404);
        $this->authorize('relate', $document);
        $relation->delete();

        return $this->ok(null, 'Relasi pengetahuan dihapus.');
    }
}
