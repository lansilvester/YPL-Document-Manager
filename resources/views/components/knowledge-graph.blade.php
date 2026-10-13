{{--
  Graf knowledge node-link (BAB 12). Reusable untuk Impact Map & halaman Peta Pengetahuan.
  Pakai: @include('components.knowledge-graph', ['graph' => $graph, 'id' => 'kg-1', 'label' => 'Graf relasi dokumen'])
  - Aksesibilitas: setiap simpul bertaut dapat difokus keyboard (SVG <a>), plus daftar teks fallback.
--}}
@php
  $id = $id ?? 'kg-graph';
  $label = $label ?? 'Graf relasi pengetahuan';
  $graph = $graph ?? ['nodes' => [], 'edges' => [], 'truncated' => 0];
  $markerId = 'kg-arrow-'.$id;
  $listId = 'kg-list-'.$id;
@endphp
<div class="rounded-xl border border-[var(--ypl-gray)] bg-slate-50/50 overflow-x-auto">
  <svg id="{{ $id }}" viewBox="0 0 900 600" class="w-full min-w-[640px] h-auto select-none" role="img" aria-label="{{ $label }}" aria-describedby="{{ $listId }}"></svg>
</div>
<ul id="{{ $listId }}" class="sr-only">
  @foreach(($graph['nodes'] ?? []) as $n)
    <li>
      {{ $n['kind'] }}: {{ $n['title'] }}
      @if(! empty($n['url']))
        — <a href="{{ $n['url'] }}">{{ $n['title'] }}</a>
      @endif
    </li>
  @endforeach
</ul>
<script nonce="{{ csp_nonce() }}">
(function () {
  var svg = document.getElementById(@json($id));
  if (!svg) return;
  var NS = 'http://www.w3.org/2000/svg';
  var XLINK = 'http://www.w3.org/1999/xlink';
  var data = @json($graph);
  var nodes = data.nodes || [], edges = data.edges || [];
  var CX = 450, CY = 290, R1 = 180, R2 = 268;
  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function color(n) {
    if (n.kind === 'system') return '#16a34a';
    if (n.kind === 'person') return '#64748b';
    if (n.kind === 'event') return '#7c3aed';
    if (n.kind === 'faq') return '#0284c7';
    var t = (n.subtitle || '').toLowerCase();
    if (['policy', 'regulation', 'standard', 'decision'].indexOf(t) !== -1) return '#002f80';
    if (['sop', 'work_instruction'].indexOf(t) !== -1) return '#d97706';
    if (['form', 'evidence'].indexOf(t) !== -1) return '#c90018';
    return '#2f6BFF';
  }
  function short(s, n) { s = s || ''; return s.length > n ? s.slice(0, n - 1) + '…' : s; }
  function el(tag, attrs, parent) {
    var e = document.createElementNS(NS, tag);
    for (var k in attrs) { if (k === 'xlink:href') { e.setAttributeNS(XLINK, 'href', attrs[k]); } else { e.setAttribute(k, attrs[k]); } }
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
  var marker = el('marker', { id: @json($markerId), viewBox: '0 0 10 10', refX: 9, refY: 5, markerWidth: 7, markerHeight: 7, orient: 'auto-start-reverse' }, defs);
  el('path', { d: 'M 0 1 L 9 5 L 0 9 z', fill: '#94a3b8' }, marker);

  var gE = el('g', {}, svg), gL = el('g', {}, svg), gN = el('g', {}, svg);
  edges.forEach(function (e) {
    var a = byId[e.from], b = byId[e.to];
    if (!a || !b || a._x === undefined || b._x === undefined) return;
    el('line', { x1: a._x, y1: a._y, x2: b._x, y2: b._y, stroke: '#94a3b8', 'stroke-width': 1.5, 'marker-end': 'url(#' + @json($markerId) + ')' }, gE);
    if (edges.length <= 24) {
      text(gL, short(String(e.label || '').replace(/_/g, ' '), 12), (a._x + b._x) / 2, (a._y + b._y) / 2 - 5,
        { 'font-size': 9, fill: '#94a3b8', 'text-anchor': 'middle', 'font-weight': 700 });
    }
  });
  nodes.forEach(function (n) {
    if (n._x === undefined) return;
    var parent = n.url ? el('a', { 'xlink:href': n.url, 'aria-label': n.title }, gN) : el('g', {}, gN);
    var g = el('g', { cursor: n.url ? 'pointer' : 'default' }, parent);
    var tip = el('title', {}, g);
    tip.textContent = n.title + (n.subtitle ? ' — ' + String(n.subtitle).replace(/_/g, ' ') : '') + (n.status ? ' · ' + n.status : '');
    el('circle', { cx: n._x, cy: n._y, r: n === center ? 22 : 15, fill: color(n), stroke: '#fff', 'stroke-width': 3,
      style: n === center ? 'filter:drop-shadow(0 2px 6px rgba(0,47,128,.35))' : '' }, g);
    if (n === center) el('circle', { cx: n._x, cy: n._y, r: 27, fill: 'none', stroke: '#c90018', 'stroke-width': 2, 'stroke-dasharray': '4 3' }, g);
    text(g, short(n.title, 16), n._x, n._y + (n === center ? 40 : 32),
      { 'font-size': 11, 'font-weight': 700, fill: '#0f172a', 'text-anchor': 'middle' });
  });
})();
</script>
