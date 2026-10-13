<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doc_code' => $this->doc_code,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'doc_type' => $this->doc_type,
            'doc_type_label' => $this->doc_type ? Document::typeLabel($this->doc_type) : null,
            'version' => $this->version,
            'status' => $this->status,
            'classification' => $this->classification,
            'visibility' => $this->visibility,
            'source_type' => $this->source_type,
            'audience' => $this->audience,
            'unit' => $this->unit,
            'effective_at' => $this->effective_at?->format('Y-m-d'),
            'review_at' => $this->review_at?->format('Y-m-d'),
            'views_count' => $this->views_count,
            'downloads_count' => $this->downloads_count,
            'file_url' => $this->when(
                $this->isVisibleTo($request->user()) && $this->file_path,
                fn () => $this->fileUrl()
            ),
            'has_file' => (bool) $this->file_path,
            'uses_structured_reader' => $this->usesStructuredReader(),
            'is_governance' => $this->isGovernance(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'author' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'approver' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
            'pic' => $this->whenLoaded('pic', fn () => $this->pic ? [
                'id' => $this->pic->id,
                'name' => $this->pic->name,
                'email' => $this->pic->email,
            ] : null),
            'pic_name' => $this->pic_name,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
