@extends('layout')
@section('title', 'Impact Map — '.$document->title)
@section('content')

@include('components.back-bar', ['trail' => [
    ['Home', route('home')],
    ['Governance', route('governance.index')],
    ['Kelola', route('governance.index')],
    [$document->title, route('governance.show', $document)],
    ['Impact Map']
]])

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-xs font-bold tracking-widest text-[#c90018]">IMPACT MAP</p>
            <h1 class="page-title mt-1">{{ $document->title }}</h1>
            <p class="text-sm text-slate-500 mt-1">Visualisasi relasi dokumen & sistem terkait</p>
        </div>
        <a href="{{ route('governance.show', $document) }}" class="btn-ghost">← Kembali</a>
    </div>

    @if($impactMap['references']->isEmpty() && $impactMap['referenced_by']->isEmpty() && count($graph['edges']) === 0)
        <div class="bg-white rounded-2xl border border-dashed border-[#d6d6d6] p-12 text-center">
            @include('components.icon', ['name' => 'git-branch', 'class' => 'w-16 h-16 text-slate-300 mx-auto mb-4'])
            <h2 class="text-xl font-extrabold text-slate-900">Belum Ada Relasi</h2>
            <p class="text-slate-500 mt-2">Dokumen ini belum memiliki relasi eksplisit dengan dokumen atau sistem lain.</p></            <p class="text-slate-400 text-sm mt-1">Tambahkan relasi di halaman detail untuk memulai visualisasi impact map.</p>
            <a href="{{ route('governance.show', $document) }}" class="btn-primary mt-4 inline-block">Kelola Relasi →</a>
        </div>
    @else
        {{-- Graf knowledge node-link (BAB 12): everything related, tanpa pencarian ulang --}}
        <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                <h2 class="font-extrabold text-[#002f80]">Graf Knowledge — {{ $document->title }}</h2>
                <div class="flex items-center gap-1 text-xs font-bold" role="group" aria-label="Kedalaman graf">
                    <span class="text-slate-400 mr-1">KEDALAMAN</span>
                    <a href="{{ route('governance.impact', ['document' => $document->slug, 'kedalaman' => 1]) }}" class="px-3 py-1.5 rounded-full {{ $depth === 1 ? 'bg-[#002f80] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">1</a>
                    <a href="{{ route('governance.impact', ['document' => $document->slug, 'kedalaman' => 2]) }}" class="px-3 py-1.5 rounded-full {{ $depth === 2 ? 'bg-[#002f80] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">2</a>
                </div>
            </div>
            <p class="text-sm text-slate-500 mb-4">Simpul = dokumen, sistem, dan PIC yang tertaut langsung maupun dua langkah dari dokumen ini. Klik simpul untuk membuka. Hanya konten yang terlihat oleh Anda yang tampil.</p>
            <div class="rounded-xl border border-[#d6d6d6] bg-slate-50/50 overflow-x-auto">
                <svg id="kg-graph" viewBox="0 0 900 600" class="w-full min-w-[640px] h-auto select-none" role="img" aria-label="Graf relasi dokumen"></svg>
            </div>
            @if($graph['truncated'] > 0)
                <p class="text-xs text-slate-400 mt-2">+{{ $graph['truncated'] }} simpul lain disembunyikan agar graf tetap terbaca — gunakan daftar di bawah untuk melihat semuanya.</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-6">
            <h2 class="font-extrabold text-[#002f80] mb-4">Impact Map — {{ $document->title }}</h2>
            <p class="text-sm text-slate-500 mb-6">Relasi keluar (outgoing) = dokumen/sistem yang direferensikan. Relasi masuk (incoming) = dokumen yang mereferensikan dokumen ini.</p>

            <div class="grid lg:grid-cols-2 gap-6">
                {{-- Outgoing References --}}
                <div class="bg-slate-50/70 rounded-2xl p-5">
                    <h3 class="font-extrabold text-[#002f80] mb-4 flex items-center gap-2">
                        @include('components.icon', ['name' => 'arrow-right', 'class' => 'w-5 h-5'])
                        REFERENSI KELUAR (Outgoing) <span class="text-xs font-bold bg-[#002f80] text-white px-2 py-0.5 rounded-full">{{ $impactMap['references']->count() }}</span>
                    </h3>
                    @if($impactMap['references']->isEmpty())
                        <p class="text-sm text-slate-400 text-center py-8">Tidak ada referensi keluar.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($impactMap['references'] as $ref)
                                <a href="{{ $ref['route'] }}" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-[#d6d6d6] hover:border-[#002f80] hover:shadow-md transition group">
                                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full shrink-0 {{ $ref['target_type'] === 'system' ? 'bg-green-50 text-green-700' : 'bg-[#002f80]/5 text-[#002f80]' }}">{{ strtoupper($ref['type']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-slate-900 group-hover:text-[#002f80] truncate block">{{ $ref['title'] }}</div>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $ref['target_type'] === 'system' ? 'bg-green-50 text-green-700' : 'bg-[#002f80]/5 text-[#002f80]' }}">{{ ucfirst($ref['target_type']) }}</span>
                                        </div>
                                    </div>
                                    @include('components.icon', ['name' => 'chev-right', 'class' => 'w-5 h-5 text-slate-300 group-hover:text-[#002f80] shrink-0'])
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Incoming References --}}
                <div class="bg-slate-50/70 rounded-2xl p-5">
                    <h3 class="font-extrabold text-[#c90018] mb-4 flex items-center gap-2">
                        @include('components.icon', ['name' => 'arrow-left', 'class' => 'w-5 h-5'])
                        DIRUJUK OLEH (Incoming) <span class="text-xs font-bold bg-[#c90018] text-white px-2 py-0.5 rounded-full">{{ $impactMap['referenced_by']->count() }}</span>
                    </h3>
                    @if($impactMap['referenced_by']->isEmpty())
                        <p class="text-sm text-slate-400 text-center py-8">Belum ada dokumen yang mereferensikan dokumen ini.</p>
                    @else
                        <div class="space-y-3">
                            @foreach($impactMap['referenced_by'] as $ref)
                                <a href="{{ route('governance.show', $ref['slug']) }}" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-[#d6d6d6] hover:border-[#c90018] hover:shadow-md transition group">
                                    <span class="text-[10px] font-bold bg-[#c90018]/10 text-[#c90018] px-2.5 py-1 rounded-full shrink-0">{{ strtoupper($ref['type']) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-bold text-slate-900 group-hover:text-[#c90018] truncate block">{{ $ref['source_title'] }}</span>
                                        <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#c90018]/10 text-[#c90018]">{{ str_replace('_', ' ', $ref['source_type']) }}</span>
                                        </div>
                                    </div>
                                    @include('components.icon', ['name' => 'chev-right', 'class' => 'w-5 h-5 text-slate-300 group-hover:text-[#c90018] shrink-0'])
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-6 bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-4">
            <h3 class="font-extrabold text-[#002f80] mb-3">Legenda</h3>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-sm">
                <div class="flex items-center gap-2 p-2 bg-green-50 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-green-500 shrink-0"></span>
                    <span class="text-green-700 font-semibold">System</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-slate-100 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-slate-500 shrink-0"></span>
                    <span class="text-slate-600 font-semibold">PIC / Orang</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-[#002f80]/5 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-[#002f80] shrink-0"></span>
                    <span class="text-[#002f80] font-semibold">Policy / Regulation</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-amber-50 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-amber-700 font-semibold">SOP / Work Instruction</span>
                </div>
                <div class="flex items-center gap-2 p-2 bg-red-50 rounded-xl">
                    <span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span>
                    <span class="text-red-700 font-semibold">Form / Evidence</span>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-4 text-center">
        <a href="{{ route('governance.show', $document) }}" class="btn-ghost">← Kembali ke Detail</a>
    </div>

<script>
(function () {
  var svg = document.getElementById('kg-graph');
  if (!svg) return;
  var NS = 'http://www.w3.org/2000/svg';
  var data = @json($graph);
  var nodes = data.nodes || [], edges = data.edges || [];
  var CX = 450, CY = 290, R1 = 180, R2 = 268;

  function color(n) {
    if (n.kind === 'system') return '#16a34a';
    if (n.kind === 'person') return '#64748b';
    var t = (n.subtitle || '').toLowerCase();
    if (['policy', 'regulation', 'standard', 'decision'].indexOf(t) !== -1) return '#002f80';
    if (['sop', 'work_instruction'].indexOf(t) !== -1) return '#d97706';
    if (['form', 'evidence'].indexOf(t) !== -1) return '#c90018';
    return '#2f6BFF';
  }
  function short(s, n) { s = s || ''; return s.length > n ? s.slice(0, n - 1) + '…' : s; }
  function el(tag, attrs, parent) {
    var e = document.createElementNS(NS, tag);
    for (var k in attrs) e.setAttribute(k, attrs[k]);
    parent.appendChild(e);
    return e;
  }
  function text(parent, str, x, y, attrs) {
    var t = el('text', attrs, parent);
    t.setAttribute('x', x); t.setAttribute('y', y);
    t.textContent = str;
    return t;
  }

  var byId = {};
  nodes.forEach(function (n) { byId[n.id] = n; });
  var center = nodes.filter(function (n) { return n.ring === 0 && n.kind === 'document'; })[0] || nodes[0];
  var ring1 = nodes.filter(function (n) { return n !== center && n.ring <= 1; });
  var ring2 = nodes.filter(function (n) { return n.ring === 2; });

  // Derajat-1: outgoing kanan, incoming kiri, person bawah.
  var outs = [], ins = [], pers = [];
  ring1.forEach(function (n) {
    var incoming = edges.some(function (e) { return e.to === n.id && e.dir === 'in'; });
    var isPic = edges.some(function (e) { return e.to === n.id && e.label === 'pic'; });
    if (isPic) pers.push(n);
    else if (incoming && !edges.some(function (e) { return e.from === n.id && e.dir === 'out'; })) ins.push(n);
    else outs.push(n);
  });
  function spread(list, cx, cy, r, a0, a1) {
    list.forEach(function (n, i) {
      var a = list.length === 1 ? (a0 + a1) / 2 : a0 + (a1 - a0) * (i / (list.length - 1));
      n._x = cx + r * Math.cos(a); n._y = cy + r * Math.sin(a); n._a = a;
    });
  }
  if (center) { center._x = CX; center._y = CY; }
  spread(outs, CX, CY, R1, -1.15, 1.15);
  spread(ins, CX, CY, R1, Math.PI - 1.15, Math.PI + 1.15);
  spread(pers, CX, CY, R1, Math.PI / 2 - 0.7, Math.PI / 2 + 0.7);
  // Derajat-2: dekat sudut induknya di cincin luar.
  ring2.forEach(function (n, i) {
    var parent = null;
    edges.forEach(function (e) {
      if (e.to === n.id && byId[e.from] && byId[e.from]._x !== undefined) parent = byId[e.from];
      if (!parent && e.from === n.id && byId[e.to] && byId[e.to]._x !== undefined) parent = byId[e.to];
    });
    var a = parent && parent._a !== undefined ? parent._a + ((i % 3) - 1) * 0.28 : (i / Math.max(1, ring2.length)) * Math.PI * 2;
    n._x = CX + R2 * Math.cos(a); n._y = CY + R2 * Math.sin(a);
  });

  var defs = el('defs', {}, svg);
  var marker = el('marker', { id: 'kg-arrow', viewBox: '0 0 10 10', refX: 9, refY: 5, markerWidth: 7, markerHeight: 7, orient: 'auto-start-reverse' }, defs);
  el('path', { d: 'M 0 1 L 9 5 L 0 9 z', fill: '#94a3b8' }, marker);

  var gE = el('g', {}, svg), gL = el('g', {}, svg), gN = el('g', {}, svg);
  edges.forEach(function (e) {
    var a = byId[e.from], b = byId[e.to];
    if (!a || !b || a._x === undefined || b._x === undefined) return;
    el('line', { x1: a._x, y1: a._y, x2: b._x, y2: b._y, stroke: '#94a3b8', 'stroke-width': 1.5, 'marker-end': 'url(#kg-arrow)' }, gE);
    if (edges.length <= 24) {
      text(gL, short(String(e.label || '').replace(/_/g, ' '), 12), (a._x + b._x) / 2, (a._y + b._y) / 2 - 5,
        { 'font-size': 9, fill: '#94a3b8', 'text-anchor': 'middle', 'font-weight': 700 });
    }
  });
  nodes.forEach(function (n) {
    if (n._x === undefined) return;
    var g = el('g', { cursor: n.url ? 'pointer' : 'default' }, gN);
    var tip = el('title', {}, g);
    tip.textContent = n.title + (n.subtitle ? ' — ' + String(n.subtitle).replace(/_/g, ' ') : '') + (n.status ? ' · ' + n.status : '');
    el('circle', { cx: n._x, cy: n._y, r: n === center ? 22 : 15, fill: color(n), stroke: '#fff', 'stroke-width': 3,
      style: n === center ? 'filter:drop-shadow(0 2px 6px rgba(0,47,128,.35))' : '' }, g);
    if (n === center) el('circle', { cx: n._x, cy: n._y, r: 27, fill: 'none', stroke: '#c90018', 'stroke-width': 2, 'stroke-dasharray': '4 3' }, g);
    text(g, short(n.title, 16), n._x, n._y + (n === center ? 40 : 32),
      { 'font-size': 11, 'font-weight': 700, fill: '#0f172a', 'text-anchor': 'middle' });
    if (n.url) g.addEventListener('click', function () { window.location.href = n.url; });
  });
})();
</script>
@endsection