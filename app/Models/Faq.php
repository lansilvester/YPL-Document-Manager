<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faq extends Model
{
    protected $fillable = ['slug', 'question', 'answer', 'category', 'visibility', 'sort_order'];

    public function documentRelations(): HasMany
    {
        return $this->hasMany(DocumentRelation::class);
    }

    /**
     * Tamu: hanya public. Login: public + internal (komunitas YPL).
     */
    public function isVisibleTo(?User $user): bool
    {
        return $this->visibility === 'public' || $user !== null;
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_relations', 'faq_id', 'document_id')
            ->withPivot('relation_type')->withTimestamps();
    }
}
