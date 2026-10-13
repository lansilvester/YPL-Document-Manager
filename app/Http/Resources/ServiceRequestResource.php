<?php

namespace App\Http\Resources;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ServiceRequest
 */
class ServiceRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->label(),
            'title' => $this->title,
            'description' => $this->description,
            'needed_at' => $this->needed_at?->format('Y-m-d'),
            'status' => $this->status,
            'handled_note' => $this->handled_note,
            'requester' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'handler' => $this->whenLoaded('handler', fn () => $this->handler ? [
                'id' => $this->handler->id,
                'name' => $this->handler->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
