<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class FaqController extends Controller
{
    public function show(Faq $faq): mixed
    {
        abort_unless($faq->isVisibleTo(auth()->user()), 404);

        $docs = $faq->documents()->with(['category', 'user'])
            ->visibleTo(auth()->user())->latest()->paginate(12);

        return view('faqs.show', compact('faq', 'docs'));
    }
}
