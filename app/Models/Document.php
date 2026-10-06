<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'approver_id', 'pic_id', 'category_id', 'title', 'slug', 'subtitle', 'description',
        'file_path', 'file_size', 'mime', 'visibility', 'source_type',
        'content', 'views_count', 'downloads_count',
        'doc_code', 'doc_type', 'version', 'status',
        'effective_at', 'review_at', 'classification',
        'audience', 'unit', 'pic_name',
    ];

    public const STATUSES = ['draft', 'review', 'approval', 'active', 'review_due', 'archived'];

    public const TYPES = ['policy', 'regulation', 'standard', 'sop', 'work_instruction', 'decision', 'form', 'evidence', 'handbook', 'other'];

    public const AUDIENCES = ['siswa', 'orang_tua', 'guru', 'karyawan', 'pimpinan', 'mitra', 'publik'];

    /**
     * Mesin transisi lifecycle: DRAFT → REVIEW → APPROVAL → ACTIVE → REVIEW_DUE → ARCHIVED.
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSITIONS = [
        'draft' => ['review', 'archived'],
        'review' => ['approval', 'draft', 'archived'],
        'approval' => ['active', 'draft', 'archived'],
        'active' => ['review_due', 'archived'],
        'review_due' => ['review', 'active', 'archived'],
        'archived' => ['draft'],
    ];

    protected function casts(): array
    {
        return [
            'views_count' => 'integer', 'downloads_count' => 'integer',
            'effective_at' => 'date', 'review_at' => 'date',
            'audience' => 'array',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->latest();
    }

    public function outgoingRelations(): HasMany
    {
        return $this->hasMany(DocumentRelation::class);
    }

    public function relatedDocuments(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_relations', 'document_id', 'related_id')
            ->withPivot('relation_type')->withTimestamps();
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Pencarian full-text yang optimal per driver DB:
     * - sqlite → FTS5 + peringkat bm25 (relevansi, bukan abjad/terbaru)
     * - mysql  → FULLTEXT boolean mode + fallback LIKE untuk recall
     * - lain   → LIKE (fallback aman)
     * Input pengguna selalu disanitasi agar operator FTS/wildcard tidak merusak query.
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return $this->applyFts($query, $term);
        }
        if ($driver === 'mysql') {
            return $this->applyFulltext($query, $term);
        }

        return $this->applyLike($query, $term);
    }

    /**
     * Varian OR dari scopeSearch untuk retrieval Ask YPL:
     * cocok bila SALAH SATU kata kunci ada, peringkat oleh bm25.
     * Relevansi akhir tetap ditentukan skor overlap di AskYpl.
     */
    public function scopeSearchAny($query, string $term)
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $match = static::ftsMatchAny($term);
            if ($match === null) {
                return $this->applyLike($query, $term);
            }

            return $query->select('documents.*')
                ->join('documents_fts', 'documents_fts.rowid', '=', 'documents.id')
                ->whereRaw('documents_fts MATCH ?', [$match])
                ->orderByRaw('bm25(documents_fts)');
        }
        if ($driver === 'mysql') {
            $bool = static::booleanMatchAny($term);
            $like = static::likePattern($term);

            return $query
                ->where(fn ($w) => $w
                    ->whereRaw('MATCH(title, description) AGAINST(? IN BOOLEAN MODE)', [$bool])
                    ->orWhere('title', 'like', $like)
                    ->orWhere('description', 'like', $like))
                ->orderByRaw('MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE) DESC', [$term]);
        }

        return $this->applyLike($query, $term);
    }

    private function applyFts($query, string $term)
    {
        $match = static::ftsMatch($term);
        if ($match === null) {
            return $this->applyLike($query, $term);
        }

        return $query->select('documents.*')
            ->join('documents_fts', 'documents_fts.rowid', '=', 'documents.id')
            ->whereRaw('documents_fts MATCH ?', [$match])
            ->orderByRaw('bm25(documents_fts)');
    }

    private function applyFulltext($query, string $term)
    {
        $bool = static::booleanMatch($term);
        $like = static::likePattern($term);

        return $query
            ->where(fn ($w) => $w
                ->whereRaw('MATCH(title, description) AGAINST(? IN BOOLEAN MODE)', [$bool])
                ->orWhere('title', 'like', $like)
                ->orWhere('description', 'like', $like))
            ->orderByRaw('MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE) DESC', [$term]);
    }

    private function applyLike($query, string $term)
    {
        $like = static::likePattern($term);

        return $query->where(fn ($w) => $w
            ->where('title', 'like', $like)
            ->orWhere('description', 'like', $like));
    }

    /** "kata1 kata2" → '"kata1" "kata2"' (implisit AND, operator FTS dinetralkan). */
    public static function ftsMatch(string $term): ?string
    {
        $terms = preg_split('/\s+/u', $term, 12, PREG_SPLIT_NO_EMPTY) ?: [];
        $quoted = [];
        foreach ($terms as $t) {
            $t = trim(str_replace('"', '', $t), '*^:.()');
            if (mb_strlen($t) >= 2) {
                $quoted[] = '"'.$t.'"';
            }
        }

        return $quoted ? implode(' ', $quoted) : null;
    }

    /** "kata1 kata2" → '"kata1" OR "kata2"' (cocok salah satu, untuk retrieval Ask). */
    public static function ftsMatchAny(string $term): ?string
    {
        $terms = preg_split('/\s+/u', $term, 20, PREG_SPLIT_NO_EMPTY) ?: [];
        $quoted = [];
        foreach ($terms as $t) {
            $t = trim(str_replace('"', '', $t), '*^:.()');
            if (mb_strlen($t) >= 2) {
                $quoted[] = '"'.$t.'"';
            }
        }

        return $quoted ? implode(' OR ', $quoted) : null;
    }

    /** "kata1 kata2" → '+kata1* +kata2*' untuk boolean mode MySQL. */
    public static function booleanMatch(string $term): string
    {
        $terms = preg_split('/\s+/u', $term, 12, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($terms as $t) {
            $t = preg_replace('/[^0-9A-Za-z\x{80}-\x{10FFFF}]/u', '', $t) ?? '';
            if (mb_strlen($t) >= 2) {
                $out[] = '+'.$t.'*';
            }
        }

        return $out ? implode(' ', $out) : '+'.preg_replace('/\W/u', '', $term);
    }

    /** Varian opsional (OR) dari booleanMatch untuk retrieval Ask. */
    public static function booleanMatchAny(string $term): string
    {
        $terms = preg_split('/\s+/u', $term, 20, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($terms as $t) {
            $t = preg_replace('/[^0-9A-Za-z\x{80}-\x{10FFFF}]/u', '', $t) ?? '';
            if (mb_strlen($t) >= 2) {
                $out[] = $t.'*';
            }
        }

        return $out ? implode(' ', $out) : preg_replace('/\W/u', '', $term);
    }

    /** Pola LIKE aman (% dan _ milik user di-escape). */
    public static function likePattern(string $term): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
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

    /**
     * Paritas level-query dari isVisibleTo(): batasi knowledge Ask YPL
     * (dan pencarian lain) sesuai otoritas user yang login.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user) {
            return $query->where('visibility', 'public');
        }
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->where(fn ($w) => $w
            ->where('visibility', 'public')
            ->orWhere('visibility', 'restricted')
            ->orWhere('user_id', $user->id));
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? route('documents.stream', $this) : null;
    }
}
