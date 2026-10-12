<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Document;
use App\Models\ReadingPosition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Mesin Rekomendasi — teroptimasi untuk hindari N+1 query.
 *
 * Strategi:
 * - Eager load semua relasi di query awal (candidates + seedDocs)
 * - Pre-compute maps di memori (relasi, tags, systems, coreaders)
 * - Scoring di memori tanpa query tambahan per kandidat
 */
class RecommendationEngine
{
    private const MAX_SEED_DOCUMENTS = 10;

    private const MAX_CANDIDATES = 200;

    public function __construct(
        private int $maxResults = 6,
        private array $weights = [
            'explicit' => 30,
            'category' => 20,
            'tags' => 20,
            'author' => 10,
            'coread' => 10,
            'system' => 10,
        ]
    ) {}

    /**
     * @return Collection<int, array{document: Document, score: int, reasons: array<string>}>
     */
    public function forDocument(Document $document, ?User $user = null): Collection
    {
        $document->loadMissing([
            'tags:id,name',
            'outgoingRelations:id,document_id,related_id,system_id',
        ]);

        $candidates = $this->candidateQuery($user)
            ->where('id', '!=', $document->id)
            ->limit(self::MAX_CANDIDATES)
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $sourceRelations = $this->buildRelationMaps($document, $candidates);
        $sourceTags = $document->tags->pluck('id')->all();
        $sourceSystems = $document->outgoingRelations
            ->whereNotNull('system_id')
            ->pluck('system_id')
            ->all();
        $coreaderMap = $user ? $this->buildCoreaderMap($document, $candidates, $user) : [];

        return $candidates
            ->map(fn (Document $candidate) => $this->scoreOptimized(
                $document,
                $candidate,
                $user,
                $sourceRelations,
                $sourceTags,
                $sourceSystems,
                $coreaderMap
            ))
            ->filter(fn ($hit) => $hit['score'] > 0)
            ->sortByDesc('score')
            ->take($this->maxResults)
            ->values();
    }

    /**
     * @return Collection<int, array{document: Document, score: int, reasons: array<string>}>
     */
    public function forUser(User $user): Collection
    {
        $readDocumentIds = ReadingPosition::query()
            ->where('user_id', $user->id)
            ->where('percent', '>=', 10)
            ->orderByDesc('updated_at')
            ->limit(self::MAX_SEED_DOCUMENTS)
            ->pluck('document_id');

        $bookmarkedDocumentIds = Bookmark::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(self::MAX_SEED_DOCUMENTS)
            ->pluck('document_id');

        $seedIds = $readDocumentIds
            ->merge($bookmarkedDocumentIds)
            ->unique()
            ->values();

        if ($seedIds->isEmpty()) {
            return collect();
        }

        $seedDocs = Document::query()
            ->visibleTo($user)
            ->whereIn('id', $seedIds)
            ->select(['id', 'category_id', 'user_id'])
            ->with([
                'tags:id,name',
                'outgoingRelations:id,document_id,related_id,system_id',
            ])
            ->get();

        if ($seedDocs->isEmpty()) {
            return collect();
        }

        $candidates = $this->candidateQuery($user)
            ->whereNotIn('id', $seedDocs->modelKeys())
            ->limit(self::MAX_CANDIDATES)
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $seedRelationMaps = [];
        $seedTags = [];
        $seedSystems = [];
        $seedAuthors = [];

        foreach ($seedDocs as $seed) {
            $seedRelationMaps[$seed->id] = $this->buildRelationMaps($seed, $candidates);
            $seedTags[$seed->id] = $seed->tags->pluck('id')->all();
            $seedSystems[$seed->id] = $seed->outgoingRelations
                ->whereNotNull('system_id')
                ->pluck('system_id')
                ->all();
            $seedAuthors[$seed->id] = $seed->user_id;
        }

        $coreaderMap = $this->buildCoreaderMapMultiple($seedDocs, $candidates, $user);

        return $candidates
            ->map(fn (Document $candidate) => $this->scorePersonalOptimized(
                $seedDocs,
                $candidate,
                $user,
                $seedRelationMaps,
                $seedTags,
                $seedSystems,
                $seedAuthors,
                $coreaderMap
            ))
            ->filter(fn ($hit) => $hit['score'] > 0)
            ->sortByDesc('score')
            ->take($this->maxResults)
            ->values();
    }

    /**
     * Candidate payload includes only fields used by scoring and recommendation cards.
     *
     * @return Builder<Document>
     */
    private function candidateQuery(?User $user): Builder
    {
        return Document::query()
            ->forCard()
            ->visibleTo($user)
            ->where('status', 'active')
            ->with('outgoingRelations:id,document_id,related_id,system_id')
            ->orderByDesc('created_at');
    }

    // ==================== PRIVATE HELPERS ====================

    /**
     * Build explicit relation map from already eager-loaded relations.
     *
     * @return array{explicit: array<int, int>}
     */
    private function buildRelationMaps(Document $source, Collection $candidates): array
    {
        $candidateIds = array_fill_keys($candidates->modelKeys(), true);
        $explicitTargets = $source->outgoingRelations
            ->pluck('related_id')
            ->filter(fn ($id) => isset($candidateIds[$id]))
            ->all();

        return [
            'explicit' => array_flip($explicitTargets),
        ];
    }

