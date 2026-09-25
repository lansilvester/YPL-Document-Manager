@extends('layout')
@section('title', $document->title)
@section('content')
<div class="flex flex-wrap items-start gap-4 mb-4">
  <div class="flex-1 min-w-60">
    <div class="text-xs text-emerald-700 font-semibold">{{ $document->category->name ?? 'Tanpa kategori' }} · {{ ucfirst($document->visibility) }}</div>
    <h1 class="text-2xl font-bold">{{ $document->title }}</h1>
    <p class="text-sm text-stone-600">Oleh {{ $document->user->name }} · 👁 {{ $document->views_count }} · ⬇ {{ $document->downloads_count }}</p>
    @if($document->description)<p class="mt-2 text-sm">{{ $document->description }}</p>@endif
  </div>
  <div class="flex flex-wrap gap-2 text-sm">
    @if($document->file_path)<a href="{{ route('documents.download', $document) }}" class="bg-emerald-700 text-white px-3 py-1.5 rounded">⬇ Unduh PDF</a>@endif
    @auth
      <form method="post" action="{{ route('documents.favorite', $document) }}">@csrf<button class="border rounded px-3 py-1.5 bg-white">{{ $isFav ? '★ Favorit' : '☆ Favorit' }}</button></form>
      <a href="{{ route('documents.share', $document) }}" class="border rounded px-3 py-1.5 bg-white">🔗 Bagikan</a>
      @if(auth()->user()->isAdmin() || $document->user_id === auth()->id())
        @if($document->source_type === 'editor')
          <a href="{{ route('editor.edit', $document) }}" class="border rounded px-3 py-1.5 bg-white">✍ Ubah Isi</a>
        @else
          <a href="{{ route('documents.edit', $document) }}" class="border rounded px-3 py-1.5 bg-white">✏ Ubah</a>
        @endif
      @endif
    @endauth
  </div>
</div>

{{-- Fase 1: penampil + atur tampilan + lanjut baca --}}
<div id="reader" data-theme="{{ $pref->theme }}" class="rounded shadow overflow-hidden border {{ $pref->theme === 'dark' ? 'bg-stone-900' : 'bg-white' }}">
  <div class="flex flex-wrap items-center gap-2 px-3 py-2 border-b text-sm {{ $pref->theme === 'dark' ? 'bg-stone-800 text-stone-100 border-stone-700' : 'bg-stone-50' }}">
    <button id="btn-prev" class="border rounded px-2 py-1">←</button>
    <span><span id="pg-now">1</span> / <span id="pg-total">…</span></span>
    <button id="btn-next" class="border rounded px-2 py-1">→</button>
    <label>Zoom <select id="sel-zoom" class="border rounded px-1">
      @foreach([50,75,100,125,150,200] as $z)<option value="{{ $z }}" {{ (int)$pref->zoom === $z ? 'selected' : '' }}>{{ $z }}%</option>@endforeach
    </select></label>
    <label>Tata letak <select id="sel-layout" class="border rounded px-1">
      <option value="single" {{ $pref->layout === 'single' ? 'selected' : '' }}>Single page</option>
      <option value="double" {{ $pref->layout === 'double' ? 'selected' : '' }}>Double page</option>
    </select></label>
    <button id="btn-theme" class="border rounded px-2 py-1">{{ $pref->theme === 'dark' ? '☀ Terang' : '🌙 Gelap' }}</button>
    @auth @if($position)<span id="resume-hint" class="text-xs opacity-75">Terakhir dibaca: hal. {{ $position->page }}</span>@endif @endauth
  </div>
  <div id="pages" class="flex flex-wrap justify-center gap-4 p-4 max-h-[75vh] overflow-auto"></div>
</div>
@if($document->source_type === 'editor' && $document->content)
  <details class="mt-4 bg-white rounded shadow p-4 text-sm"><summary class="cursor-pointer font-semibold">Lihat teks sumber (editor)</summary><div class="prose max-w-none mt-2">{!! $document->content !!}</div></details>
@endif

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
const PDF_URL = @json($document->fileUrl());
const START_PAGE = @json(auth()->check() && $position ? (int)$position->page : 1);
const IS_AUTH = @json(auth()->check());
const SAVE_POS_URL = @json(auth()->check() ? route('api.position', $document) : null);
const SAVE_PREF_URL = @json(auth()->check() ? route('api.prefs') : null);
const CSRF = @json(csrf_token());
let pdf = null, page = START_PAGE, zoom = @json((int)$pref->zoom), layout = @json($pref->layout), theme = @json($pref->theme);
let saveTimer = null;

async function render() {
  if (!PDF_URL) { document.getElementById('pages').innerHTML = '<p class="text-sm p-4">Dokumen ini tidak memiliki file PDF.</p>'; return; }
  pdf = pdf || await pdfjsLib.getDocument(PDF_URL).promise;
  document.getElementById('pg-total').textContent = pdf.numPages;
  page = Math.min(Math.max(1, page), pdf.numPages);
  document.getElementById('pg-now').textContent = page;
  const box = document.getElementById('pages'); box.innerHTML = '';
  const pages = layout === 'double' && page < pdf.numPages ? [page, page + 1] : [page];
  for (const n of pages) {
    const p = await pdf.getPage(n);
    const vp = p.getViewport({ scale: (zoom / 100) * 1.2 });
    const cv = document.createElement('canvas');
    cv.className = theme === 'dark' ? 'rounded shadow' : 'rounded shadow border';
    if (theme === 'dark') cv.style.filter = 'invert(0.92) hue-rotate(180deg)';
    cv.height = vp.height; cv.width = vp.width;
    box.appendChild(cv);
    await p.render({ canvasContext: cv.getContext('2d'), viewport: vp }).promise;
  }
  const hint = document.getElementById('resume-hint');
  if (hint) hint.textContent = 'Terakhir dibaca: hal. ' + page;
  if (IS_AUTH) { clearTimeout(saveTimer); saveTimer = setTimeout(savePosition, 1200); }
}
function savePosition() {
  fetch(SAVE_POS_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ page, total_pages: pdf ? pdf.numPages : null, percent: pdf ? Math.round(page / pdf.numPages * 100) : 0 }) });
}
function savePrefs() {
  if (!IS_AUTH) return;
  fetch(SAVE_PREF_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ theme, zoom, layout }) });
  document.getElementById('reader').dataset.theme = theme;
}
document.getElementById('btn-prev').onclick = () => { page -= layout === 'double' ? 2 : 1; render(); };
document.getElementById('btn-next').onclick = async () => { pdf = pdf || await pdfjsLib.getDocument(PDF_URL).promise; page = Math.min(page + (layout === 'double' ? 2 : 1), pdf.numPages); render(); };
document.getElementById('sel-zoom').onchange = e => { zoom = +e.target.value; savePrefs(); render(); };
document.getElementById('sel-layout').onchange = e => { layout = e.target.value; savePrefs(); render(); };
document.getElementById('btn-theme').onclick = e => {
  theme = theme === 'dark' ? 'light' : 'dark';
  const r = document.getElementById('reader');
  r.className = 'rounded shadow overflow-hidden border ' + (theme === 'dark' ? 'bg-stone-900' : 'bg-white');
  e.target.textContent = theme === 'dark' ? '☀ Terang' : '🌙 Gelap';
  savePrefs(); render();
};
document.addEventListener('keydown', e => {
  if (e.key === 'ArrowRight') document.getElementById('btn-next').click();
  if (e.key === 'ArrowLeft') document.getElementById('btn-prev').click();
});
render();
</script>
@endpush
@endsection
