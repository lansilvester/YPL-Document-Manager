<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Document;
use App\Models\ReadingPosition;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Mesin Rekomendasi — menemukan dokumen terkait untuk:
 * - Panel "Terkait" di detail dokumen
 * - Sidebar di Reader Mode
 * - Personalisasi homepage (Phase 6)
 *
 * Sumber sinyal (bobot total 100):
 * 1. Relasi eksplisit (document_relations)          → 30
 * 2. Kategori sama                                    → 20
 * 3. Tag overlap                                      → 20
 * 4. Penulis sama                                     → 10
 * 5. Co-reading (reading_positions overlap)           → 10
 * 4. Sistem sama (document_relations → systems)       → 10
 *
 * Hanya mengembalikan dokumen yang visibleTo($user).
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
        $candidates = Document::query()->with(['category', 'tags', 'user', 'outgoingRelations.related', 'outgoingRelations.system'])
            ->visibleTo($user)
            ->where('id', '!=', $document->id)
            ->where('status', 'active')
            ->limit(200)
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $scored = $candidates
            ->map(fn (Document $c) => $this->score($document, $c, $user))
            ->filter(fn ($hit) => $hit['score'] > 0)
            ->sortByDesc('score')
            ->take($this->maxResults)
            ->values();

        return $scored;
    }

    /**
     * @return array{document: Document, score: int, reasons: array<string>}
     */
    private function score(Document $source, Document $candidate, ?User $user): array
    {
        $reasons = [];
        $score = 0;

        // 1. Relasi eksplisit (source → candidate)
        $explicit = $source->outgoingRelations()
            ->where('related_id', $candidate->id)
            ->exists();
        if ($explicit) {
            $score += $this->weights['explicit'];
            $reasons[] = 'Relasi eksplisit';
        }

        // 2. Kategori sama
        if ($source->category_id && $source->category_id === $candidate->category_id) {
            $score += $this->weights['category'];
            $reasons[] = 'Kategori sama';
        }

        // 3. Tag overlap
        $sourceTags = $source->tags->pluck('id')->toArray();
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

        // 5. Co-reading (users who read both)
        if ($user) {
            $coread = ReadingPosition::query()
                ->whereIn('document_id', [$source->id, $candidate->id])
                ->where('user_id', '!=', $user->id)
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(DISTINCT document_id) = 2')
                ->exists();
            if ($coread) {
                $score += $this->weights['coread'];
                $reasons[] = 'Sering dibaca bersama';
            }
        }

        // 6. Sistem sama
        $sourceSystems = $source->outgoingRelations()->whereNotNull('system_id')->pluck('system_id');
        $candSystems = $candidate->outgoingRelations()->whereNotNull('system_id')->pluck('system_id');
        if ($sourceSystems->intersect($candSystems)->isNotEmpty()) {
            $score += $this->weights['system'];
            $reasons[] = 'Sistem operasi sama';
        }

        return ['document' => $candidate, 'score' => $score, 'reasons' => $reasons];
    }

    /**
     * Rekomendasi personal untuk user (berdasarkan riwayat baca + bookmark)
     *
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

        $seedDocs = Document::whereIn('id', $seedIds)
            ->visibleTo($user)
            ->with(['category', 'tags', 'user', 'outgoingRelations.related', 'outgoingRelations.system'])
            ->get();

        $candidates = Document::query()->with(['category', 'tags', 'user', 'outgoingRelations.related', 'outgoingRelations.system'])
            ->visibleTo($user)
            ->where('status', 'active')
            ->whereNotIn('id', $seedIds)
            ->limit(200)
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $scored = $candidates
            ->map(fn (Document $c) => $this->scorePersonal($seedDocs, $c, $user))
            ->filter(fn ($hit) => $hit['score'] > 0)
            ->sortByDesc('score')
            ->take($this->maxResults)
            ->values();

        return $scored;
    }

    /**
     * @return array{document: Document, score: int, reasons: array<string>}
     */
    private function scorePersonal(Collection $seedDocs, Document $candidate, User $user): array
    {
        $reasons = [];
        $score = 0;

        // Semakin mirip dengan dokumen yang sudah dibaca/bookmark
        foreach ($seedDocs as $seed) {
            $hit = $this->score($seed, $candidate, $user);
            $score += $hit['score'];
            $reasons = array_merge($reasons, $hit['reasons']);
        }

        // Normalisasi (maks 100)
        $score = min($score, 100);

        return ['document' => $candidate, 'score' => $score, 'reasons' => array_unique($reasons)];
    }
}
