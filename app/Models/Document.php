<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'description',
        'file_path', 'file_size', 'mime', 'visibility', 'source_type',
        'content', 'views_count', 'downloads_count',
    ];

    protected function casts(): array
    {
        return ['views_count' => 'integer', 'downloads_count' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function isVisibleTo(?User $user): bool
    {
        if ($this->visibility === 'public') {
            return true;
        }
        if (! $user) {
            return false;
        }
        if ($user->role === 'admin' || $this->user_id === $user->id) {
            return true;
        }
        // restricted = hanya pengguna login (komunitas YPL)
        return $this->visibility === 'restricted';
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }
}
