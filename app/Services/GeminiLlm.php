<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Klien Gemini generateContent untuk Ask YPL (testing only).
 *
 * Hanya menyusun jawaban dari kutipan yang SUDAH lolos scope izin
 * (disiapkan AskYpl). Gagal/timeout → null → fallback ekstraktif.
 * API key tidak pernah ditulis ke log.
 */
class GeminiLlm
{
    public function __construct(
        private string $apiKey = '',
        private string $model = 'gemini-3.8-flash',
        private string $baseUrl = 'https://generativelanguage.googleapis.com',
        private int $timeout = 25,
        private int $maxOutputTokens = 2000,
    ) {
        $this->apiKey = $apiKey !== '' ? $apiKey : (string) config('ask.key');
        $this->model = config('ask.model', $this->model);
        $this->baseUrl = rtrim((string) config('ask.base_url', $this->baseUrl), '/');
        $this->timeout = (int) config('ask.timeout', $this->timeout);
    }

    public static function enabled(): bool
    {
        return config('ask.driver') === 'gemini' && (string) config('ask.key') !== '';
    }

    /**
     * @param  array<int, array{document: Document, passage: string, score: int}>  $sources
     */
    public function compose(string $question, array $sources): ?string
    {
        if ($this->apiKey === '' || $sources === []) {
            return null;
        }

        $context = '';
        foreach ($sources as $i => $hit) {
            $doc = $hit['document'];
            $context .= '['.($i + 1).'] '.$doc->title.' ('.str_replace('_', ' ', $doc->doc_type ?? 'dokumen').' v'.$doc->version.', '.str_replace('_', ' ', $doc->status).'): '.$hit['passage']."\n";
        }

        try {
            $response = $this->post($question, $context);
            if ($this->isOverloaded($response)) {
                if (! app()->runningUnitTests()) {
                    sleep(2);
                }
                $response = $this->post($question, $context);
            }
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        return is_string($text) && trim($text) !== '' ? trim($text) : null;
    }

    private function post(string $question, string $context): Response
    {
        return Http::timeout($this->timeout)
            ->acceptJson()
            ->post($this->baseUrl.'/v1beta/models/'.$this->model.':generateContent', [
                'system_instruction' => ['parts' => [['text' => $this->systemPrompt()]]],
                'contents' => [['parts' => [['text' => "Pertanyaan: {$question}\n\nSumber resmi:\n{$context}"]]]],
                'generationConfig' => ['maxOutputTokens' => $this->maxOutputTokens, 'temperature' => 0.2],
            ]);
    }

    private function isOverloaded(Response $response): bool
    {
        return in_array($response->status(), [429, 503], true);
    }

    private function systemPrompt(): string
    {
        return 'Kamu adalah Ask YPL, asisten knowledge Yayasan Pendidikan Lokon. '
            .'Jawab HANYA dari sumber resmi bernomor yang diberikan, dalam Bahasa Indonesia yang ringkas. '
            .'Setiap klaim penting wajib disertai sitasi nomor sumber seperti [1], [2]. '
            .'Jika sumber tidak cukup menjawab, katakan jujur bahwa sumber resmi belum ditemukan dan jangan mengarang. '
            .'Jangan menyebut dokumen di luar daftar sumber.';
    }
}
