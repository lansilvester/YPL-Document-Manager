<?php

namespace App\Http\Resources;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Faq
 */
class FaqResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'question' => $this->question,
            'answer' => $this->answer,
            'category' => $this->category,
            'visibility' => $this->visibility,
            'sort_order' => $this->sort_order,
        ];
    }
}
