<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Faq;

class FaqController extends Controller
{
    public function show(Faq $faq): mixed
    {
        abort_unless($faq->isVisibleTo(auth()->user()), 404);

        $docs = Document::query()->forCard()
            ->whereIn('documents.id', $faq->documents()->select('documents.id'))
            ->visibleTo(auth()->user())->latest()->paginate(12);

        return view('faqs.show', compact('faq', 'docs'));
    }
}
