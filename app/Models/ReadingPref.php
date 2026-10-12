<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingPref extends Model
{
    protected $fillable = ['user_id', 'theme', 'zoom', 'layout'];

    /** Tema baca BAB 15: navy, paper, dark. */
    public const THEMES = ['light', 'paper', 'dark'];

    /** Kelas body per tema + fallback aman untuk nilai lawas. */
    public static function bodyClass(string $theme): string
    {
        return match ($theme) {
            'dark' => 'bg-[#141920] text-stone-100',
            'paper' => 'bg-[#faf6ec] text-slate-800',
            default => 'bg-[#10182e] text-white',
        };
    }

    public function normalizedTheme(): string
    {
        return in_array($this->theme, self::THEMES, true) ? $this->theme : 'light';
    }
}
