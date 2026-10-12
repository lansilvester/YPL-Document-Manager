<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = ['slug', 'title', 'description', 'category', 'starts_at', 'ends_at', 'location', 'visibility'];

    public const CATEGORIES = ['foundation', 'academic', 'boarding', 'event', 'deadline'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

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
        return $this->belongsToMany(Document::class, 'document_relations', 'event_id', 'document_id')
            ->withPivot('relation_type')->withTimestamps();
    }
}
