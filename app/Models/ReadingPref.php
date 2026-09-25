<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingPref extends Model
{
    protected $fillable = ['user_id', 'theme', 'zoom', 'layout'];
}
