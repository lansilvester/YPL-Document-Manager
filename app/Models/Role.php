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

    /**
     * Kartu aksi cepat: label + tujuan (satu sumber, dipakai beranda).
     *
     * @var array<string, array{label: string, route: string, params?: array<string, string>}>
     */
    public const QUICK_META = [
        'handbook' => ['label' => 'Handbook', 'route' => 'library.index'],
        'policy' => ['label' => 'Policy', 'route' => 'governance.index', 'params' => ['tipe' => 'policy']],
        'sop' => ['label' => 'SOP', 'route' => 'governance.index', 'params' => ['tipe' => 'sop']],
        'form' => ['label' => 'Formulir', 'route' => 'governance.index', 'params' => ['tipe' => 'form']],
        'calendar' => ['label' => 'Calendar', 'route' => 'calendar.index'],
        'ask' => ['label' => 'Ask YPL', 'route' => 'ask.index'],
    ];

    /**
     * Kartu explore: judul + deskripsi + tujuan + koleksi scope-nya.
     *
     * @var array<string, array{title: string, desc: string, route: string, params?: array<string, string>, collection: string}>
     */
    public const EXPLORE_META = [
        'handbooks' => ['title' => 'YPL Handbooks', 'desc' => '10 living digital books', 'route' => 'library.index', 'collection' => 'handbook'],
        'governance' => ['title' => 'Governance Library', 'desc' => 'Policy • Regulation • SOP', 'route' => 'governance.index', 'collection' => 'governance'],
        'learning' => ['title' => 'Learning Library', 'desc' => 'Academic resources', 'route' => 'library.index', 'params' => ['koleksi' => 'academic'], 'collection' => 'academic'],
        'boarding' => ['title' => 'Boarding Library', 'desc' => 'Student life & residential knowledge', 'route' => 'library.index', 'params' => ['koleksi' => 'boarding'], 'collection' => 'boarding'],
    ];

    /**
     * Petakan user (atau tamu) ke baris peran. Fallback aman bila
     * slug tak dikenal atau tabel belum di-seed. Tanpa static cache
     * agar aman di test maupun long-running process.
     */
    public static function for(?User $user): self
    {
        $role = $user ? static::query()->where('slug', $user->role)->first() : null;
        $role ??= static::query()->where('slug', $user ? 'anggota' : 'publik')->first();
        $slug = $role?->slug ?? ($user ? 'anggota' : 'publik');

        return $role ?? new static(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    public function allowsCollection(string $collection): bool
    {
        $allowed = $this->scopes['collections'] ?? static::COLLECTIONS;

        return in_array($collection, $allowed, true);
    }

    /** Urutan aksi cepat sesuai config peran (fallback urutan baku). */
    public function orderedQuick(): array
    {
        $keys = array_intersect($this->homepage['quick'] ?? static::QUICK_ACTIONS, static::QUICK_ACTIONS);

        return array_values($keys !== [] ? $keys : static::QUICK_ACTIONS);
    }

    /** Kartu explore yang masuk scope peran (fallback semua bila kosong). */
    public function scopedExplore(): array
    {
        $keys = array_intersect($this->homepage['explore'] ?? static::EXPLORE, static::EXPLORE);
        $keys = array_values($keys !== [] ? $keys : static::EXPLORE);

        $filtered = array_filter($keys, fn ($k) => $this->allowsCollection(static::EXPLORE_META[$k]['collection']));

        return array_values($filtered !== [] ? $filtered : $keys);
    }

    /** Peran operasional (boleh memantau review & layanan). */
    public function isOperational(): bool
    {
        return in_array($this->slug, ['pimpinan', 'admin', 'staff', 'superadmin'], true);
    }
}
