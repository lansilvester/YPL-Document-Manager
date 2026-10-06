<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\AskYpl;
use Illuminate\Http\Request;

class AskController extends Controller
{
    public function index(): mixed
    {
        return view('ask.index', [
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
        ]);

        $result = $ask->answer($data['question'], $request->user());
        ActivityLog::record(
            $request->user()?->id, null, 'ask',
            ['q' => mb_substr($data['question'], 0, 100), 'found' => $result['found'], 'sources' => count($result['sources'])]
        );

        return view('ask.index', [
            'examples' => [],
            'question' => $data['question'],
            'answer' => $result['answer'],
            'sources' => $result['sources'],
            'found' => $result['found'],
            'ai' => $result['ai'],
        ]);
    }
}
