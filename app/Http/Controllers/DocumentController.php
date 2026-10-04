<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentRelation;
use App\Models\DocumentVersion;
use App\Models\Like;
use App\Models\ReadingPosition;
use App\Models\ReadingPref;
use App\Models\Setting;
use App\Models\System;
use App\Models\User;
use App\Services\DocumentFiles;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->when($q, fn ($s) => $s->search($q))
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
        $document->load(['tags', 'approver', 'pic', 'versions.user', 'outgoingRelations.related', 'outgoingRelations.system']);
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
        $isMarked = $user ? Bookmark::where('user_id', $user->id)->where('document_id', $document->id)->exists() : false;
        $liked = $user ? Like::where('user_id', $user->id)->where('document_id', $document->id)->exists() : false;
        $likesCount = $document->likes()->count();
        // Kode semat hanya ditawarkan untuk dokumen publik, atau kepada staf/pemilik/admin
        $canEmbed = $document->visibility === 'public'
            || ($user && ($user->isStaff() || $document->user_id === $user->id));
        $embedCode = '<iframe src="'.route('documents.embed', $document).'" width="100%" height="600" style="border:1px solid #d6d6d6;border-radius:12px" allowfullscreen loading="lazy" title="'.e($document->title).'"></iframe>';
        $safeContent = $document->source_type === 'editor' && $document->content
            ? HtmlSanitizer::clean($document->content)
            : null;

        // Dokumen sejenis: tag/flag sama; fallback kategori; terakhir semua dokumen lain.
        $tagIds = $document->tags->pluck('id');
        $related = Document::query()->with(['category', 'user', 'tags'])
            ->where('visibility', 'public')
            ->where('id', '!=', $document->id)
            ->when($tagIds->isNotEmpty(), fn ($q) => $q->whereHas('tags', fn ($t) => $t->whereIn('tags.id', $tagIds)))
            ->when($tagIds->isEmpty(), fn ($q) => $q->where('category_id', $document->category_id))
            ->latest()
            ->limit(6)
            ->get();
        if ($related->isEmpty()) {
            $related = Document::query()->with(['category', 'user', 'tags'])
                ->where('visibility', 'public')
                ->where('id', '!=', $document->id)
                ->latest()
                ->limit(6)
                ->get();
        }

        // Dokumen populer: paling sering dibaca.
        $popular = Document::query()->with(['category', 'user', 'tags'])
            ->where('visibility', 'public')
            ->where('id', '!=', $document->id)
            ->orderByDesc('views_count')
            ->limit(6)
            ->get();

        // Phase 3 — transisi lifecycle yang boleh dilakukan user ini.
        $transitions = [];
        if (auth()->check()) {
            foreach (Document::TRANSITIONS[$document->status] ?? [] as $next) {
                if (auth()->user()->can('transition', [$document, $next])) {
                    $transitions[] = $next;
                }
            }
        }

        return view('documents.show', compact('document', 'pref', 'position', 'isMarked', 'liked', 'likesCount', 'canEmbed', 'embedCode', 'safeContent', 'related', 'popular', 'transitions'));
    }

    /**
     * Phase 3 — pindahkan status lifecycle dengan audit trail versi.
     */
    public function transition(Request $request, Document $document)
    {
        $data = $request->validate([
            'to' => ['required', 'string', 'in:'.implode(',', Document::STATUSES)],
            'change_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->authorize('transition', [$document, $data['to']]);

        $from = $document->status;
        DB::transaction(function () use ($document, $data, $from) {
            DocumentVersion::create([
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'version' => $document->version,
                'from_status' => $from,
                'to_status' => $data['to'],
                'change_note' => $data['change_note'] ?? null,
            ]);
            $document->update(array_filter([
                'status' => $data['to'],
                'approver_id' => $data['to'] === 'active' ? auth()->id() : $document->approver_id,
            ]));
        });
        ActivityLog::record(auth()->id(), $document->id, 'transition', ['from' => $from, 'to' => $data['to']]);

        return back()->with('ok', "Status berubah: {$from} → {$data['to']}.");
    }

    /**
     * Phase 3–4 — sambungkan dokumen/sistem terkait (connected knowledge).
     */
    public function attachRelation(Request $request, Document $document)
    {
        $this->authorize('relate', $document);

        $data = $request->validate([
            'related_slug' => ['nullable', 'string', 'max:255', 'required_without:system_slug'],
            'system_slug' => ['nullable', 'string', 'max:60', 'required_without:related_slug', 'exists:systems,slug'],
            'relation_type' => ['nullable', 'string', 'in:'.implode(',', DocumentRelation::TYPES)],
        ]);
        $data['relation_type'] ??= 'related';

        if (! empty($data['related_slug'])) {
            $related = Document::where('slug', $data['related_slug'])->firstOrFail();
            abort_if($related->id === $document->id, 422, 'Dokumen tidak dapat direlasikan ke dirinya sendiri.');

            DocumentRelation::firstOrCreate(
                ['document_id' => $document->id, 'related_id' => $related->id, 'system_id' => null],
                ['relation_type' => $data['relation_type']]
            );
        } else {
            $system = System::where('slug', $data['system_slug'])->firstOrFail();

            DocumentRelation::firstOrCreate(
                ['document_id' => $document->id, 'related_id' => null, 'system_id' => $system->id],
                ['relation_type' => 'system']
            );
        }

        return back()->with('ok', 'Relasi pengetahuan ditambahkan.');
    }

    public function detachRelation(Document $document, DocumentRelation $relation)
    {
        abort_unless($relation->document_id === $document->id, 404);
        $this->authorize('relate', $document);
        $relation->delete();

        return back()->with('ok', 'Relasi pengetahuan dihapus.');
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
        abort_unless(Setting::feature('download'), 404, 'Fitur unduh dinonaktifkan.');
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
        abort_unless(Setting::feature('download'), 404, 'Fitur unduh dinonaktifkan.');
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
        abort_unless(Setting::feature('embed'), 404, 'Fitur semat dinonaktifkan.');
        if (! $document->isVisibleTo(auth()->user())) {
            abort(403, 'Dokumen tidak dapat disematkan.');
        }

        return view('documents.embed', compact('document'));
    }

    // Fase 2: unggah PDF + judul & kategori
    public function create()
    {
        abort_unless(Setting::feature('upload'), 404, 'Fitur unggah dinonaktifkan.');

        return view('documents.create', [
            'categories' => Category::orderBy('name')->get(),
            'pics' => User::whereIn('role', ['staff', 'admin', 'superadmin'])->orderBy('name')->limit(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        DocumentFiles::ensureValidUpload($request);
        abort_unless(Setting::feature('upload'), 404, 'Fitur unggah dinonaktifkan.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:9216'],
            'doc_type' => ['nullable', 'string', 'in:'.implode(',', Document::TYPES)],
            'classification' => ['nullable', 'string', 'in:internal,confidential,public'],
            'version' => ['nullable', 'string', 'max:20'],
            'effective_at' => ['nullable', 'date'],
            'review_at' => ['nullable', 'date', 'after_or_equal:effective_at'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', 'array'],
            'audience.*' => ['string', 'in:'.implode(',', Document::AUDIENCES)],
            'unit' => ['nullable', 'string', 'max:60'],
            'pic_id' => ['nullable', 'exists:users,id'],
            'pic_name' => ['nullable', 'string', 'max:100'],
        ]);

        $doc = DocumentFiles::transactional(function (array &$gate) use ($request, $data) {
            [$path, $size] = DocumentFiles::storeUpload($request->file('file'), $gate);

            $doc = Document::create([
                'user_id' => auth()->id(),
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
                'subtitle' => $data['subtitle'] ?? null,
                'description' => $data['description'] ?? null,
                'file_path' => $path,
                'file_size' => $size,
                'mime' => 'application/pdf',
                'visibility' => $data['visibility'],
                'source_type' => 'upload',
                'doc_type' => $data['doc_type'] ?? null,
                'classification' => $data['classification'] ?? null,
                'version' => $data['version'] ?? '1.0',
                'effective_at' => $data['effective_at'] ?? null,
                'review_at' => $data['review_at'] ?? null,
                'audience' => $data['audience'] ?? null,
                'unit' => $data['unit'] ?? null,
                'pic_id' => $data['pic_id'] ?? null,
                'pic_name' => $data['pic_name'] ?? null,
            ]);
            $doc->update(['doc_code' => 'YPL-'.strtoupper($doc->doc_type ?? 'DOC').'-'.$doc->id]);

            return $doc;
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

        return view('documents.edit', [
            'document' => $document,
            'categories' => Category::orderBy('name')->get(),
            'pics' => User::whereIn('role', ['staff', 'admin', 'superadmin'])->orderBy('name')->limit(100)->get(),
        ]);
    }

    public function update(Request $request, Document $document)
    {
        DocumentFiles::ensureValidUpload($request);
        $this->authorize('update', $document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:9216'],
            'doc_type' => ['nullable', 'string', 'in:'.implode(',', Document::TYPES)],
            'classification' => ['nullable', 'string', 'in:internal,confidential,public'],
            'version' => ['nullable', 'string', 'max:20'],
            'effective_at' => ['nullable', 'date'],
            'review_at' => ['nullable', 'date', 'after_or_equal:effective_at'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', 'array'],
            'audience.*' => ['string', 'in:'.implode(',', Document::AUDIENCES)],
            'unit' => ['nullable', 'string', 'max:60'],
            'pic_id' => ['nullable', 'exists:users,id'],
            'pic_name' => ['nullable', 'string', 'max:100'],
        ]);

        DocumentFiles::transactional(function (array &$gate) use ($request, $data, $document) {
            if ($request->hasFile('file')) {
                [$document->file_path, $document->file_size] = DocumentFiles::replace($document->file_path, $request->file('file'), $gate);
                $document->mime = 'application/pdf';
            }

            $document->fill([
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? $document->subtitle,
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'visibility' => $data['visibility'],
                'doc_type' => $data['doc_type'] ?? $document->doc_type,
                'classification' => $data['classification'] ?? $document->classification,
                'version' => $data['version'] ?? $document->version,
                'effective_at' => $data['effective_at'] ?? $document->effective_at,
                'review_at' => $data['review_at'] ?? $document->review_at,
                'audience' => array_key_exists('audience', $data) ? $data['audience'] : $document->audience,
                'unit' => $data['unit'] ?? $document->unit,
                'pic_id' => array_key_exists('pic_id', $data) ? $data['pic_id'] : $document->pic_id,
                'pic_name' => $data['pic_name'] ?? $document->pic_name,
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
        DB::transaction(function () use ($document) {
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
