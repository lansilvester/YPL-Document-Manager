<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\Tag;
use Illuminate\Http\Request;
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

        $docs = Document::query()->with(['category', 'user', 'tags'])
            ->when($q, fn ($s) => $s->search($q))
            ->latest()->paginate(15)->withQueryString();

        return view('staff.documents', [
            'docs' => $docs,
            'q' => $q,
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        \App\Services\DocumentFiles::ensureValidUpload($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        $doc = \App\Services\DocumentFiles::transactional(function (array &$gate) use ($request, $data) {
            [$path, $size] = \App\Services\DocumentFiles::storeUpload($request->file('file'), $gate);

            $doc = Document::create([
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
            $doc->tags()->sync($data['tags'] ?? []);

            return $doc;
        });

        ActivityLog::record(auth()->id(), $doc->id, 'upload', ['via' => 'staff']);

        return back()->with('ok', "Dokumen “{$doc->title}” berhasil ditambahkan.");
    }

    public function update(Request $request, Document $document)
    {
        \App\Services\DocumentFiles::ensureValidUpload($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
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
            $document->tags()->sync($data['tags'] ?? []);
        });

        ActivityLog::record(auth()->id(), $document->id, 'update', ['via' => 'staff']);

        return back()->with('ok', "Dokumen “{$document->title}” diperbarui.");
    }

    public function destroy(Document $document)
    {
        $path = $document->file_path;
        $title = $document->title;
        \Illuminate\Support\Facades\DB::transaction(function () use ($document) {
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
