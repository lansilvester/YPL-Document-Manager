<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EditorController extends Controller
{
    // Fase 3: tulis isi dokumen langsung di platform
    public function create()
    {
        return view('editor.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'content' => ['required', 'string', 'max:200000'],
        ]);

        $pdfPath = $this->renderPdf($data['title'], $data['content']);

        $doc = Document::create([
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
        ]);

        ActivityLog::record(auth()->id(), $doc->id, 'upload', ['via' => 'editor']);

        return redirect()->route('documents.show', $doc)->with('ok', 'Dokumen dibuat dan disimpan sebagai PDF.');
    }

    // Fase 3: ubah dokumen tersimpan tanpa unggah ulang
    public function edit(Document $document)
    {
        if ($document->source_type !== 'editor') {
            return redirect()->route('documents.edit', $document);
        }
        $this->authorizeDoc($document);

        return view('editor.edit', ['document' => $document, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Document $document)
    {
        $this->authorizeDoc($document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'visibility' => ['required', 'in:public,private,restricted'],
            'content' => ['required', 'string', 'max:200000'],
        ]);

        $document->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'visibility' => $data['visibility'],
            'content' => $data['content'],
            'file_path' => $this->renderPdf($data['title'], $data['content'], $document->file_path),
            'mime' => 'application/pdf',
        ])->save();

        ActivityLog::record(auth()->id(), $document->id, 'update', ['via' => 'editor']);

        return redirect()->route('documents.show', $document)->with('ok', 'Dokumen diperbarui dan PDF dibuat ulang.');
    }

    private function renderPdf(string $title, string $html, ?string $existing = null): string
    {
        $safe = '<h1>'.e($title).'</h1><hr>'.$html; // konten editor (disanitasi saat tampil)
        $pdf = Pdf::loadHTML($safe)->setPaper('a4');
        $path = $existing && str_starts_with($existing, 'documents/editor-')
            ? $existing
            : 'documents/editor-'.Str::random(12).'.pdf';
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    private function authorizeDoc(Document $document): void
    {
        if (auth()->user()->isAdmin() || $document->user_id === auth()->id()) {
            return;
        }
        abort(403);
    }
}
