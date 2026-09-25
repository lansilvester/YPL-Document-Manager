<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingPosition extends Model
{
    protected $fillable = ['user_id', 'document_id', 'page', 'total_pages', 'percent'];

    protected function casts(): array
    {
        return ['percent' => 'float'];
    }
}
