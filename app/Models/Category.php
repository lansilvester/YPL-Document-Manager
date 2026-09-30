<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'code', 'collection', 'color', 'sort_order'];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
