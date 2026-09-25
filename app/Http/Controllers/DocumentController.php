<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\Favorite;
use App\Models\Like;
use App\Models\ReadingPosition;
use App\Models\ReadingPref;
use App\Services\HtmlSanitizer;
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

        $recentView = ActivityLog::where('document_id', $document->id)
            ->where('action', 'view')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->when(auth()->check(), fn ($query) => $query->where('user_id', auth()->id()), fn ($query) => $query->whereNull('user_id')->where('ip', request()->ip()))
            ->exists();
        if (! $recentView) {
            $document->increment('views_count');
            ActivityLog::record(auth()->id(), $document->id, 'view');
        }

        $user = auth()->user();
        $pref = $user?->readingPref ?? new ReadingPref(['theme' => 'light', 'zoom' => 100, 'layout' => 'single']);
        $position = $user
            ? ReadingPosition::firstOrNew(
                ['user_id' => $user->id, 'document_id' => $document->id],
                ['page' => 1, 'percent' => 0]
            ) : null;
        $isFav = $user ? Favorite::where('user_id', $user->id)->where('document_id', $document->id)->exists() : false;
        $liked = $user ? Like::where('user_id', $user->id)->where('document_id', $document->id)->exists() : false;
        $likesCount = $document->likes()->count();
        // Kode semat hanya ditawarkan untuk dokumen publik, atau kepada staf/pemilik/admin
        $canEmbed = $document->visibility === 'public'
            || ($user && ($user->isStaff() || $document->user_id === $user->id));
        $embedCode = '<iframe src="'.route('documents.embed', $document).'" width="100%" height="600" style="border:1px solid #d6d6d6;border-radius:12px" allowfullscreen loading="lazy" title="'.e($document->title).'"></iframe>';
        $safeContent = $document->source_type === 'editor' && $document->content
            ? HtmlSanitizer::clean($document->content)
            : null;

        return view('documents.show', compact('document', 'pref', 'position', 'isFav', 'liked', 'likesCount', 'canEmbed', 'embedCode', 'safeContent'));
    }

    public function stream(Document $document)
    {
        abort_unless($document->isVisibleTo(auth()->user()) && $document->file_path, 404);

        $disk = Storage::disk('private');
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->response($document->file_path, Str::slug($document->title).'.pdf', [
            'Content-Type' => $document->mime ?: 'application/pdf',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(Document $document)
    {
        abort_unless(\App\Models\Setting::feature('download'), 404, 'Fitur unduh dinonaktifkan.');
        if (! $document->isVisibleTo(auth()->user()) || ! $document->file_path || ! Storage::disk('private')->exists($document->file_path)) {
            abort(404);
        }
        $document->increment('downloads_count');
        ActivityLog::record(auth()->id(), $document->id, 'download');

        return Storage::disk('private')->download($document->file_path, Str::slug($document->title).'.pdf');
    }

    // Unduh sebagai HTML: hanya anggota terdaftar (route di balik auth)
    public function downloadHtml(Document $document)
    {
        abort_unless(\App\Models\Setting::feature('download'), 404, 'Fitur unduh dinonaktifkan.');
        if (! $document->isVisibleTo(auth()->user())) {
            abort(403);
        }

        $body = $document->source_type === 'editor' && $document->content
            ? HtmlSanitizer::clean($document->content)
            : '<p><em>Dokumen ini berupa berkas PDF. Unduh versi PDF untuk isi lengkapnya.</em></p>'
              .($document->description ? '<p>'.e($document->description).'</p>' : '');

        $html = '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">'
            .'<title>'.e($document->title).' — Lokon Dokumen Digital</title></head>'
            .'<body style="font-family:sans-serif;max-width:720px;margin:2rem auto;line-height:1.7">'
            .'<h1>'.e($document->title).'</h1>'
            .'<p><small>Oleh '.e($document->user->name).' · Yayasan Pendidikan Lokon · Diunduh '.now()->format('d M Y').'</small></p><hr>'
            .$body.'<hr><p><small>Sumber: '.e(url()->current()).'</small></p></body></html>';

        ActivityLog::record(auth()->id(), $document->id, 'download', ['format' => 'html']);

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, Str::slug($document->title).'.html', ['Content-Type' => 'text/html; charset=utf-8']);
    }

    // Sematan (embed) minimal untuk iframe. Tamu: hanya dokumen publik.
    public function embed(Document $document)
    {
        abort_unless(\App\Models\Setting::feature('embed'), 404, 'Fitur semat dinonaktifkan.');
        if (! $document->isVisibleTo(auth()->user())) {
            abort(403, 'Dokumen tidak dapat disematkan.');
        }

        return view('documents.embed', compact('document'));
    }

    // Fase 2: unggah PDF + judul & kategori
    public function create()
    {
        abort_unless(\App\Models\Setting::feature('upload'), 404, 'Fitur unggah dinonaktifkan.');

        return view('documents.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless(\App\Models\Setting::feature('upload'), 404, 'Fitur unggah dinonaktifkan.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        $doc = \App\Services\DocumentFiles::transactional(function (array &$gate) use ($request, $data) {
            [$path, $size] = \App\Services\DocumentFiles::storeUpload($request->file('file'), $gate);

            return Document::create([
                'user_id' => auth()->id(),
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
                'description' => $data['description'] ?? null,
                'file_path' => $path,
                'file_size' => $size,
                'mime' => 'application/pdf',
                'visibility' => $data['visibility'],
                'source_type' => 'upload',
            ]);
        });

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
        $this->authorize('update', $document);

        return view('documents.edit', ['document' => $document, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        \App\Services\DocumentFiles::transactional(function (array &$gate) use ($request, $data, $document) {
            if ($request->hasFile('file')) {
                [$document->file_path, $document->file_size] = \App\Services\DocumentFiles::replace($document->file_path, $request->file('file'), $gate);
                $document->mime = 'application/pdf';
            }

            $document->fill([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'visibility' => $data['visibility'],
            ])->save();
        });

        ActivityLog::record(auth()->id(), $document->id, 'update');

        return redirect()->route('documents.show', $document)->with('ok', 'Dokumen diperbarui.');
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);
        $path = $document->file_path;
        $title = $document->title;
        \Illuminate\Support\Facades\DB::transaction(function () use ($document) {
            $document->delete();
        });
        // Berkas dihapus setelah record ter-commit: tidak ada risiko record tanpa berkas.
        if ($path) {
            Storage::disk('private')->delete($path);
        }
        ActivityLog::record(auth()->id(), null, 'delete', ['title' => $title]);

        return redirect()->route('documents.my')->with('ok', 'Dokumen dihapus.');
    }

}
