<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use App\Models\Setting;
use App\Services\DocumentFiles;
use App\Services\HtmlSanitizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EditorController extends Controller
{
    // Fase 3: tulis isi dokumen langsung di platform
    public function create()
    {
        abort_unless(Setting::feature('editor'), 404, 'Fitur tulis dinonaktifkan.');

        return view('editor.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless(Setting::feature('editor'), 404, 'Fitur tulis dinonaktifkan.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'content' => ['required', 'string', 'max:200000'],
        ]);
        $data['content'] = HtmlSanitizer::clean($data['content']);

        $doc = DocumentFiles::transactional(function (array &$gate) use ($data) {
            $pdfPath = 'documents/editor-'.Str::random(12).'.pdf';
            DocumentFiles::putAtomically($pdfPath, Pdf::loadHTML('<h1>'.e($data['title']).'</h1><hr>'.$data['content'])->setPaper('a4')->output());
            $gate['paths'][] = $pdfPath;

            return Document::create([
                'user_id' => auth()->id(),
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
                'description' => $data['description'] ?? null,
                'file_path' => $pdfPath,
                'mime' => 'application/pdf',
                'visibility' => $data['visibility'],
                'source_type' => 'editor',
                'content' => $data['content'],
                'version' => '1.0',
            ]);
        });

        $doc->refresh();
        $doc->recordVersion(null, 'Dokumen dibuat via editor.', $doc->snapshot());

        ActivityLog::record(auth()->id(), $doc->id, 'upload', ['via' => 'editor']);

        return redirect()->route('documents.show', $doc)->with('ok', 'Dokumen dibuat dan disimpan sebagai PDF.');
    }

    // Fase 3: ubah dokumen tersimpan tanpa unggah ulang
    public function edit(Document $document)
    {
        abort_unless(Setting::feature('editor'), 404, 'Fitur tulis dinonaktifkan.');
        if ($document->source_type !== 'editor') {
            return redirect()->route('documents.edit', $document);
        }
        $this->authorize('update', $document);

        return view('editor.edit', ['document' => $document, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Document $document)
    {
        abort_unless(Setting::feature('editor'), 404, 'Fitur tulis dinonaktifkan.');
        $this->authorize('update', $document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'content' => ['required', 'string', 'max:200000'],
        ]);
        $data['content'] = HtmlSanitizer::clean($data['content']);

        DocumentFiles::transactional(function () use ($data, $document) {
            $this->renderPdf($data['title'], $data['content'], $document->file_path);

            $document->fill([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'visibility' => $data['visibility'],
                'content' => $data['content'],
                'mime' => 'application/pdf',
                'version' => $document->nextVersion(),
            ])->save();

            $document->recordVersion($document->status, 'Perubahan isi dokumen via editor.');
        });

        ActivityLog::record(auth()->id(), $document->id, 'update', ['via' => 'editor']);

        return redirect()->route('documents.show', $document)->with('ok', 'Dokumen diperbarui dan PDF dibuat ulang.');
    }

    private function renderPdf(string $title, string $html, ?string $existing = null): string
    {
        $safe = '<h1>'.e($title).'</h1><hr>'.$html;
        $pdf = Pdf::loadHTML($safe)->setPaper('a4');
        $path = $existing && str_starts_with($existing, 'documents/editor-')
            ? $existing
            : 'documents/editor-'.Str::random(12).'.pdf';
        // Tulis atomik (temp + rename): pembaca concurrent tidak pernah melihat file setengah tulis.
        DocumentFiles::putAtomically($path, $pdf->output());

        return $path;
    }
}
