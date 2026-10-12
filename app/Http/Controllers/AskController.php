<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Services\AskYpl;
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
            'question' => ['required', 'string', 'min:3', 'max:500'],
            'doc' => ['nullable', 'string', 'max:255'],
        ]);

        // Scope per-dokumen: slug asing/tak terlihat → 404, tanpa fallback diam-diam.
        $scope = isset($data['doc'])
            ? Document::query()->visibleTo($request->user())->where('slug', $data['doc'])->firstOrFail()
            : null;

        $result = $ask->answer($data['question'], $request->user(), $scope);
        ActivityLog::record(
            $request->user()?->id, $scope?->id, 'ask',
            ['q' => mb_substr($data['question'], 0, 100), 'found' => $result['found'], 'sources' => count($result['sources'])]
        );

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
}
