<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Services\AskYpl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AskController extends Controller
{
    public function index(Request $request): mixed
    {
        $data = $request->validate(['doc' => ['nullable', 'string', 'max:255']]);
        $scope = isset($data['doc'])
            ? Document::query()->visibleTo($request->user())->where('slug', $data['doc'])->first()
            : null;

        return view('ask.index', [
            'scope' => $scope,
            'examples' => [
                'Bagaimana cara mengajukan purchase request?',
                'Apa saja tata tertib asrama?',
                'Siapa PIC pengadaan barang?',
            ],
        ]);
    }

    public function ask(Request $request, AskYpl $ask): mixed
    {
        $data = $request->validate([
            'question' => ['nullable', 'string', 'min:3', 'max:500', 'required_without:explain'],
            'doc' => ['nullable', 'string', 'max:255'],
            'explain' => ['nullable', 'boolean'],
        ]);

        // Scope per-dokumen: slug asing/tak terlihat → 404, tanpa fallback diam-diam.
        $scope = isset($data['doc'])
            ? Document::query()->visibleTo($request->user())->where('slug', $data['doc'])->firstOrFail()
            : null;

        if (! empty($data['explain'])) {
            abort_unless($scope, 422, 'Mode ringkas membutuhkan konteks dokumen.');
            $result = $ask->explain($scope, $request->user());
            $data['question'] = 'Ringkas dokumen ini';
        } else {
            $result = $ask->answer($data['question'], $request->user(), $scope);
        }
        ActivityLog::record(
            $request->user()?->id, $scope?->id, 'ask',
            ['q' => mb_substr($data['question'], 0, 100), 'found' => $result['found'], 'sources' => count($result['sources'])]
        );

        // Respons JSON untuk panel Ask inline di Reader (BAB 8/14) — bentuk sumber
        // terstruktur agar klien tak perlu mengurai HTML.
        if ($request->wantsJson()) {
            return $this->jsonResponse($result, $scope);
        }

        return view('ask.index', [
            'examples' => [],
            'scope' => $scope,
            'doc' => $data['doc'] ?? null,
            'question' => $data['question'],
            'answer' => $result['answer'],
            'sources' => $result['sources'],
            'found' => $result['found'],
            'ai' => $result['ai'],
        ]);
    }

    /**
     * Proyeksi sumber Ask ke bentuk JSON yang stabil untuk panel inline.
     *
     * @param  array{answer: string, sources: array<int, array{document: Document, passage: string, score: int, section?: ?string}>, found: bool, ai: bool}  $result
     */
    private function jsonResponse(array $result, ?Document $scope): JsonResponse
    {
        return response()->json([
            'answer' => $result['answer'],
            'found' => $result['found'],
            'ai' => $result['ai'],
            'scope' => $scope?->slug,
            'sources' => collect($result['sources'])->map(fn (array $hit) => [
                'title' => $hit['document']->title,
                'url' => route('documents.show', $hit['document']),
                'section' => $hit['section'] ?? null,
                'passage' => $hit['passage'],
                'doc_type' => strtoupper(str_replace('_', ' ', $hit['document']->doc_type ?? 'dokumen')),
                'status' => strtoupper(str_replace('_', ' ', (string) $hit['document']->status)),
                'version' => (string) $hit['document']->version,
            ])->values(),
        ]);
    }
}
