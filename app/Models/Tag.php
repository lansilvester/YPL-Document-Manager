<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'color'];

    public const COLORS = ['navy', 'red', 'steel', 'gold', 'slate', 'charcoal'];

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class);
    }
}
