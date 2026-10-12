<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\Event;
use App\Models\Faq;
use App\Models\System;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Membangun struktur impact map dari relasi yang sudah di-eager-load.
 * Dipakai GovernanceController@show dan DocumentController@show agar konsisten.
 *
 * @return array{references: Collection, referenced_by: Collection}
 */
class ImpactMap
{
    public static function for(Document $document, ?User $user = null): array
    {
        return [
            'references' => $document->outgoingRelations
                ->filter(fn ($r) => $r->system
                    || ($r->event && $r->event->isVisibleTo($user))
                    || ($r->faq && $r->faq->isVisibleTo($user))
                    || ($r->related && $r->related->isVisibleTo($user)))
                ->map(fn ($r) => [
                    'type' => $r->relation_type,
                    'target_type' => $r->system ? 'system' : ($r->event ? 'event' : ($r->faq ? 'faq' : 'document')),
                    'title' => $r->system?->name ?? $r->event?->title ?? $r->faq?->question ?? $r->related?->title,
                    'slug' => $r->system?->slug ?? $r->event?->slug ?? $r->faq?->slug ?? $r->related?->slug,
                    'route' => $r->system ? route('systems.show', $r->system)
                        : ($r->event ? route('events.show', $r->event)
                        : ($r->faq ? route('faqs.show', $r->faq)
                        : route('documents.show', $r->related))),
                ])->values(),
            'referenced_by' => $document->incomingRelations
                ->filter(fn ($r) => $r->document && $r->document->isVisibleTo($user))
                ->map(fn ($r) => [
                    'type' => $r->relation_type,
                    'source_title' => $r->document->title,
                    'source_type' => $r->document->doc_type,
                    'slug' => $r->document->slug,
                ]),
        ];
    }

