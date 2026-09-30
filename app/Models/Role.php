<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'priority', 'is_system', 'scopes', 'homepage',
    ];

    protected function casts(): array
    {
        return ['is_system' => 'boolean', 'scopes' => 'array', 'homepage' => 'array'];
    }

    public const COLLECTIONS = ['handbook', 'governance', 'academic', 'boarding', 'staff', 'parent', 'public'];

    public const DOC_TYPES = ['policy', 'regulation', 'standard', 'sop', 'work_instruction', 'decision', 'form', 'evidence', 'handbook', 'other'];

    public const QUICK_ACTIONS = ['handbook', 'policy', 'sop', 'form', 'calendar', 'ask'];

    public const EXPLORE = ['handbooks', 'governance', 'learning', 'boarding'];

    public const SYSTEM_SLUGS = ['anggota', 'staff', 'admin', 'superadmin'];

    public function allowsCollection(string $collection): bool
    {
        $allowed = $this->scopes['collections'] ?? static::COLLECTIONS;

        return in_array($collection, $allowed, true);
    }
}
