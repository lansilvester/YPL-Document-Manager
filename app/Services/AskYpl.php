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

        // Scope per-dokumen (BAB 14): kandidat tunggal tanpa query pencarian.
        // Scope tak terlihat → tidak ditemukan (tanpa fallback global diam-diam).
        $candidates = $scope !== null
            ? ($scope->isVisibleTo($user) ? collect([$scope]) : collect())
            : Document::query()
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
     * Explain-document terstruktur (BAB 14): ringkasan, audience, berlaku,
     * perubahan terakhir, dan dokumen terkait — murni dari metadata +
     * kutipan dokumen, tanpa LLM dan tanpa filler.
     *
     * @return array{answer: string, sources: array<int, array{document: Document, passage: string, score: int}>, found: bool, ai: bool}
     */
    public function explain(Document $doc, ?User $user): array
    {
        if (! $doc->isVisibleTo($user)) {
            return ['answer' => '', 'sources' => [], 'found' => false, 'ai' => false];
        }

        $lines = ['RINGKASAN '.$doc->title, ''];
        $summary = trim((string) ($doc->description ?? ''));
        if ($summary === '' && $doc->content) {
            $summary = implode(' ', array_slice($this->sentences($this->plainText($doc->content)), 0, 3));
        }
        $lines[] = $summary !== '' ? $summary : 'Belum ada ringkasan tertulis untuk dokumen ini.';
        $lines[] = '';

        $audience = collect((array) ($doc->audience ?? []))
            ->map(fn ($a) => strtoupper(str_replace('_', ' ', (string) $a)))
            ->filter()->values();
        if ($doc->unit) {
            $audience->push('UNIT '.$doc->unit);
        }
        if ($audience->isNotEmpty()) {
            $lines[] = 'WAJIB MENGIKUTI: '.$audience->implode(', ');
            $lines[] = '';
        }

        $meta = array_filter([
            $doc->version ? 'v'.$doc->version : null,
            $doc->status ? strtoupper(str_replace('_', ' ', $doc->status)) : null,
            $doc->effective_at ? 'berlaku '.$doc->effective_at->format('d M Y') : null,
            $doc->review_at ? 'tinjau sebelum '.$doc->review_at->format('d M Y') : null,
        ]);
        if ($meta !== []) {
            $lines[] = 'BERLAKU: '.implode(' · ', $meta);
            $lines[] = '';
        }

        $last = $doc->versions()->latest()->first();
        if ($last) {
            $change = 'v'.$last->version.' ('.($last->from_status ?? 'awal').' → '.$last->to_status.', '.$last->created_at->format('d M Y').')';
            if ($last->change_note) {
                $change .= ' — '.$last->change_note;
            }
            $lines[] = 'PERUBAHAN TERAKHIR: '.$change;
            $lines[] = '';
        }

        $related = $doc->outgoingRelations()->with('related:id,title,slug,status,visibility,user_id')->get()
            ->map(fn ($r) => $r->related)->filter(fn ($d) => $d && $d->isVisibleTo($user))
            ->take(5)->values();
        if ($related->isNotEmpty()) {
            $lines[] = 'TERKAIT: '.$related->map->title->implode('; ');
            $lines[] = '';
        }

        $lines[] = 'Buka halaman dokumen untuk konteks lengkap dan riwayat versinya.';

        return [
            'answer' => implode("\n", $lines),
            'sources' => [['document' => $doc, 'passage' => $summary !== '' ? $summary : $doc->title, 'score' => 1, 'section' => 'Ringkasan', 'field' => 'description']],
            'found' => true,
            'ai' => false,
        ];
    }

    /**
     * @return array{document: Document, passage: string, score: int, section: ?string, field: string}
     */
    private function bestPassage(Document $doc, Collection $keywords): array
    {
        $best = ['document' => $doc, 'passage' => '', 'score' => 0, 'section' => null, 'field' => 'title'];

        $candidates = [
            ['text' => $doc->title.' '.$doc->subtitle, 'section' => null, 'field' => 'title'],
            ['text' => $doc->description ?? '', 'section' => 'Ringkasan', 'field' => 'description'],
        ];
        foreach ($this->contentSentences($doc->content) as $c) {
            $candidates[] = $c;
        }

        foreach ($candidates as $c) {
            foreach ($this->sentences($c['text']) as $sentence) {
                $score = $this->score($sentence, $keywords);
                if ($score > $best['score']) {
                    $best = [
                        'document' => $doc, 'passage' => $sentence, 'score' => $score,
                        'section' => $c['section'], 'field' => $c['field'],
                    ];
                }
            }
        }

        return $best;
    }

    /**
     * Pecah konten HTML menjadi kalimat beserta heading h1–h3 terdekat di atasnya
     * (BAB 14): sitasi section-level yang jujur — tanpa nomor halaman rekaan.
     *
     * @return list<array{text: string, section: ?string, field: string}>
     */
    private function contentSentences(?string $html): array
    {
        if ($html === null || $html === '') {
            return [];
        }

        $parts = preg_split('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $out = [];
        $section = null;
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                $section = $this->plainText($part) ?: null;

                continue;
            }
            $text = $this->plainText($part);
            if (trim($text) === '') {
                continue;
            }
            $out[] = ['text' => $text, 'section' => $section, 'field' => 'content'];
        }

        return $out;
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
