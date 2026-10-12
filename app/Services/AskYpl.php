<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Mesin Ask YPL v1 (ekstraktif, tanpa LLM).
 *
 * Menjawab dari knowledge yang boleh diakses user: kandidat diambil
 * lewat scopeVisibleTo() sehingga dokumen di luar otoritas tidak
 * pernah masuk retrieval, apalagi dikutip.
 *
 * @return array{answer: string, sources: array<int, array{document: Document, passage: string, score: int}>, found: bool, ai: bool}
 */
class AskYpl
{
    public function __construct(private int $maxSources = 5) {}

    /**
     * @return array{answer: string, sources: array<int, array{document: Document, passage: string, score: int}>, found: bool, ai: bool}
     */
    public function answer(string $question, ?User $user, ?Document $scope = null): array
    {
        $keywords = $this->keywords($question);

        // Scope per-dokumen (BAB 14): tanpa query pencarian — kandidat tunggal.
        $candidates = $scope !== null && $scope->isVisibleTo($user)
            ? collect([$scope])
            : Document::query()->with(['category'])
                ->visibleTo($user)
                ->searchAny($question)
                ->limit($this->maxSources * 3)
                ->get();

        $scored = $candidates
            ->map(fn (Document $doc) => $this->bestPassage($doc, $keywords))
            ->filter(fn ($hit) => $hit['score'] > 0)
            ->sortByDesc('score')
            ->take($this->maxSources)
            ->values();

        if ($scored->isEmpty()) {
            return [
                'answer' => 'Saya tidak menemukan sumber resmi yang menjawab pertanyaan tersebut dalam knowledge YPL yang dapat Anda akses. Coba gunakan kata kunci lain, atau jelajahi Library dan Governance secara langsung.',
                'sources' => [],
                'found' => false,
                'ai' => false,
            ];
        }

        $sources = $scored->all();
        if (GeminiLlm::enabled() && ($composed = (new GeminiLlm)->compose($question, $sources)) !== null) {
            return ['answer' => $composed, 'sources' => $sources, 'found' => true, 'ai' => true];
        }

        $lines = [
            'Berdasarkan '.$scored->count().' sumber resmi:',
            '',
        ];
        foreach ($scored as $i => $hit) {
            /** @var Document $doc */
            $doc = $hit['document'];
            $lines[] = ($i + 1).'. '.$doc->title.' — "'.$hit['passage'].'"';
        }
        $lines[] = '';
        $lines[] = 'Buka sumber untuk konteks lengkapnya.';

        return [
            'answer' => implode("\n", $lines),
            'sources' => $sources,
            'found' => true,
            'ai' => false,
        ];
    }

    /**
     * @return array{document: Document, passage: string, score: int}
     */
    private function bestPassage(Document $doc, Collection $keywords): array
    {
        $fields = array_filter([
            $doc->title.' '.$doc->subtitle,
            $doc->description,
            $this->plainText($doc->content),
        ]);

        $best = ['document' => $doc, 'passage' => '', 'score' => 0];
        foreach ($fields as $text) {
            foreach ($this->sentences($text) as $sentence) {
                $score = $this->score($sentence, $keywords);
                if ($score > $best['score']) {
                    $best = ['document' => $doc, 'passage' => $sentence, 'score' => $score];
                }
            }
        }

        return $best;
    }

    /** Kata kunci: huruf kecil, min 2 karakter, tanpa duplikat (paritas ftsMatch). */
    private function keywords(string $question): Collection
    {
        $words = preg_split('/\s+/u', mb_strtolower($question), 20, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($words)
            ->map(fn ($w) => trim((string) preg_replace('/[^\p{L}\p{N}]+/u', '', $w)))
            ->filter(fn ($w) => mb_strlen($w) >= 2)
            ->unique()
            ->values();
    }

    /** @return list<string> */
    private function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [];

        return array_values(array_filter(array_map(
            fn ($s) => mb_substr(trim($s), 0, 280),
            $parts
        ), fn ($s) => mb_strlen($s) >= 10));
    }

    private function score(string $sentence, Collection $keywords): int
    {
        $lower = mb_strtolower($sentence);
        $score = 0;
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                $score += mb_strlen($kw) >= 5 ? 3 : 1;
            }
        }

        return $score;
    }

    private function plainText(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    }
}
