@extends('layout')
@section('title', 'Peta Pengetahuan')
@section('content')
@include('components.back-bar', ['trail' => [
    ['Beranda', route('home')],
    ['Library', route('library.index')],
    ['Peta Pengetahuan'],
]])

<p class="page-eyebrow text-[var(--ypl-red)] mt-2">LIBRARY · KNOWLEDGE GRAPH</p>
<h1 class="page-title">Peta Pengetahuan YPL</h1>
<p class="text-sm text-slate-500 mt-1">Semua yang terkait dengan satu topik — dokumen, sistem, agenda, FAQ, dan PIC dalam satu graf. Hanya konten yang boleh Anda lihat yang tampil.</p>

<div class="grid grid-cols-3 gap-3 mt-4 max-w-2xl">
  @foreach([['Dokumen', $stats['dokumen'], 'file'], ['Relasi', $stats['relasi'], 'link'], ['Sistem', $stats['sistem'], 'tool']] as [$label, $n, $icon])
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] p-4 flex items-center gap-3">
      <span class="w-10 h-10 rounded-xl grid place-items-center text-white grad-navy shrink-0">@include('components.icon', ['name' => $icon, 'class' => 'w-5 h-5'])</span>
      <span class="min-w-0"><span class="block text-2xl font-extrabold text-[var(--ypl-navy)] tabular-nums leading-none">{{ number_format($n) }}</span><span class="block text-[11px] font-bold tracking-widest text-slate-400 mt-1">{{ strtoupper($label) }}</span></span>
    </div>
  @endforeach
</div>

<form action="{{ route('library.graph') }}" method="get" class="mt-5 flex gap-2 bg-white rounded-full border border-[var(--ypl-gray)] p-1.5 max-w-xl">
  <input name="q" value="{{ $q }}" placeholder="Cari dokumen untuk dijadikan pusat peta…" class="flex-1 bg-transparent text-sm px-4 outline-none min-w-0" aria-label="Cari dokumen pusat peta">
  <button class="shrink-0 rounded-full font-bold text-sm px-5 py-2 text-white grad-navy inline-flex items-center gap-2">@include('components.icon', ['name' => 'search', 'class' => 'w-4 h-4']) Cari</button>
</form>

@if($focus && $graph)
  <div class="mt-6 bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
      <h2 class="font-extrabold text-[var(--ypl-navy)]">Graf: {{ $focus->title }}</h2>
      <div class="flex items-center gap-2">
        <span class="text-xs font-bold text-slate-400">Kedalaman</span>
        <div class="flex items-center gap-1 rounded-full bg-slate-100 p-1">
          @foreach([1, 2] as $d)
            <a href="{{ route('library.graph', ['dokumen' => $focus->slug, 'kedalaman' => $d]) }}" class="px-3 py-1 rounded-full text-xs font-bold {{ $depth === $d ? 'bg-[var(--ypl-navy)] text-white' : 'text-slate-600 hover:bg-slate-200' }}" aria-current="{{ $depth === $d ? 'true' : 'false' }}">{{ $d }}</a>
          @endforeach
        </div>
        <a href="{{ $focus->isGovernance() ? route('governance.impact', $focus) : route('documents.show', $focus) }}" class="text-xs font-bold text-slate-400 hover:text-[var(--ypl-navy)]">Impact Map lengkap →</a>
      </div>
    </div>
    <p class="text-sm text-slate-500 mb-4">Simpul tengah adalah dokumen yang Anda pilih; cincin pertama & kedua adalah yang tertaut padanya. Klik simpul untuk membuka.</p>
    @include('components.knowledge-graph', ['graph' => $graph, 'id' => 'kg-explore', 'label' => 'Graf peta pengetahuan untuk '.$focus->title])
    @if($graph['truncated'] > 0)
      <p class="text-xs text-slate-400 mt-2">+{{ $graph['truncated'] }} simpul lain disembunyikan agar graf tetap terbaca.</p>
    @endif
    @if(count($graph['edges']) === 0)
      <p class="text-sm text-slate-400 mt-3">Dokumen ini belum memiliki relasi pengetahuan.</p>
    @endif
  </div>
@elseif($focusSlug && ! $focus)
  <div class="mt-6 bg-white rounded-2xl border border-dashed border-[var(--ypl-gray)] p-8 text-center text-slate-500">Dokumen yang Anda cari tidak ditemukan atau bersifat privat.</div>
@endif

<div class="mt-6 bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-6">
  <h2 class="font-extrabold text-[var(--ypl-navy)]">Pilih Pusat Peta</h2>
  <p class="text-sm text-slate-500 mt-1">Dokumen berikut paling banyak terhubung dengan pengetahuan lain.</p>
  <div class="mt-4 grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
    @forelse($centers as $d)
      <a href="{{ route('library.graph', ['dokumen' => $d->slug, 'kedalaman' => $depth]) }}" class="card-hover bg-white rounded-xl border border-[var(--ypl-gray)] p-4 block">
        <div class="text-[11px] font-bold text-slate-400">{{ $d->category->name ?? 'Tanpa kategori' }}</div>
        <div class="font-bold text-slate-900 leading-snug mt-0.5 line-clamp-2">{{ $d->title }}</div>
        <div class="text-xs text-slate-500 mt-2">{{ ($d->outgoing_relations_count ?? 0) + ($d->incoming_relations_count ?? 0) }} relasi · {{ number_format($d->views_count) }} dibaca</div>
      </a>
    @empty
      <p class="text-sm text-slate-400 col-span-full">Belum ada dokumen dengan relasi. Tambahkan relasi lewat detail dokumen.</p>
    @endforelse
  </div>
</div>

<div class="mt-6 bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-4">
  <h3 class="font-extrabold text-[var(--ypl-navy)] mb-3">Legenda</h3>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-sm">
    @foreach([['System', 'bg-green-500'], ['Agenda', 'bg-violet-600'], ['FAQ', 'bg-sky-600'], ['PIC / Orang', 'bg-slate-500'], ['Policy / Regulation', 'bg-[var(--ypl-navy)]'], ['SOP / Form', 'bg-amber-500']] as [$label, $dot])
      <div class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl"><span class="w-3 h-3 rounded-full {{ $dot }} shrink-0"></span><span class="text-slate-600 font-semibold">{{ $label }}</span></div>
    @endforeach
  </div>
</div>
@endsection
