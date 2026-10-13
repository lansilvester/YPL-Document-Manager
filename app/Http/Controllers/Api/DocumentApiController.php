<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DocumentResource;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Setting;
use App\Services\DocumentFiles;
use App\Services\HtmlSanitizer;
use App\Services\RecommendationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentApiController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $user = $request->user();

        $docs = Document::query()
            ->visibleTo($user)
            ->when($data['q'] ?? null, fn ($q, $term) => $q->searchAny($term))
            ->when($data['category'] ?? null, fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($data['type'] ?? null, fn ($q, $t) => $q->where('doc_type', $t))
            ->with(['category:id,name,slug', 'user:id,name'])
            ->orderByDesc('created_at')
            ->paginate($data['per_page'] ?? 15)->withQueryString();

        return DocumentResource::collection($docs);
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403, 'Dokumen bersifat privat.');

        $document->load(['category', 'user:id,name', 'approver:id,name', 'pic:id,name', 'tags']);

        return $this->ok(new DocumentResource($document));
    }

    /**
     * Isi dokumen editor (HTML ter-sanitasi) untuk Reader/klien.
     */
    public function content(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403, 'Dokumen bersifat privat.');

        $content = $document->usesStructuredReader() && $document->content
            ? HtmlSanitizer::clean($document->content)
            : null;

        return $this->ok([
            'slug' => $document->slug,
            'source_type' => $document->source_type,
            'uses_structured_reader' => $document->usesStructuredReader(),
            'content' => $content,
            'file_url' => $document->file_path ? $document->fileUrl() : null,
        ]);
    }

    public function related(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403, 'Dokumen bersifat privat.');

        $related = (new RecommendationEngine(8))->forDocument($document, $request->user());

        return $this->ok(
            $related->map(fn (array $hit) => [
                'score' => $hit['score'],
                'reasons' => $hit['reasons'],
                'document' => new DocumentResource($hit['document']),
            ])->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        DocumentFiles::ensureValidUpload($request);
        abort_unless(Setting::feature('upload'), 403, 'Fitur unggah dinonaktifkan.');

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
                'user_id' => $request->user()->id,
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
            $doc->refresh();
            $doc->recordVersion(null, 'Dokumen dibuat.', $doc->snapshot(), $request->user()->id);

            return $doc;
        });

        ActivityLog::record($request->user()->id, $doc->id, 'upload', ['via' => 'api']);

        return $this->created(new DocumentResource($doc), 'Dokumen berhasil diunggah.', route('documents.show', $doc));
    }

    public function update(Request $request, Document $document): JsonResponse
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
            'change_note' => ['nullable', 'string', 'max:500'],
        ]);

        DocumentFiles::transactional(function (array &$gate) use ($request, $data, $document) {
            $requested = $data['version'] ?? null;
            $bump = $requested === null || $requested === $document->version;

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
                'version' => $bump ? $document->nextVersion() : $requested,
                'effective_at' => $data['effective_at'] ?? $document->effective_at,
                'review_at' => $data['review_at'] ?? $document->review_at,
                'audience' => array_key_exists('audience', $data) ? $data['audience'] : $document->audience,
                'unit' => $data['unit'] ?? $document->unit,
                'pic_id' => array_key_exists('pic_id', $data) ? $data['pic_id'] : $document->pic_id,
                'pic_name' => $data['pic_name'] ?? $document->pic_name,
            ])->save();

            $note = $request->input('change_note') ?: 'Perubahan metadata'.($request->hasFile('file') ? ' & berkas PDF.' : '.');
            $document->recordVersion($document->status, $note, null, $request->user()->id);
        });

        ActivityLog::record($request->user()->id, $document->id, 'update', ['via' => 'api']);

        return $this->ok(new DocumentResource($document->fresh()->load(['category', 'user:id,name', 'tags'])), 'Dokumen diperbarui.');
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $path = $document->file_path;
        $title = $document->title;
        DB::transaction(fn () => $document->delete());
        if ($path) {
            Storage::disk('private')->delete($path);
        }
        ActivityLog::record($request->user()->id, null, 'delete', ['title' => $title, 'via' => 'api']);

        return $this->ok(null, 'Dokumen dihapus.');
    }
}
