<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;
use App\Services\DocumentFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Ruang kerja Staff: satu tampilan CRUD untuk tambah/upload PDF,
 * ubah & ganti file, hapus, dan pemberian badge/tag/flag pada dokumen.
 */
class StaffController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $docs = Document::query()
            ->when($q, fn ($s) => $s->search($q))
            ->select([
                'id', 'title', 'slug', 'category_id', 'user_id', 'description',
                'visibility', 'views_count', 'file_path', 'file_size',
            ])
            ->with([
                'category:id,name',
                'user:id,name',
                'tags:id,name,color',
            ])
            ->latest()->paginate(15)->withQueryString();

        return view('staff.documents', [
            'docs' => $docs,
            'q' => $q,
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'pics' => User::whereIn('role', ['staff', 'admin', 'superadmin'])->orderBy('name')->limit(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        DocumentFiles::ensureValidUpload($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
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
            $doc->tags()->sync($data['tags'] ?? []);

            return $doc;
        });

        ActivityLog::record(auth()->id(), $doc->id, 'upload', ['via' => 'staff']);

        return back()->with('ok', "Dokumen “{$doc->title}” berhasil ditambahkan.");
    }

    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);
        DocumentFiles::ensureValidUpload($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
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
            $document->tags()->sync($data['tags'] ?? []);
        });

        ActivityLog::record(auth()->id(), $document->id, 'update', ['via' => 'staff']);

        return back()->with('ok', "Dokumen “{$document->title}” diperbarui.");
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);
        $path = $document->file_path;
        $title = $document->title;
        DB::transaction(function () use ($document) {
            $document->tags()->detach();
            $document->delete();
        });
        if ($path) {
            Storage::disk('private')->delete($path);
        }
        ActivityLog::record(auth()->id(), null, 'delete', ['via' => 'staff', 'title' => $title]);

        return back()->with('ok', 'Dokumen dihapus dari perpustakaan.');
    }

    public function storeTag(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required', Rule::in(Tag::COLORS)],
        ]);

        Tag::firstOrCreate(
            ['slug' => Str::slug($data['name'])],
            ['name' => $data['name'], 'color' => $data['color']]
        );

        return back()->with('ok', "Badge “{$data['name']}” ditambahkan.");
    }

    public function destroyTag(Tag $tag)
    {
        $tag->delete();

        return back()->with('ok', 'Badge dihapus.');
    }
}
