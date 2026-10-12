<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRelation extends Model
{
    protected $fillable = ['document_id', 'related_id', 'system_id', 'event_id', 'faq_id', 'relation_type'];

    public const TYPES = ['handbook', 'policy', 'regulation', 'standard', 'sop', 'wi', 'form', 'system', 'calendar', 'faq', 'pic', 'related'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function related(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'related_id');
    }

    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function faq(): BelongsTo
    {
        return $this->belongsTo(Faq::class);
    }
}
