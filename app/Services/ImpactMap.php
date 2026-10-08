<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;

/**
 * Membangun struktur impact map dari relasi yang sudah di-eager-load.
 * Dipakai GovernanceController@show dan DocumentController@show agar konsisten.
 *
 * @return array{references: Collection, referenced_by: Collection}
 */
class ImpactMap
{
    public static function for(Document $document): array
    {
        return [
            'references' => $document->outgoingRelations
                ->filter(fn ($r) => $r->system || $r->related)
                ->map(fn ($r) => [
                    'type' => $r->relation_type,
                    'target_type' => $r->system ? 'system' : 'document',
                    'title' => $r->system?->name ?? $r->related?->title,
                    'slug' => $r->system?->slug ?? $r->related?->slug,
                    'route' => $r->system ? route('systems.show', $r->system) : route('documents.show', $r->related),
                ])->values(),
            'referenced_by' => $document->incomingRelations->map(fn ($r) => [
                'type' => $r->relation_type,
                'source_title' => $r->document->title,
                'source_type' => $r->document->doc_type,
                'slug' => $r->document->slug,
            ]),
        ];
    }
}