    /**
     * Graf knowledge 2-hop untuk visualisasi node-link (BAB 12).
     * Hanya simpul dokumen yang terlihat user yang masuk graf (permission-aware).
     *
     * @return array{nodes: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>, truncated: int}
     */
    public static function graph(Document $document, ?User $user, int $depth = 2): array
    {
        $depth = max(1, min(2, $depth));
        $nodes = [];
        $edges = [];
        $seenDocs = [];
        $seenSystems = [];
        $seenEvents = [];
        $seenFaqs = [];
        $seenUsers = [];
        $truncated = 0;

        $addDoc = function (Document $doc, int $ring) use (&$nodes, &$seenDocs): ?string {
            $key = 'd'.$doc->id;
            if (isset($seenDocs[$key])) {
                return $key;
            }
            if ($ring > 0 && count(array_filter($seenDocs, fn ($r) => $r === $ring)) >= 24) {
                return null;
            }
            $seenDocs[$key] = $ring;
            $nodes[] = [
                'id' => $key, 'kind' => 'document', 'ring' => $ring,
                'title' => $doc->title, 'subtitle' => $doc->doc_type,
                'status' => $doc->status,
                'url' => $doc->isGovernance() ? route('governance.show', $doc->slug) : route('documents.show', $doc->slug),
            ];

            return $key;
        };

        $addSystem = function (System $system, int $ring) use (&$nodes, &$seenSystems): ?string {
            $key = 's'.$system->id;
            if (isset($seenSystems[$key])) {
                return $key;
            }
            if ($ring > 0 && count(array_filter($seenSystems, fn ($r) => $r === $ring)) >= 24) {
                return null;
            }
            $seenSystems[$key] = $ring;
            $nodes[] = [
                'id' => $key, 'kind' => 'system', 'ring' => $ring,
                'title' => $system->name, 'subtitle' => 'System',
                'url' => route('systems.show', $system->slug),
            ];

            return $key;
        };

        $addEvent = function (Event $event, int $ring) use (&$nodes, &$seenEvents): ?string {
            $key = 'e'.$event->id;
            if (isset($seenEvents[$key])) {
                return $key;
            }
            if ($ring > 0 && count(array_filter($seenEvents, fn ($r) => $r === $ring)) >= 24) {
                return null;
            }
            $seenEvents[$key] = $ring;
            $nodes[] = [
                'id' => $key, 'kind' => 'event', 'ring' => $ring,
                'title' => $event->title, 'subtitle' => 'Agenda',
                'url' => route('events.show', $event->slug),
            ];

            return $key;
        };

        $addFaq = function (Faq $faq, int $ring) use (&$nodes, &$seenFaqs): ?string {
            $key = 'f'.$faq->id;
            if (isset($seenFaqs[$key])) {
                return $key;
            }
            if ($ring > 0 && count(array_filter($seenFaqs, fn ($r) => $r === $ring)) >= 24) {
                return null;
            }
            $seenFaqs[$key] = $ring;
            $nodes[] = [
                'id' => $key, 'kind' => 'faq', 'ring' => $ring,
                'title' => $faq->question, 'subtitle' => 'FAQ',
                'url' => route('faqs.show', $faq->slug),
            ];

            return $key;
        };

        $addPerson = function (int $userId, string $name, int $ring) use (&$nodes, &$seenUsers): ?string {
            $key = 'u'.$userId;
            if (isset($seenUsers[$key])) {
                return $key;
            }
            if (count($seenUsers) >= 12) {
                return null;
            }
            $seenUsers[$key] = $ring;
            $nodes[] = [
                'id' => $key, 'kind' => 'person', 'ring' => $ring,
                'title' => $name, 'subtitle' => 'PIC',
                'url' => null,
            ];

            return $key;
        };

        $centerKey = $addDoc($document, 0);
        if ($document->pic_id) {
            $centerPicName = $document->relationLoaded('pic')
                ? ($document->pic?->name ?? $document->pic_name ?? 'PIC')
                : (User::query()->whereKey($document->pic_id)->value('name') ?? $document->pic_name ?? 'PIC');
            $picKey = $addPerson($document->pic_id, $centerPicName, 0);
            if ($picKey) {
                $edges[] = ['from' => $centerKey, 'to' => $picKey, 'label' => 'pic', 'dir' => 'out'];
            }
        }

        // BFS dua arah: relasi keluar + masuk, difilter visibilitas per kandidat.
        $frontier = [$document->id];
        $visited = [$document->id => true];
        for ($ring = 1; $ring <= $depth; $ring++) {
            $relations = DocumentRelation::query()
                ->where(fn ($q) => $q->whereIn('document_id', $frontier)->orWhereIn('related_id', $frontier))
                ->with(['system:id,name,slug', 'event:id,title,slug,visibility', 'faq:id,question,slug,visibility'])
                ->get();

            $candidateIds = $relations->map(fn ($r) => in_array($r->document_id, $frontier, true) ? $r->related_id : $r->document_id)
                ->filter()->unique()->values();
            $candidates = Document::query()
                ->whereIn('id', $candidateIds)
                ->select(['id', 'title', 'slug', 'doc_type', 'status', 'visibility', 'user_id', 'pic_id', 'pic_name'])
                ->get()->keyBy('id');
            $pics = User::query()->whereIn('id', $candidates->map->pic_id->filter()->unique()->values())
                ->select(['id', 'name'])->get()->keyBy('id');

            $nextFrontier = [];
            foreach ($relations as $rel) {
                $forward = in_array($rel->document_id, $frontier, true);
                $sourceId = $forward ? $rel->document_id : $rel->related_id;
                $sourceKey = 'd'.$sourceId;
                if (! isset($seenDocs[$sourceKey])) {
                    continue;
                }

                if ($rel->system) {
                    $targetKey = $addSystem($rel->system, $ring);
                    if (! $targetKey) {
                        $truncated++;

                        continue;
                    }
                    $edges[] = ['from' => $forward ? $sourceKey : $targetKey, 'to' => $forward ? $targetKey : $sourceKey, 'label' => $rel->relation_type, 'dir' => $forward ? 'out' : 'in'];

                    continue;
                }

                if (($rel->event && $rel->event->isVisibleTo($user)) || ($rel->faq && $rel->faq->isVisibleTo($user))) {
                    $targetKey = $rel->event ? $addEvent($rel->event, $ring) : $addFaq($rel->faq, $ring);
                    if (! $targetKey) {
                        $truncated++;

                        continue;
                    }
                    $edges[] = ['from' => $sourceKey, 'to' => $targetKey, 'label' => $rel->relation_type, 'dir' => $forward ? 'out' : 'in'];

                    continue;
                }

                $targetId = $forward ? $rel->related_id : $rel->document_id;
                if (! $targetId || isset($visited[$targetId])) {
                    continue;
                }
                $target = $candidates->get($targetId);
                // Relasi yatim atau tak terlihat dilewati agar tak bocor & tak error.
                if (! $target || ! $target->isVisibleTo($user)) {
                    continue;
                }
                $visited[$targetId] = true;
                $targetKey = $addDoc($target, $ring);
                if (! $targetKey) {
                    $truncated++;

                    continue;
                }
                $edges[] = ['from' => $forward ? $sourceKey : $targetKey, 'to' => $forward ? $targetKey : $sourceKey, 'label' => $rel->relation_type, 'dir' => $forward ? 'out' : 'in'];
                if ($target->pic_id) {
                    $picKey = $addPerson($target->pic_id, $pics->get($target->pic_id)?->name ?? $target->pic_name ?? 'PIC', $ring);
                    if ($picKey) {
                        $edges[] = ['from' => $targetKey, 'to' => $picKey, 'label' => 'pic', 'dir' => 'out'];
                    }
                }
                $nextFrontier[] = $targetId;
            }
            $frontier = array_values(array_unique($nextFrontier));
            if ($frontier === []) {
                break;
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges, 'truncated' => $truncated];
    }
}
