<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Document;
use App\Models\ReadingPosition;
use App\Models\User;
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
        // 1 query: ambil kandidat + semua relasi yang dibutuhkan
        $candidates = Document::query()
            ->with([
                'category',
                'tags',
                'user',
                'outgoingRelations.related' => fn ($q) => $q->with(['category', 'user']),
                'outgoingRelations.system',
                'tags',
            ])
            ->visibleTo($user)
            ->where('id', '!=', $document->id)
            ->where('status', 'active')
            ->limit(200)
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        // Pre-compute maps sekali saja (bukan per kandidat)
        $sourceRelations = $this->buildRelationMaps($document, $candidates);
        $sourceTags = $document->tags->pluck('id')->toArray();
        $sourceSystems = $document->outgoingRelations()->whereNotNull('system_id')->pluck('system_id')->toArray();

        // Pre-compute coreaders sekali untuk semua kandidat
        $coreaderMap = $user ? $this->buildCoreaderMap($document, $candidates, $user) : [];

        $scored = $candidates
            ->map(fn (Document $c) => $this->scoreOptimized(
                $document,
                $c,
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

        return $scored;
    }

    /**
     * @return Collection<int, array{document: Document, score: int, reasons: array<string>}>
     */
    public function forUser(User $user): Collection
    {
        $readDocIds = ReadingPosition::where('user_id', $user->id)
            ->where('percent', '>=', 10)
            ->pluck('document_id')->toArray();

        $bookmarkDocIds = Bookmark::where('user_id', $user->id)
            ->pluck('document_id')->toArray();

        $seedIds = array_unique(array_merge($readDocIds, $bookmarkDocIds));

        if (empty($seedIds)) {
            return collect();
        }

        // Seed docs dengan relasi lengkap
        $seedDocs = Document::query()
            ->visibleTo($user)
            ->with([
                'category', 'tags', 'user',
                'outgoingRelations.related',
                'outgoingRelations.system',
            ])
            ->whereIn('id', $seedIds)
            ->get();

        // Kandidat (exclude seed)
        $candidates = Document::query()
            ->visibleTo($user)
            ->with([
                'category', 'tags', 'user',
                'outgoingRelations.related',
                'outgoingRelations.system',
            ])
            ->where('status', 'active')
            ->whereNotIn('id', $seedIds)
            ->limit(200)
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        // Pre-compute maps untuk seed docs
        $seedRelationMaps = [];
        $seedTags = [];
        $seedSystems = [];
        $seedAuthors = [];

        foreach ($seedDocs as $seed) {
            $seedRelationMaps[$seed->id] = $this->buildRelationMaps($seed, collect());
            $seedTags[$seed->id] = $seed->tags->pluck('id')->toArray();
            $seedSystems[$seed->id] = $seed->outgoingRelations()->whereNotNull('system_id')->pluck('system_id')->toArray();
            $seedAuthors[$seed->id] = $seed->user_id;
        }

        // Coreader map untuk semua kandidat sekaligus
        $coreaderMap = $this->buildCoreaderMapMultiple($seedDocs, $user);

        $scored = $candidates
            ->map(fn (Document $c) => $this->scorePersonalOptimized(
                $seedDocs,
                $c,
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

        return $scored;
    }

    // ==================== PRIVATE HELPERS ====================

    /**
     * Build relation maps untuk 1 dokumen source terhadap koleksi candidates.
     * Returns: ['explicit' => [candidate_id => true], 'related_ids' => [...]]
     */
    private function buildRelationMaps(Document $source, Collection $candidates): array
    {
        $candidateIds = $candidates->pluck('id')->toArray();

        // Cek relasi eksplisit source -> candidate dalam 1 query
        $explicitTargets = $source->outgoingRelations()
            ->whereIn('related_id', $candidateIds)
            ->pluck('related_id')
            ->toArray();

        return [
            'explicit' => array_flip($explicitTargets),
            'related_ids' => $source->outgoingRelations->pluck('related_id')->toArray(),
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
    private function buildCoreaderMapMultiple(Collection $seedDocs, ?User $user): array
    {
        if (! $user || $seedDocs->isEmpty()) {
            return [];
        }

        $seedIds = $seedDocs->pluck('id')->toArray();
        $userId = $user->id ?? 0;

        // Users yang membaca minimal 2 dari seed docs (co-reading signal)
        $coreaderUserIds = ReadingPosition::query()
            ->whereIn('document_id', $seedIds)
            ->where('user_id', '!=', $userId)
            ->where('percent', '>=', 10)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT document_id) >= 2')
            ->pluck('user_id')
            ->toArray();

        if (empty($coreaderUserIds)) {
            return [];
        }

        // Kandidat yang dibaca oleh coreader users
        return Document::query()
            ->where('status', 'active')
            ->whereHas('readingPositions', fn ($q) => $q
                ->whereIn('user_id', $coreaderUserIds)
                ->where('percent', '>=', 10)
            )
            ->pluck('id')
            ->toArray();
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