    /**
     * Build coreader map: candidate_id -> bool (apakah ada user lain yg baca keduanya)
     * 1 query untuk semua kandidat sekaligus.
     */
    private function buildCoreaderMap(Document $source, Collection $candidates, ?User $user): array
    {
        if (! $user) {
            return [];
        }

        $candidateIds = $candidates->pluck('id')->toArray();

        // Users yang membaca source DAN minimal 1 kandidat
        $coreaderUserIds = ReadingPosition::query()
            ->where('document_id', $source->id)
            ->where('user_id', '!=', $user->id ?? 0)
            ->where('percent', '>=', 10)
            ->pluck('user_id')
            ->toArray();

        if (empty($coreaderUserIds)) {
            return array_fill_keys($candidateIds, false);
        }

        // Cek mana kandidat yang juga dibaca oleh user tersebut
        $coreaderCandidates = ReadingPosition::query()
            ->whereIn('document_id', $candidateIds)
            ->whereIn('user_id', $coreaderUserIds)
            ->where('percent', '>=', 10)
            ->pluck('document_id')
            ->unique()
            ->toArray();

        return array_fill_keys($candidateIds, false) + array_fill_keys($coreaderCandidates, true);
    }

    /**
     * Coreader map untuk multiple seed docs sekaligus.
     */
    private function buildCoreaderMapMultiple(Collection $seedDocs, Collection $candidates, ?User $user): array
    {
        if (! $user || $seedDocs->isEmpty() || $candidates->isEmpty()) {
            return [];
        }

        $seedIds = $seedDocs->pluck('id')->toArray();
        $candidateIds = $candidates->modelKeys();

        // Users yang membaca minimal 2 dari seed docs (co-reading signal)
        $coreaderUserIds = ReadingPosition::query()
            ->whereIn('document_id', $seedIds)
            ->where('user_id', '!=', $user->id)
            ->where('percent', '>=', 10)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT document_id) >= 2')
            ->pluck('user_id')
            ->toArray();

        if (empty($coreaderUserIds)) {
            return [];
        }

        $coreaderCandidates = ReadingPosition::query()
            ->whereIn('document_id', $candidateIds)
            ->whereIn('user_id', $coreaderUserIds)
            ->where('percent', '>=', 10)
            ->distinct()
            ->pluck('document_id')
            ->all();

        return array_fill_keys($candidateIds, false) + array_fill_keys($coreaderCandidates, true);
    }

    /**
     * Scoring optimized — semua data sudah di-precompute, 0 query tambahan.
     */
    private function scoreOptimized(
        Document $source,
        Document $candidate,
        ?User $user,
        array $sourceRelations,
        array $sourceTags,
        array $sourceSystems,
        array $coreaderMap
    ): array {
        $reasons = [];
        $score = 0;

        // 1. Relasi eksplisit (source -> candidate)
        if (isset($sourceRelations['explicit'][$candidate->id])) {
            $score += $this->weights['explicit'];
            $reasons[] = 'Relasi eksplisit';
        }

        // 2. Kategori sama
        if ($source->category_id && $source->category_id === $candidate->category_id) {
            $score += $this->weights['category'];
            $reasons[] = 'Kategori sama';
        }

        // 3. Tag overlap
        $candTags = $candidate->tags->pluck('id')->toArray();
        $overlap = array_intersect($sourceTags, $candTags);
        if (! empty($overlap)) {
            $score += $this->weights['tags'] * min(count($overlap), 3);
            $reasons[] = 'Tag serupa ('.count($overlap).')';
        }

        // 4. Penulis sama
        if ($source->user_id === $candidate->user_id) {
            $score += $this->weights['author'];
            $reasons[] = 'Penulis sama';
        }

        // 5. Co-reading
        if (! empty($coreaderMap[$candidate->id])) {
            $score += $this->weights['coread'];
            $reasons[] = 'Sering dibaca bersama';
        }

        // 6. Sistem sama
        $candSystems = $candidate->outgoingRelations->whereNotNull('system_id')->pluck('system_id')->toArray();
        if (array_intersect($sourceSystems, $candSystems)) {
            $score += $this->weights['system'];
            $reasons[] = 'Sistem operasi sama';
        }

        return ['document' => $candidate, 'score' => $score, 'reasons' => $reasons];
    }

    private function scorePersonalOptimized(
        Collection $seedDocs,
        Document $candidate,
        User $user,
        array $seedRelationMaps,
        array $seedTags,
        array $seedSystems,
        array $seedAuthors,
        array $coreaderMap
    ): array {
        $reasons = [];
        $score = 0;

        // Siapkan candidate clone untuk scoreOptimized
        $candidateClone = clone $candidate;

        // Akumulasikan skor dari setiap seed doc
        foreach ($seedDocs as $seed) {
            $hit = $this->scoreOptimized(
                $seed,
                $candidateClone,
                null, // user tidak dipakai di scoreOptimized untuk personal
                $seedRelationMaps[$seed->id] ?? ['explicit' => [], 'related_ids' => []],
                $seedTags[$seed->id] ?? [],
                $seedSystems[$seed->id] ?? [],
                [] // coreader map sudah di-handle di level personal
            );

            $score += $hit['score'];
            $reasons = array_merge($reasons, $hit['reasons']);
        }

        // Bonus co-reading personal
        if (! empty($coreaderMap[$candidate->id])) {
            $score += $this->weights['coread'];
            $reasons[] = 'Sering dibaca bersama';
        }

        // Bonus same author dengan seed docs
        if (in_array($candidate->user_id, array_values($seedAuthors))) {
            $score += $this->weights['author'];
            $reasons[] = 'Penulis sama';
        }

        $score = min($score, 100);

        return ['document' => $candidate, 'score' => $score, 'reasons' => array_unique($reasons)];
    }
}
