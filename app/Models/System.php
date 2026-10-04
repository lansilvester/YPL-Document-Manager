<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class System extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'url'];

    public function documentRelations(): HasMany
    {
        return $this->hasMany(DocumentRelation::class);
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_relations', 'system_id', 'document_id')
            ->withPivot('relation_type')->withTimestamps();
    }
}
