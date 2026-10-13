<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $document->title }} — Reader Mode · YPL</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<style>
  @include('components.tokens')
  .grad-navy { background: var(--ypl-grad-navy); }
  .grad-red { background: var(--ypl-grad-red); }
  .brand-line { background: linear-gradient(90deg, var(--ypl-navy) 60%, var(--ypl-red) 60%); }
  html, body { height: 100%; }
  body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
  .font-reading { font-family: Georgia, 'Times New Roman', serif; }
  #stage { perspective: 2200px; }
  #pages { transform-style: preserve-3d; }
  #pages.flipping { animation: pageturn .55s ease; }
  @keyframes pageturn {
    0% { transform: rotateY(0deg); opacity: 1; }
    40% { transform: rotateY(-9deg); opacity: .85; }
    100% { transform: rotateY(0deg); opacity: 1; }
  }
  @media (prefers-reduced-motion: reduce) {
    #pages.flipping { animation: none; }
  }
  #reader-dark #pages canvas, [data-theme="dark"] #pages canvas { filter: invert(0.92) hue-rotate(180deg); }
  .pg-grid button:focus-visible, button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible { outline: 2px solid #2f6bff; outline-offset: 2px; }
</style>
</head>
<body id="reader-root" data-theme="{{ $pref->normalizedTheme() }}" class="{{ \App\Models\ReadingPref::bodyClass($pref->theme) }} min-h-screen flex flex-col">
<div class="h-1 shrink-0 brand-line"></div>

{{-- Top slim bar: kembali + kode/judul + menu --}}
<header class="shrink-0 bg-[var(--ypl-reader-bar)] text-white">
  <div class="max-w-7xl mx-auto px-3 sm:px-4 py-2 flex items-center gap-3 text-sm">
    <a href="{{ $document->category ? route('library.show', $document->category) : route('library.index') }}" class="w-9 h-9 rounded-full grid place-items-center hover:bg-white/10 shrink-0" aria-label="Kembali ke Library">←</a>
    <div class="min-w-0 flex-1 text-center sm:text-left">
      <span class="font-bold">{{ $document->category->code ?? 'YPL' }}</span>
      <span class="opacity-50 mx-1.5">•</span>
      <span class="truncate">{{ $document->title }}</span>
    </div>
    <div class="relative shrink-0">
      <button id="btn-menu" class="w-9 h-9 rounded-full grid place-items-center hover:bg-white/10 font-bold" aria-haspopup="true" aria-expanded="false" aria-label="Menu pembaca">…</button>
      <div id="menu-panel" class="hidden absolute right-0 mt-1 w-52 rounded-2xl bg-white text-slate-800 shadow-xl overflow-hidden z-50 text-sm font-semibold">
        <button id="btn-theme" class="w-full text-left px-4 py-2.5 hover:bg-slate-100">Tema: {{ ucfirst($pref->normalizedTheme()) }} →</button>
        <button id="btn-full" class="w-full text-left px-4 py-2.5 hover:bg-slate-100">⛶ Layar Penuh</button>
        <a href="{{ route('documents.show', $document) }}" class="block px-4 py-2.5 hover:bg-slate-100">Lihat halaman detail →</a>
      </div>
    </div>
  </div>
</header>

{{-- Stage + TOC drawer --}}
<div class="flex-1 flex min-h-0 relative">
  <aside id="toc-drawer" class="hidden absolute z-30 left-0 top-0 bottom-0 w-80 max-w-[85vw] overflow-y-auto bg-[var(--ypl-reader-panel)] text-slate-800 shadow-2xl">
    <div class="flex items-center justify-between px-5 pt-4">
      <h2 class="font-extrabold tracking-wide text-[var(--ypl-navy)]">CONTENTS</h2>
      <button id="btn-toc-close" class="w-8 h-8 rounded-full grid place-items-center text-slate-400 hover:bg-slate-200" aria-label="Tutup daftar isi">✕</button>
    </div>
    <div class="px-3 py-2 space-y-0.5">
      <a href="{{ route('reader.show', $document) }}" class="block px-3 py-2.5 rounded-xl bg-[var(--ypl-navy)]/10 font-bold text-sm text-[var(--ypl-navy)]">▶ {{ $document->title }} <span class="block text-[11px] font-normal text-slate-500">Sedang dibaca</span></a>
      @foreach($chapters as $i => $c)
        <a href="{{ route('reader.show', $c) }}" class="block px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 text-sm"><span class="text-slate-400 font-bold mr-2">{{ $i + 2 }}.</span>{{ $c->title }}</a>
      @endforeach
    </div>
    @if(!empty($sections))
    <div class="px-5 pb-3">
      <h2 class="text-xs font-bold tracking-widest text-slate-400 mt-3 mb-2">BAGIAN DOKUMEN</h2>
      <ol class="space-y-0.5 text-sm max-h-64 overflow-y-auto">
        @foreach($sections as $s)
          <li><a href="#{{ $s['id'] }}" data-section-jump="{{ $s['id'] }}" class="block px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5"><span class="text-slate-400 font-bold mr-2">{{ $loop->iteration }}.</span>{{ $s['label'] }}</a></li>
        @endforeach
      </ol>
    </div>
    @endif
    <div class="px-5 pb-5">
      <h2 class="text-xs font-bold tracking-widest text-slate-400 mt-3 mb-2">TABLE OF CONTENTS</h2>
      <p class="text-xs text-slate-500">Bab = dokumen lain dalam koleksi {{ $document->category->name ?? 'ini' }}.</p>
      <h2 class="text-xs font-bold tracking-widest text-slate-400 mt-4 mb-2">DIRECT PAGE JUMP</h2>
      <form id="form-jump" class="flex gap-2">
        <input id="in-jump" type="number" min="1" placeholder="No. halaman" class="w-full rounded-full bg-white border border-slate-300 px-3 py-2 text-sm outline-none" aria-label="Lompat ke halaman">
        <button class="shrink-0 rounded-full bg-[var(--ypl-navy)] text-white font-bold text-sm px-4">Go</button>
      </form>
      <h2 class="text-xs font-bold tracking-widest text-slate-400 mt-4 mb-2">PAGES</h2>
      <div id="pg-grid" class="pg-grid grid grid-cols-3 gap-2"></div>
      <p class="text-[11px] text-slate-400 mt-1.5">Pratinjau dimuat saat terlihat. Klik untuk lompat.</p>
    </div>

    {{-- Rekomendasi di Reader --}}
    @if($recommendations->isNotEmpty())
      <div class="px-5 pt-4 pb-5 border-t border-slate-200">
        <h2 class="text-xs font-bold tracking-widest text-[var(--ypl-navy)] mb-3">REKOMENDASI</h2>
        <div class="space-y-2">
          @foreach($recommendations as $rec)
            <a href="{{ route('reader.show', $rec['document']) }}" class="block px-3 py-2 rounded-xl hover:bg-[var(--ypl-navy)]/5 text-sm">
              <span class="text-slate-400 font-bold mr-2">{{ $loop->iteration }}.</span>{{ $rec['document']->title }}
              @if($rec['reasons'])
                <span class="block text-[10px] text-[var(--ypl-navy)] font-bold mt-1">{{ $rec['reasons'][0] }}</span>
              @endif
            </a>
          @endforeach
        </div>
      </div>
    @endif
  </aside>

  <main id="stage" class="flex-1 min-w-0 overflow-y-auto">
    <div id="search-panel" class="hidden mx-auto max-w-2xl mt-3 px-3">
      <form id="form-search" class="flex gap-2 bg-white rounded-full p-1.5 shadow-xl">
        <input id="in-search" placeholder="Search in book…" class="flex-1 bg-transparent text-slate-800 text-sm px-4 outline-none min-w-0" aria-label="Cari dalam buku">
        <button class="shrink-0 rounded-full font-bold text-sm px-5 py-2 text-white bg-[var(--ypl-navy)]">Find</button>
      </form>
      <div id="search-meta" class="text-xs text-slate-300 mt-1.5 px-1"></div>
      <div id="search-hits" class="mt-1 space-y-1"></div>
    </div>
    <div id="ask-panel" class="hidden mx-auto max-w-2xl mt-3 px-3">
      <div class="bg-[var(--ypl-reader-panel)] text-slate-800 rounded-2xl p-4 shadow-xl">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-xs font-bold tracking-widest text-[var(--ypl-navy)]">ASK YPL · KONTEKS DOKUMEN INI</h2>
          <a href="{{ route('ask.index', ['doc' => $document->slug]) }}" class="text-[11px] font-bold text-slate-500 hover:text-[var(--ypl-navy)] shrink-0">Halaman penuh →</a>
        </div>
        <form id="form-ask" class="flex gap-2 mt-2">
          <input id="in-ask" maxlength="500" placeholder="Tanya tentang dokumen ini…" class="flex-1 rounded-full bg-white border border-slate-300 px-4 py-2 text-sm outline-none min-w-0" aria-label="Pertanyaan Ask YPL">
          <button class="shrink-0 rounded-full bg-[var(--ypl-navy)] text-white font-bold text-sm px-5 py-2">Tanya</button>
        </form>
        <div class="mt-2">
          <button type="button" id="btn-explain" class="text-xs font-semibold bg-white border border-slate-300 rounded-full px-3 py-1.5 hover:border-[var(--ypl-navy)] hover:text-[var(--ypl-navy)] transition">Ringkas dokumen ini</button>
        </div>
        <p id="ask-error" class="hidden text-xs font-bold text-[var(--ypl-red)] mt-2"></p>
        <div id="ask-result" class="hidden mt-3"></div>
      </div>
    </div>
    <div id="aa-panel" class="hidden mx-auto max-w-2xl mt-3 px-3">
      <div class="bg-[var(--ypl-reader-panel)] text-slate-800 rounded-2xl p-4 shadow-xl flex flex-wrap items-center gap-4 text-sm font-semibold">
        <label class="flex items-center gap-2">Ukuran huruf
          <select id="sel-zoom" class="rounded-full text-sm font-bold px-3 py-1.5 bg-white border border-slate-300 outline-none cursor-pointer">
            @foreach([75, 100, 125, 150, 200] as $z)<option value="{{ $z }}" {{ (int) $pref->zoom === $z ? 'selected' : '' }}>{{ $z }}%</option>@endforeach
          </select>
        </label>
        <label class="flex items-center gap-2">Tampilan
          <select id="sel-layout" class="rounded-full text-sm font-bold px-3 py-1.5 bg-white border border-slate-300 outline-none cursor-pointer">
            <option value="single" {{ $pref->layout === 'single' ? 'selected' : '' }}>Single page</option>
            <option value="double" {{ $pref->layout === 'double' ? 'selected' : '' }}>Double page</option>
          </select>
        </label>
      </div>
    </div>
    @auth
    <div id="notes-panel" class="hidden mx-auto max-w-2xl mt-3 px-3">
      <div class="bg-[var(--ypl-reader-panel)] text-slate-800 rounded-2xl p-4 shadow-xl">
        <h2 class="text-xs font-bold tracking-widest text-[var(--ypl-navy)] mb-2">CATATAN SAYA</h2>
        <form id="form-note" class="flex gap-2">
          <input id="in-note" maxlength="1000" placeholder="Tulis catatan untuk halaman ini…" class="flex-1 rounded-full bg-white border border-slate-300 px-4 text-sm outline-none min-w-0" aria-label="Isi catatan">
          <button class="shrink-0 rounded-full bg-[var(--ypl-navy)] text-white font-bold text-sm px-5 py-2">Simpan</button>
        </form>
        <p id="notes-error" class="hidden text-xs font-bold text-[var(--ypl-red)] mt-1.5"></p>
        <div id="notes-list" class="mt-2 space-y-1.5 max-h-56 overflow-y-auto">
          @forelse($notes as $note)
            <div class="flex items-start gap-2 bg-white rounded-xl px-3 py-2 text-sm" data-note="{{ $note->id }}">
              <button class="shrink-0 text-[11px] font-bold bg-[var(--ypl-navy)]/10 text-[var(--ypl-navy)] rounded-full px-2 py-0.5" data-jump="{{ $note->page }}" aria-label="Lompat ke halaman {{ $note->page }}">hlm. {{ $note->page }}</button>
              <span class="flex-1 min-w-0">{{ $note->body }}</span>
              <button class="shrink-0 text-slate-300 hover:text-[var(--ypl-red)] font-bold" data-del="{{ route('api.notes.destroy', [$document, $note]) }}" data-id="{{ $note->id }}" aria-label="Hapus catatan">✕</button>
            </div>
          @empty
            <p id="notes-empty" class="text-xs text-slate-500 py-2">Belum ada catatan. Catatan tersimpan per halaman dan hanya terlihat oleh Anda.</p>
          @endforelse
        </div>
      </div>
    </div>
    @endauth
    @if($safeContent)
    <article id="structured-reader" class="mx-auto max-w-3xl px-4 sm:px-8 py-6">
      <h1 class="font-reading text-2xl sm:text-3xl font-bold text-[var(--ypl-navy)] mb-1">{{ $document->title }}</h1>
      @if($document->subtitle)<p class="text-slate-500 mb-4">{{ $document->subtitle }}</p>@endif
      <div id="structured-content" class="prose font-reading leading-relaxed text-slate-800 text-[calc(1rem*var(--ypl-read-scale,1))]">
        {!! $safeContent !!}
      </div>
      <p class="text-xs text-slate-400 mt-10 border-t border-slate-200 pt-3">Dokumen terstruktur (HTML) · Yayasan Pendidikan Lokon</p>
    </article>
    @else
    <div id="pages" class="flex flex-wrap justify-center items-start gap-5 p-4 sm:p-6 min-h-[60vh]"></div>
    @endif
  </main>
</div>

{{-- Bottom toolbar ala blueprint --}}
<footer class="shrink-0 bg-[var(--ypl-reader-bar)] border-t border-white/10 text-white">
  <div class="max-w-7xl mx-auto px-2 sm:px-4 py-2 flex items-center gap-1 sm:gap-2 text-sm font-bold overflow-x-auto whitespace-nowrap">
    <button id="btn-toc" class="px-3 py-2 rounded-full hover:bg-white/10" aria-expanded="false" aria-controls="toc-drawer">☰ <span class="hidden sm:inline">Contents</span></button>
    <button id="btn-search" class="px-3 py-2 rounded-full hover:bg-white/10" aria-expanded="false" aria-controls="search-panel">⌕ <span class="hidden sm:inline">Search</span></button>
    @auth
      @include('components.bookmark-button', [
        'document' => $document,
        'marked' => $isMarked,
        'labelOn' => 'Tersimpan',
        'labelOff' => 'Bookmark',
        'btnClass' => 'px-3 py-1.5 rounded-full hover:bg-white/10 inline-flex items-center gap-1.5 cursor-pointer transition text-sm font-bold',
        'iconClass' => 'w-4 h-4',
        'activeClass' => '!text-amber-300 bg-white/15 shadow-sm',
        'inactiveClass' => 'text-white/80',
      ])
    @else
      <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-full hover:bg-white/10 inline-flex items-center gap-1.5 text-white/80 text-sm font-bold">
        @include('components.icon', ['name' => 'bookmark', 'class' => 'w-4 h-4'])
        <span class="hidden sm:inline">Bookmark</span>
      </a>
    @endauth
    <button id="btn-aa" class="px-3 py-2 rounded-full hover:bg-white/10" aria-expanded="false" aria-controls="aa-panel">Aa</button>
    @auth
      <button id="btn-notes" class="px-3 py-2 rounded-full hover:bg-white/10" aria-expanded="false" aria-controls="notes-panel">✎ <span class="hidden sm:inline">Catatan</span>@if($notes->isNotEmpty())<span id="notes-count" class="ml-1 text-[10px] bg-amber-300 text-slate-900 rounded-full px-1.5 py-0.5">{{ $notes->count() }}</span>@endif</button>
    @else
      <a href="{{ route('login') }}" class="px-3 py-2 rounded-full hover:bg-white/10 text-white/80">✎ <span class="hidden sm:inline">Catatan</span></a>
    @endauth
    <button id="btn-ask" class="px-3 py-2 rounded-full hover:bg-white/10" aria-expanded="false" aria-controls="ask-panel">✦ <span class="hidden sm:inline">Ask YPL</span></button>
    <span class="flex-1"></span>
    <button id="btn-prev" aria-label="Halaman sebelumnya" class="w-9 h-9 rounded-full grid place-items-center hover:bg-white/10 shrink-0">◀</button>
    <span class="text-sm font-bold tabular-nums px-1 shrink-0"><span id="pg-now">1</span> / <span id="pg-total">…</span></span>
    <button id="btn-next" aria-label="Halaman berikutnya" class="w-9 h-9 rounded-full grid place-items-center hover:bg-white/10 shrink-0">▶</button>
    <span id="reading-pct" class="text-xs text-slate-300 tabular-nums pl-2 shrink-0">Reading 0%</span>
  </div>
</footer>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
const PDF_URL = @json($document->fileUrl());
const START_PAGE = @json(auth()->check() && $position ? (int) $position->page : 1);
const IS_AUTH = @json(auth()->check());
const SAVE_POS_URL = @json(auth()->check() ? route('api.position', $document) : null);
const SAVE_PREF_URL = @json(auth()->check() ? route('api.prefs') : null);
const NOTE_SAVE_URL = @json(auth()->check() ? route('api.notes.store', $document) : null);
const ASK_URL = @json(route('ask.query'));
const DOC_SLUG = @json($document->slug);
const CSRF = @json(csrf_token());
const STRUCTURED = @json($safeContent !== null);
let pdf = null, page = START_PAGE, zoom = @json((int) $pref->zoom), layout = @json($pref->layout);
let theme = @json($pref->normalizedTheme());
const THEME_ORDER = @json(\App\Models\ReadingPref::THEMES);
const THEME_LABEL = { light: 'Terang', paper: 'Paper', dark: 'Gelap' };
function applyTheme(next) {
  theme = next;
  const root = document.getElementById('reader-root');
  const cls = theme === 'dark' ? 'bg-[var(--ypl-reader-dark)] text-stone-100' : (theme === 'paper' ? 'bg-[var(--ypl-reader-paper)] text-slate-800' : 'bg-[var(--ypl-reader-light)] text-white');
  root.className = cls + ' min-h-screen flex flex-col';
  root.dataset.theme = theme;
  document.getElementById('btn-theme').textContent = 'Tema: ' + (THEME_LABEL[theme] || theme) + ' →';
}
if (window.matchMedia('(max-width: 767px)').matches && layout === 'double') layout = 'single';
let saveTimer = null, pageTexts = {};

async function render(flip = true) {
  if (!PDF_URL) return;
  pdf = pdf || await pdfjsLib.getDocument(PDF_URL).promise;
  page = Math.min(Math.max(1, page), pdf.numPages);
  const pct = Math.round(page / pdf.numPages * 100);
  document.getElementById('pg-now').textContent = page;
  document.getElementById('pg-total').textContent = pdf.numPages;
  document.getElementById('reading-pct').textContent = 'Reading ' + pct + '%';
  const box = document.getElementById('pages');
  if (flip) { box.classList.remove('flipping'); void box.offsetWidth; box.classList.add('flipping'); }
  box.innerHTML = '';
  const pages = layout === 'double' && page < pdf.numPages ? [page, page + 1] : [page];
  for (const n of pages) {
    const p = await pdf.getPage(n);
    const vp = p.getViewport({ scale: (zoom / 100) * 1.35 });
    const cv = document.createElement('canvas');
    cv.className = 'rounded-lg shadow-2xl max-w-full h-auto bg-white';
    cv.height = vp.height; cv.width = vp.width;
    box.appendChild(cv);
    await p.render({ canvasContext: cv.getContext('2d'), viewport: vp }).promise;
  }
  buildGrid();
  if (IS_AUTH) { clearTimeout(saveTimer); saveTimer = setTimeout(savePosition, 1200); }
}
function step(d) {
  const s = layout === 'double' ? 2 : 1;
  const max = pdf ? pdf.numPages : 9999;
  page = Math.min(Math.max(1, page + d * s), max);
  render(true);
}
function buildGrid() {
  const g = document.getElementById('pg-grid');
  if (!pdf || g.dataset.built) return;
  g.dataset.built = '1';
  const n = Math.min(pdf.numPages, 200);
  const renderThumb = async (btn) => {
    if (btn.dataset.done) return;
    btn.dataset.done = '1';
    try {
      const p = await pdf.getPage(+btn.dataset.page);
      const vp = p.getViewport({ scale: 0.32 });
      const cv = btn.querySelector('canvas');
      cv.height = vp.height; cv.width = vp.width;
      await p.render({ canvasContext: cv.getContext('2d'), viewport: vp }).promise;
    } catch (err) { btn.querySelector('span').textContent = btn.dataset.page; }
  };
  const immediates = [];
  for (let i = 1; i <= n; i++) {
    const b = document.createElement('button');
    b.dataset.page = i;
    b.className = 'rounded-xl overflow-hidden border border-slate-300 bg-white hover:border-[var(--ypl-navy)] hover:shadow-md transition text-center';
    b.setAttribute('aria-label', 'Ke halaman ' + i);
    b.onclick = () => { page = i; render(true); };
    const cv = document.createElement('canvas');
    cv.className = 'w-full h-auto block bg-slate-100';
    const lb = document.createElement('span');
    lb.className = 'block text-[10px] font-bold text-slate-500 py-1';
    lb.textContent = i;
    b.appendChild(cv); b.appendChild(lb);
    g.appendChild(b);
    immediates.push(b);
  }
  if (!('IntersectionObserver' in window)) { immediates.forEach(renderThumb); return; }
  const io = new IntersectionObserver((ents) => {
    ents.forEach((en) => { if (en.isIntersecting) { renderThumb(en.target); io.unobserve(en.target); } });
  }, { root: document.getElementById('toc-drawer'), rootMargin: '300px' });
  immediates.forEach((b) => io.observe(b));
}
async function searchBook(q) {
  const meta = document.getElementById('search-meta'), hits = document.getElementById('search-hits');
  hits.innerHTML = ''; meta.textContent = 'Mencari…';
  pdf = pdf || await pdfjsLib.getDocument(PDF_URL).promise;
  const needle = q.toLowerCase(), found = [];
  const n = Math.min(pdf.numPages, 100);
  for (let i = 1; i <= n; i++) {
    if (!pageTexts[i]) {
      const tc = await (await pdf.getPage(i)).getTextContent();
      pageTexts[i] = tc.items.map((it) => it.str).join(' ');
    }
    if (pageTexts[i].toLowerCase().includes(needle)) found.push(i);
  }
  meta.textContent = found.length ? found.length + ' halaman cocok untuk “' + q + '”.' : 'Tidak ditemukan dalam 100 halaman pertama.';
  for (const p of found.slice(0, 20)) {
    const b = document.createElement('button');
    b.className = 'block w-full text-left text-xs bg-white/10 hover:bg-white/20 rounded-xl px-3 py-2 text-white';
    b.textContent = '→ Halaman ' + p;
    b.onclick = () => { page = p; render(true); };
    hits.appendChild(b);
  }
}
function savePosition() {
  fetch(SAVE_POS_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ page, total_pages: pdf ? pdf.numPages : null, percent: pdf ? Math.round(page / pdf.numPages * 100) : 0 }) });
}
function savePrefs() {
  if (!IS_AUTH) return;
  fetch(SAVE_PREF_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ theme, zoom, layout }) });
}
function applyScale() {
  const el = document.getElementById('structured-content');
  if (el) el.style.setProperty('--ypl-read-scale', (zoom / 100).toFixed(2));
}
function structuredProgress() {
  const art = document.getElementById('structured-reader');
  if (!art) return;
  const max = art.scrollHeight - window.innerHeight;
  document.getElementById('reading-pct').textContent = 'Reading ' + (max > 0 ? Math.min(100, Math.round((window.scrollY / max) * 100)) : 100) + '%';
}
function structuredSearch(q) {
  const meta = document.getElementById('search-meta'), hits = document.getElementById('search-hits');
  hits.innerHTML = '';
  if (window.find) {
    const found = window.find(q, false, false, true, false, true);
    meta.textContent = found ? 'Lompat ke kemunculan “' + q + '” di dalam dokumen.' : 'Tidak ditemukan dalam dokumen.';
    if (found) return;
  }
  const needle = q.toLowerCase();
  const all = document.querySelectorAll('#structured-content h2, #structured-content h3, #structured-content p, #structured-content li');
  const matched = [];
  all.forEach((el) => {
    if (el.textContent.toLowerCase().includes(needle)) {
      const sec = el.closest('section, article') || el.parentElement;
      const h = sec.querySelector('h2, h3');
      if (h && !matched.includes(h)) matched.push(h);
    }
  });
  meta.textContent = matched.length ? matched.length + ' bagian cocok untuk “' + q + '”.' : 'Tidak ditemukan dalam dokumen.';
  matched.slice(0, 20).forEach((h) => {
    const b = document.createElement('button');
    b.className = 'block w-full text-left text-xs bg-white/10 hover:bg-white/20 rounded-xl px-3 py-2 text-white';
    b.textContent = '→ ' + h.textContent.trim().slice(0, 60);
    b.onclick = () => h.scrollIntoView({ behavior: 'smooth', block: 'start' });
    hits.appendChild(b);
  });
}
const toc = document.getElementById('toc-drawer'), btnToc = document.getElementById('btn-toc');
function setToc(open) { toc.classList.toggle('hidden', !open); btnToc.setAttribute('aria-expanded', String(open)); }
btnToc.onclick = () => setToc(toc.classList.contains('hidden'));
document.getElementById('btn-toc-close').onclick = () => setToc(false);
const sp = document.getElementById('search-panel'), btnSearch = document.getElementById('btn-search');
btnSearch.onclick = () => { const open = sp.classList.toggle('hidden'); btnSearch.setAttribute('aria-expanded', String(!open)); if (!open) document.getElementById('in-search').focus(); };
const ap = document.getElementById('ask-panel'), btnAsk = document.getElementById('btn-ask');
btnAsk.onclick = () => { const open = ap.classList.toggle('hidden'); btnAsk.setAttribute('aria-expanded', String(!open)); if (!open) document.getElementById('in-ask').focus(); };
const askResult = document.getElementById('ask-result'), askError = document.getElementById('ask-error');
function askSetError(msg) { askError.textContent = msg || ''; askError.classList.toggle('hidden', !msg); }
function askSourceCard(src) {
  const a = document.createElement('a');
  a.href = src.url; a.target = '_blank'; a.rel = 'noopener';
  a.className = 'block bg-white rounded-xl border border-slate-200 p-3 hover:border-[var(--ypl-navy)] transition';
  const meta = document.createElement('div');
  meta.className = 'flex flex-wrap gap-1 text-[10px] font-bold';
  [[src.doc_type, 'bg-[var(--ypl-navy)]/5 text-[var(--ypl-navy)]'], [src.status, 'bg-slate-100 text-slate-500'], ['v' + src.version, 'bg-slate-100 text-slate-500']]
    .concat(src.section ? [['§ ' + src.section, 'bg-emerald-50 text-emerald-700']] : [])
    .forEach(([text, cls]) => { const s = document.createElement('span'); s.className = cls + ' px-2 py-0.5 rounded-full'; s.textContent = text; meta.appendChild(s); });
  const title = document.createElement('div');
  title.className = 'font-bold text-sm text-slate-900 mt-1.5 leading-snug';
  title.textContent = src.title;
  const quote = document.createElement('p');
  quote.className = 'text-[12px] text-slate-500 mt-1 line-clamp-2';
  quote.textContent = '\u201C' + src.passage + '\u201D';
  a.append(meta, title, quote);
  return a;
}
async function askYpl(question, explain) {
  askSetError('');
  askResult.classList.remove('hidden');
  askResult.innerHTML = '<p class="text-xs text-slate-400">Mencari jawaban…</p>';
  try {
    const res = await fetch(ASK_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ question, doc: DOC_SLUG, explain: explain ? 1 : 0 }),
    });
    if (res.status === 419 || (res.redirected && res.url.includes('/masuk'))) { window.location.reload(); return; }
    if (!res.ok) {
      await res.json().catch(() => null);
      askSetError(res.status === 429 ? 'Terlalu banyak permintaan. Coba lagi sebentar.' : 'Tidak bisa menjawab sekarang. Coba lagi.');
      askResult.innerHTML = '';
      return;
    }
    const data = await res.json();
    askResult.innerHTML = '';
    const head = document.createElement('div');
    head.className = 'text-[10px] font-bold tracking-widest ' + (data.found ? 'text-[var(--ypl-navy)]' : 'text-amber-600');
    head.textContent = data.found ? (data.ai ? 'JAWABAN AI BERBASIS SUMBER 🧪' : 'JAWABAN BERBASIS SUMBER') : 'SUMBER TIDAK DITEMUKAN';
    const body = document.createElement('div');
    body.className = 'whitespace-pre-line text-sm text-slate-700 mt-1.5';
    body.textContent = data.answer;
    askResult.append(head, body);
    if (data.found && data.sources.length) {
      const label = document.createElement('h3');
      label.className = 'text-xs font-bold tracking-widest text-[var(--ypl-navy)] mt-3 mb-1.5';
      label.textContent = 'SUMBER (' + data.sources.length + ')';
      const list = document.createElement('div');
      list.className = 'space-y-2';
      data.sources.forEach((s) => list.appendChild(askSourceCard(s)));
      askResult.append(label, list);
    }
  } catch (err) {
    askSetError('Koneksi terputus. Periksa internet lalu coba lagi.');
    askResult.innerHTML = '';
  }
}
document.getElementById('form-ask').onsubmit = (e) => {
  e.preventDefault();
  const q = document.getElementById('in-ask').value.trim();
  if (q.length < 3) { askSetError('Pertanyaan minimal 3 karakter.'); return; }
  askYpl(q, false);
};
document.getElementById('btn-explain').onclick = () => askYpl('Ringkas dokumen ini', true);
const aa = document.getElementById('aa-panel'), btnAa = document.getElementById('btn-aa');
btnAa.onclick = () => { const open = aa.classList.toggle('hidden'); btnAa.setAttribute('aria-expanded', String(!open)); };
const np = document.getElementById('notes-panel'), btnNotes = document.getElementById('btn-notes');
if (btnNotes && np) btnNotes.onclick = () => { const open = np.classList.toggle('hidden'); btnNotes.setAttribute('aria-expanded', String(!open)); };
function noteRow(id, pg, body, delUrl) {
  const row = document.createElement('div');
  row.className = 'flex items-start gap-2 bg-white rounded-xl px-3 py-2 text-sm';
  row.dataset.note = id;
  const jump = document.createElement('button');
  jump.className = 'shrink-0 text-[11px] font-bold bg-[var(--ypl-navy)]/10 text-[var(--ypl-navy)] rounded-full px-2 py-0.5';
  jump.textContent = 'hlm. ' + pg;
  jump.setAttribute('aria-label', 'Lompat ke halaman ' + pg);
  jump.onclick = () => { page = pg; render(true); };
  const tx = document.createElement('span');
  tx.className = 'flex-1 min-w-0';
  tx.textContent = body;
  const del = document.createElement('button');
  del.className = 'shrink-0 text-slate-300 hover:text-[var(--ypl-red)] font-bold';
  del.textContent = '✕';
  del.setAttribute('aria-label', 'Hapus catatan');
  del.onclick = () => deleteNote(delUrl, id);
  row.appendChild(jump); row.appendChild(tx); row.appendChild(del);
  return row;
}
async function deleteNote(url, id) {
  const res = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
  if (!res.ok) return;
  document.querySelectorAll('[data-note="' + id + '"]').forEach((el) => el.remove());
}
const formNote = document.getElementById('form-note');
if (formNote) formNote.onsubmit = async (e) => {
  e.preventDefault();
  const inp = document.getElementById('in-note'), err = document.getElementById('notes-error');
  const body = inp.value.trim();
  err.classList.add('hidden');
  if (!body) { err.textContent = 'Isi catatan dulu.'; err.classList.remove('hidden'); return; }
  const res = await fetch(NOTE_SAVE_URL, { method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ page, body }) });
  if (res.status === 422) { err.textContent = 'Catatan maksimal 1000 karakter.'; err.classList.remove('hidden'); return; }
  if (!res.ok) { err.textContent = 'Gagal menyimpan. Coba lagi.'; err.classList.remove('hidden'); return; }
  const data = await res.json();
  const list = document.getElementById('notes-list');
  const empty = document.getElementById('notes-empty');
  if (empty) empty.remove();
  list.prepend(noteRow(data.note.id, data.note.page, data.note.body, data.note.delete_url));
  inp.value = '';
};
document.getElementById('notes-list')?.addEventListener('click', (e) => {
  const j = e.target.closest('[data-jump]');
  if (j) { page = +j.dataset.jump; render(true); return; }
  const d = e.target.closest('[data-del]');
  if (d) deleteNote(d.dataset.del, d.dataset.id);
});
document.getElementById('form-search').onsubmit = (e) => { e.preventDefault(); const q = document.getElementById('in-search').value.trim(); if (!q) return; if (STRUCTURED) structuredSearch(q); else searchBook(q); };
document.getElementById('form-jump').onsubmit = (e) => {
  e.preventDefault();
  if (STRUCTURED) {
    const toc = document.querySelectorAll('#structured-content h2[id]');
    const target = toc[parseInt(document.getElementById('in-jump').value, 10) - 1];
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    return;
  }
  const v = parseInt(document.getElementById('in-jump').value, 10); if (v >= 1) { page = v; render(true); }
};
function structuredStep(d) {
  window.scrollBy({ top: d * (window.innerHeight * 0.85), behavior: 'smooth' });
}
document.getElementById('btn-prev').onclick = () => STRUCTURED ? structuredStep(-1) : step(-1);
document.getElementById('btn-next').onclick = () => STRUCTURED ? structuredStep(1) : step(1);
document.getElementById('sel-zoom').onchange = (e) => { zoom = +e.target.value; savePrefs(); if (STRUCTURED) applyScale(); else render(false); };
document.getElementById('sel-layout').onchange = (e) => { layout = e.target.value; savePrefs(); if (!STRUCTURED) render(false); };
const menu = document.getElementById('menu-panel'), btnMenu = document.getElementById('btn-menu');
btnMenu.onclick = (e) => { e.stopPropagation(); const open = menu.classList.toggle('hidden'); btnMenu.setAttribute('aria-expanded', String(!open)); };
document.addEventListener('click', (e) => { if (!menu.classList.contains('hidden') && !menu.contains(e.target)) menu.classList.add('hidden'); });
document.getElementById('btn-theme').onclick = () => {
  applyTheme(THEME_ORDER[(THEME_ORDER.indexOf(theme) + 1) % THEME_ORDER.length]);
  savePrefs(); if (!STRUCTURED) render(false);
};
document.getElementById('btn-full').onclick = () => {
  const r = document.getElementById('reader-root');
  if (document.fullscreenElement) document.exitFullscreen();
  else if (r.requestFullscreen) r.requestFullscreen();
};
let touchX = null;
document.getElementById('stage').addEventListener('touchstart', (e) => { touchX = e.touches[0].clientX; }, { passive: true });
document.getElementById('stage').addEventListener('touchend', (e) => {
  if (STRUCTURED) return;
  if (touchX === null) return;
  const dx = e.changedTouches[0].clientX - touchX;
  if (Math.abs(dx) > 60) step(dx < 0 ? 1 : -1);
  touchX = null;
}, { passive: true });
document.addEventListener('keydown', (e) => {
  if (/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) return;
  if (e.key === 'ArrowRight') STRUCTURED ? structuredStep(1) : step(1);
  if (e.key === 'ArrowLeft') STRUCTURED ? structuredStep(-1) : step(-1);
  if (e.key === 'Escape') { setToc(false); menu.classList.add('hidden'); }
});
document.querySelectorAll('[data-section-jump]').forEach((a) => {
  a.addEventListener('click', (e) => {
    e.preventDefault();
    setToc(false);
    const t = document.getElementById(a.dataset.sectionJump);
    if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});
window.addEventListener('scroll', () => { if (STRUCTURED) structuredProgress(); }, { passive: true });
document.addEventListener('submit', (e) => {
  const form = e.target.closest('form[data-bookmark-form]');
  if (!form) return;
  e.preventDefault();
  const btn = form.querySelector('[data-bookmark-btn], button[type="submit"]');
  if (!btn || btn.dataset.busy === '1') return;
  btn.dataset.busy = '1';
  btn.classList.add('opacity-70', 'scale-95');

  const tokenInput = form.querySelector('input[name="_token"]');
  fetch(form.action, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': tokenInput ? tokenInput.value : ''
    }
  })
  .then(res => {
    if (res.status === 401 || res.redirected) {
      window.location.href = '{{ route('login') }}';
      return null;
    }
    return res.json();
  })
  .then(data => {
    if (!data || typeof data.marked === 'undefined') return;
    const isMarked = Boolean(data.marked);
    btn.setAttribute('data-marked', isMarked ? '1' : '0');
    btn.setAttribute('aria-pressed', isMarked ? 'true' : 'false');
    const iconMarked = form.querySelector('[data-icon-marked]');
    const iconUnmarked = form.querySelector('[data-icon-unmarked]');
    if (iconMarked && iconUnmarked) {
      iconMarked.classList.toggle('hidden', !isMarked);
      iconMarked.classList.toggle('inline-flex', isMarked);
      iconUnmarked.classList.toggle('hidden', isMarked);
      iconUnmarked.classList.toggle('inline-flex', !isMarked);
    }
    const label = form.querySelector('[data-label]');
    if (label) {
      label.textContent = isMarked ? (btn.getAttribute('data-label-on') || 'Tersimpan') : (btn.getAttribute('data-label-off') || 'Bookmark');
    }
    const activeClasses = (btn.getAttribute('data-active-class') || '').split(' ').filter(Boolean);
    const inactiveClasses = (btn.getAttribute('data-inactive-class') || '').split(' ').filter(Boolean);
    if (isMarked) {
      inactiveClasses.forEach(c => btn.classList.remove(c));
      activeClasses.forEach(c => btn.classList.add(c));
    } else {
      activeClasses.forEach(c => btn.classList.remove(c));
      inactiveClasses.forEach(c => btn.classList.add(c));
    }
    btn.classList.add('scale-105');
    setTimeout(() => btn.classList.remove('scale-105'), 180);
  })
  .catch(err => {
    console.error('Bookmark error:', err);
    form.submit();
  })
  .finally(() => {
    btn.dataset.busy = '0';
    btn.classList.remove('opacity-70', 'scale-95');
  });
});
if (STRUCTURED) { applyScale(); structuredProgress(); document.getElementById('pg-now').textContent = '§'; document.getElementById('pg-total').textContent = '1'; } else { render(false); }
</script>
</body>
</html>
