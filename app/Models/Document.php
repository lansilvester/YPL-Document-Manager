<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
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

    public const TYPES = ['policy', 'regulation', 'standard', 'sop', 'work_instruction', 'decision', 'organization', 'risk', 'compliance', 'quality', 'document_control', 'form', 'evidence', 'handbook', 'other'];

    /**
     * Label tampilan per tipe agar konsisten di seluruh UI (BAB 17).
     * Kode tetap singular untuk kompatibilitas data; label mengikuti nomenklatur blueprint.
     *
     * @var array<string, string>
     */
    public const TYPE_LABELS = [
        'policy' => 'Policy',
        'regulation' => 'Regulation',
        'standard' => 'Standard',
        'sop' => 'SOP',
        'work_instruction' => 'Work Instruction',
        'decision' => 'Decisions',
        'organization' => 'Organization',
        'risk' => 'Risk',
        'compliance' => 'Compliance',
        'quality' => 'Quality',
        'document_control' => 'Document Control',
        'form' => 'Form',
        'evidence' => 'Evidence',
        'handbook' => 'Handbook',
        'other' => 'Other',
    ];

    /** Label tampilan untuk sebuah tipe dokumen (fallback: ubah _ → spasi). */
    public static function typeLabel(?string $type): string
    {
        if ($type === null || $type === '') {
            return '—';
        }

        return static::TYPE_LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

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

    public function incomingRelations(): HasMany
    {
        return $this->hasMany(DocumentRelation::class, 'related_id');
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

    /**
     * Kelas badge Tailwind sesuai status lifecycle.
     */
    public function statusBadge(): string
    {
        return static::statusBadgeFor($this->status);
    }

    public static function statusBadgeFor(?string $status): string
    {
        return match ($status) {
            'active' => 'bg-green-50 text-green-700',
            'draft' => 'bg-slate-100 text-slate-600',
            'review' => 'bg-blue-50 text-blue-700',
            'approval' => 'bg-purple-50 text-purple-700',
            'review_due' => 'bg-amber-50 text-amber-700',
            'archived' => 'bg-slate-100 text-slate-500',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Snapshot isi revisi untuk revision history (BAB 13).
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'doc_type' => $this->doc_type,
            'version' => $this->version,
            'classification' => $this->classification,
            'audience' => $this->audience,
            'unit' => $this->unit,
            'effective_at' => $this->effective_at?->format('Y-m-d'),
            'review_at' => $this->review_at?->format('Y-m-d'),
        ];
    }

    /**
     * Naikkan nomor versi semantik sederhana: "1.0" → "1.1" → "1.2".
     * Segmen non-numerik atau format tak terduga diperlakukan aman (tetap versi saat ini).
     */
    public function nextVersion(): string
    {
        $current = trim((string) $this->version);
        if ($current === '') {
            return '1.0';
        }

        $parts = explode('.', $current);
        $last = array_pop($parts);
        if (! is_numeric($last)) {
            return $current;
        }

        $parts[] = (string) ((int) $last + 1);

        return implode('.', $parts);
    }

    /**
     * Catat satu revisi baru dengan snapshot isi (audit trail BAB 13).
     * Nomor versi harus sudah ditetapkan pada $this sebelum dipanggil.
     *
     * @param  array<string, mixed>|null  $snapshot
     */
    public function recordVersion(?string $fromStatus, ?string $changeNote = null, ?array $snapshot = null, ?int $userId = null): DocumentVersion
    {
        return $this->versions()->create([
            'user_id' => $userId ?? auth()->id(),
            'version' => $this->version,
            'from_status' => $fromStatus,
            'to_status' => $this->status,
            'change_note' => $changeNote,
            'snapshot' => $snapshot ?? $this->snapshot(),
        ]);
    }

    /**
     * Bandingkan dua snapshot revisi untuk "What changed?" (BAB 13).
     *
     * @param  array<string, mixed>|null  $from
     * @param  array<string, mixed>|null  $to
     * @return array<int, array{field: string, from: mixed, to: mixed}>
     */
    public static function snapshotDiff(?array $from, ?array $to): array
    {
        $from ??= [];
        $to ??= [];
        $diff = [];

        foreach (array_keys($from + $to) as $field) {
            $a = $from[$field] ?? null;
            $b = $to[$field] ?? null;
            if ($a !== $b) {
                $diff[] = ['field' => $field, 'from' => $a, 'to' => $b];
            }
        }

        return $diff;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function readingPositions(): HasMany
    {
        return $this->hasMany(ReadingPosition::class);
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
     * Load only the fields and relations rendered by document cards.
     *
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopeForCard(Builder $query): Builder
    {
        return $query
            ->select([
                'documents.id', 'documents.title', 'documents.slug',
                'documents.category_id', 'documents.user_id',
                'documents.source_type', 'documents.description', 'documents.views_count',
            ])
            ->with([
                'category:id,name',
                'user:id,name',
                'tags:id,name',
            ]);
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

    /** Status yang boleh tampil di katalog publik (kriteria 12, opsi A). */
    public const LIVE_STATUSES = ['active', 'review_due'];

    public function isVisibleTo(?User $user): bool
    {
        if (! $user) {
            return $this->visibility === 'public'
                && in_array($this->status, self::LIVE_STATUSES, true);
        }
        if ($user->role === 'admin' || $this->user_id === $user->id) {
            return true;
        }
        if ($this->visibility === 'private') {
            return false;
        }
        // Staff pengelola menelaah draf internal non-privat (lifecycle).
        if ($user->isStaff()) {
            return true;
        }
        // Komunitas: hanya versi yang berlaku.
        if (! in_array($this->status, self::LIVE_STATUSES, true)) {
            return false;
        }

        // restricted = hanya pengguna login (komunitas YPL)
        return $this->visibility === 'public' || $this->visibility === 'restricted';
    }

    /**
     * Paritas level-query dari isVisibleTo(): batasi knowledge Ask YPL
     * (dan pencarian lain) sesuai otoritas user yang login.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user) {
            return $query->where('visibility', 'public')->whereIn('status', self::LIVE_STATUSES);
        }
        if ($user->role === 'admin') {
            return $query;
        }
        if ($user->isStaff()) {
            // Setara isVisibleTo: semua non-privat + milik sendiri.
            return $query->where(fn ($w) => $w
                ->where('visibility', '!=', 'private')
                ->orWhere('user_id', $user->id));
        }

        return $query->where(fn ($w) => $w
            ->where(fn ($q) => $q->where('visibility', 'public')->whereIn('status', self::LIVE_STATUSES))
            ->orWhere(fn ($q) => $q->where('visibility', 'restricted')->whereIn('status', self::LIVE_STATUSES))
            ->orWhere('user_id', $user->id));
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? route('documents.stream', $this) : null;
    }

    /**
     * Tipe dokumen yang tampil dengan layout mockup governance (BAB 13).
     *
     * @var array<int, string>
     */
    public const GOVERNANCE_TYPES = [
        'policy', 'regulation', 'standard', 'sop', 'work_instruction', 'decision',
        'organization', 'risk', 'compliance', 'quality', 'document_control', 'form', 'evidence',
    ];

    /**
     * Scope untuk dokumen governance (tipe policy, regulation, standard, sop, work_instruction, decision, form, evidence).
     */
    public function scopeGovernance($query)
    {
        return $query->whereIn('doc_type', static::GOVERNANCE_TYPES);
    }

    public function isGovernance(): bool
    {
        return in_array($this->doc_type, static::GOVERNANCE_TYPES, true);
    }

    public function usesStructuredReader(): bool
    {
        return $this->source_type === 'editor' && filled($this->content);
    }

    /**
     * Default kebijakan arsip (BAB 13 — Archive policy), dalam hari.
     * Dapat ditimpa Super Admin lewat Pengaturan Web (kunci `retention_days`).
     */
    public const RETENTION_DEFAULT_DAYS = 1095;

    /** Ambang retensi aktif (hari) untuk dokumen berstatus review_due. */
    public static function retentionDays(): int
    {
        $value = Setting::get('retention_days');

        return max(30, (int) ($value ?? static::RETENTION_DEFAULT_DAYS));
    }

    /**
     * Ambang waktu: dokumen review_due yang tanggal review-nya lebih tua dari ini
     * sudah layak diarsipkan otomatis.
     */
    public static function retentionCutoff(?Carbon $asOf = null): Carbon
    {
        return ($asOf ?? Carbon::now())->copy()->subDays(static::retentionDays());
    }

    /**
     * Kandidat arsip otomatis: status review_due dan sudah melewati masa retensi.
     * review_at NULL diperlakukan sejak tanggal update terakhir agar dokumen
     * lama tanpa jadwal review tidak menggantung selamanya.
     */
    public function scopeArchiveDue($query, ?Carbon $asOf = null)
    {
        $cutoff = static::retentionCutoff($asOf);

        return $query->where('status', 'review_due')
            ->where(fn ($w) => $w
                ->where(fn ($q) => $q->whereNotNull('review_at')->whereDate('review_at', '<', $cutoff->toDateString()))
                ->orWhere(fn ($q) => $q->whereNull('review_at')->where('updated_at', '<', $cutoff)));
    }
}
