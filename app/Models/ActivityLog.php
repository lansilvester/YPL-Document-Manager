<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'document_id', 'action', 'meta', 'ip'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public static function record(?int $userId, ?int $docId, string $action, ?array $meta = null): void
    {
        static::create([
            'user_id' => $userId,
            'document_id' => $docId,
            'action' => $action,
            'meta' => $meta,
            'ip' => request()->ip(),
        ]);
    }
}
