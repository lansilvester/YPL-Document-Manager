<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Favorite;
use App\Models\ReadingPosition;
use App\Models\ReadingPref;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    // Fase 3: simpan ke favorit (toggle bookmark)
    public function toggleFavorite(Document $document)
    {
        $fav = Favorite::where('user_id', auth()->id())->where('document_id', $document->id)->first();
        if ($fav) {
            $fav->delete();

            return back()->with('ok', 'Dihapus dari favorit.');
        }

        Favorite::create(['user_id' => auth()->id(), 'document_id' => $document->id]);
        ActivityLog::record(auth()->id(), $document->id, 'favorite');

        return back()->with('ok', 'Ditambahkan ke favorit.');
    }

    public function favorites()
    {
        $docs = Document::whereIn('id', Favorite::where('user_id', auth()->id())->pluck('document_id'))
            ->with(['category', 'user'])->latest()->paginate(12);

        return view('documents.favorites', compact('docs'));
    }

    // Fase 3: bagikan dokumen (tautan internal + medsos)
    public function share(Document $document)
    {
        if (! $document->isVisibleTo(auth()->user())) {
            abort(403);
        }
        $url = route('documents.show', $document);

        return view('documents.share', compact('document', 'url'));
    }

    // Fase 1: sinkronisasi posisi baca (API)
    public function savePosition(Request $request, Document $document)
    {
        $data = $request->validate([
            'page' => ['required', 'integer', 'min:1'],
            'total_pages' => ['nullable', 'integer', 'min:1'],
            'percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        ReadingPosition::updateOrCreate(
            ['user_id' => auth()->id(), 'document_id' => $document->id],
            ['page' => $data['page'], 'total_pages' => $data['total_pages'] ?? null,
             'percent' => $data['percent'] ?? 0]
        );

        return response()->json(['ok' => true]);
    }

    // Fase 1: preferensi tampilan baca (API)
    public function savePrefs(Request $request)
    {
        $data = $request->validate([
            'theme' => ['required', 'in:light,dark'],
            'zoom' => ['required', 'integer', 'min:50', 'max:250'],
            'layout' => ['required', 'in:single,double'],
        ]);

        ReadingPref::updateOrCreate(['user_id' => auth()->id()], $data);

        return response()->json(['ok' => true]);
    }
}
