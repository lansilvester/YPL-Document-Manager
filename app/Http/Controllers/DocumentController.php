<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\Favorite;
use App\Models\ReadingPosition;
use App\Models\ReadingPref;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    // Fase 2: daftar publik + pencarian + filter kategori
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $cat = $request->query('kategori');

        $docs = Document::query()->with(['category', 'user', 'tags'])
            ->where('visibility', 'public')
            ->when($q, fn ($s) => $s->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")))
            ->when($cat, fn ($s) => $s->whereHas('category', fn ($c) => $c->where('slug', $cat)))
            ->latest()->paginate(12)->withQueryString();

        return view('documents.index', [
            'docs' => $docs, 'q' => $q, 'activeCat' => $cat,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    // Fase 1: buka dokumen + lanjut baca + atur tampilan
    public function show(Document $document)
    {
        $document->load('tags');
        if (! $document->isVisibleTo(auth()->user())) {
            abort(403, 'Dokumen bersifat privat.');
        }

        $document->increment('views_count');
        ActivityLog::record(auth()->id(), $document->id, 'view');

        $user = auth()->user();
        $pref = $user?->readingPref ?? new ReadingPref(['theme' => 'light', 'zoom' => 100, 'layout' => 'single']);
        $position = $user
            ? ReadingPosition::firstOrNew(
                ['user_id' => $user->id, 'document_id' => $document->id],
                ['page' => 1, 'percent' => 0]
            ) : null;
        $isFav = $user ? Favorite::where('user_id', $user->id)->where('document_id', $document->id)->exists() : false;

        return view('documents.show', compact('document', 'pref', 'position', 'isFav'));
    }

    public function download(Document $document)
    {
        if (! $document->isVisibleTo(auth()->user()) || ! $document->file_path) {
            abort(404);
        }
        $document->increment('downloads_count');
        ActivityLog::record(auth()->id(), $document->id, 'download');

        return Storage::disk('public')->download($document->file_path, Str::slug($document->title).'.pdf');
    }

    // Fase 2: unggah PDF + judul & kategori
    public function create()
    {
        return view('documents.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        $path = $request->file('file')->store('documents', 'public');

        $doc = Document::create([
            'user_id' => auth()->id(),
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_size' => $request->file('file')->getSize(),
            'mime' => 'application/pdf',
            'visibility' => $data['visibility'],
            'source_type' => 'upload',
        ]);

        ActivityLog::record(auth()->id(), $doc->id, 'upload');

        return redirect()->route('documents.show', $doc)->with('ok', 'Dokumen berhasil diunggah.');
    }

    // Fase 2: kelola dokumen saya
    public function my()
    {
        $docs = Document::where('user_id', auth()->id())->with('category')->latest()->paginate(12);

        return view('documents.my', compact('docs'));
    }

    public function edit(Document $document)
    {
        $this->authorizeDoc($document);

        return view('documents.edit', ['document' => $document, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Document $document)
    {
        $this->authorizeDoc($document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        if ($request->hasFile('file')) {
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
            $document->file_path = $request->file('file')->store('documents', 'public');
            $document->file_size = $request->file('file')->getSize();
            $document->mime = 'application/pdf';
        }

        $document->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'visibility' => $data['visibility'],
        ])->save();

        ActivityLog::record(auth()->id(), $document->id, 'update');

        return redirect()->route('documents.show', $document)->with('ok', 'Dokumen diperbarui.');
    }

    public function destroy(Document $document)
    {
        $this->authorizeDoc($document);
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        ActivityLog::record(auth()->id(), $document->id, 'delete', ['title' => $document->title]);
        $document->delete();

        return redirect()->route('documents.my')->with('ok', 'Dokumen dihapus.');
    }

    private function authorizeDoc(Document $document): void
    {
        if (auth()->user()->isAdmin() || $document->user_id === auth()->id()) {
            return;
        }
        abort(403);
    }
}
