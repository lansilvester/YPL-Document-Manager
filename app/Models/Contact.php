<?php

namespace App\Models;

use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected $fillable = ['unit_id', 'name', 'position', 'email', 'phone', 'sort_order'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
