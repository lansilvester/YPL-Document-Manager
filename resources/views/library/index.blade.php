@extends('layout')
@section('title', 'Perpustakaan')
@section('content')
<div class="mt-2">
  <h1 class="page-title">YPL DIGITAL BOOKSHELF</h1>
  <p class="text-xs sm:text-sm text-slate-500 mt-1">Institutional knowledge — designed to be read, explored and connected.</p>
  <div class="flex gap-2 mt-3 text-[13px] font-semibold overflow-x-auto pb-1">
    <a href="{{ route('library.index', ['koleksi' => 'semua']) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ $filter === 'semua' ? 'bg-[var(--ypl-navy)] text-white border-[var(--ypl-navy)]' : 'bg-white border-[var(--ypl-gray)] hover:border-[var(--ypl-navy)]' }}">All</a>
    @foreach(['handbook' => 'Handbooks', 'governance' => 'Governance', 'academic' => 'Learning'] as $key => $label)
      <a href="{{ route('library.index', ['koleksi' => $key]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ $filter === $key ? 'bg-[var(--ypl-navy)] text-white border-[var(--ypl-navy)]' : 'bg-white border-[var(--ypl-gray)] hover:border-[var(--ypl-navy)]' }}">{{ $label }}</a>
    @endforeach
  </div>
</div>

<h2 class="text-lg font-extrabold tracking-tight text-slate-900 mt-6 mb-3">THE YPL HANDBOOK COLLECTION</h2>
<style nonce="{{ csp_nonce() }}">
  .book { position: relative; aspect-ratio: 3/4.2; border-radius: 4px 12px 12px 4px; transition: transform .3s ease, box-shadow .3s ease; box-shadow: 0 10px 24px -12px rgba(0,20,60,.5); }
  .book:hover { transform: translateY(-6px) rotateY(-6deg); box-shadow: 0 24px 40px -16px rgba(0,20,60,.55); }
  .book::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 14px; border-radius: 4px 0 0 4px; background: linear-gradient(90deg, rgba(0,0,0,.45), rgba(255,255,255,.25) 55%, rgba(0,0,0,.25)); }
  .book::after { content: ''; position: absolute; top: 3px; bottom: 3px; right: 0; width: 7px; border-radius: 0 8px 8px 0; background: repeating-linear-gradient(90deg, var(--ypl-reader-panel) 0 1.5px, #d9d2c2 1.5px 2.5px); }
</style>
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-4 gap-y-6">
  @forelse($handbooks as $h)
    @php
      $palet = ['navy' => 'linear-gradient(150deg,var(--ypl-navy-800),var(--ypl-navy) 70%)', 'blue' => 'linear-gradient(150deg,var(--ypl-navy-500),var(--ypl-navy) 70%)', 'red' => 'linear-gradient(150deg,var(--ypl-red-800),var(--ypl-red) 70%)', 'deep-navy' => 'linear-gradient(150deg,var(--ypl-navy-900),#1e3a8a 70%)', 'purple' => 'linear-gradient(150deg,#4c1d95,#7c3aed 70%)', 'slate' => 'linear-gradient(150deg,#334155,var(--ypl-muted) 70%)', 'teal' => 'linear-gradient(150deg,#0f766e,#14b8a6 70%)', 'violet' => 'linear-gradient(150deg,#6d28d9,#8b5cf6 70%)', 'charcoal' => 'linear-gradient(150deg,#1f2937,#4b5563 70%)', 'brown' => 'linear-gradient(150deg,#7c2d12,#b45309 70%)'];
      $bg = $palet[$h->color ?? ''] ?? 'linear-gradient(150deg,var(--ypl-navy-800),var(--ypl-navy) 70%)';
    @endphp
    <a href="{{ route('library.show', $h) }}" class="book text-white p-4 pl-6 flex flex-col" style="background:{{ $bg }}" aria-label="{{ $h->name }}">
      <span class="text-xs font-bold opacity-80 tracking-widest">{{ $h->code ?? 'YPL' }}</span>
      <span class="font-extrabold leading-tight mt-2 flex-1 text-[15px]">{{ str_replace($h->code.' — ', '', $h->name) }}</span>
      <span class="text-[9px] font-bold tracking-wider opacity-70">YAYASAN PENDIDIKAN LOKON</span>
    </a>
  @empty
    <p class="text-sm text-slate-500 col-span-full">Koleksi handbook belum di-seed. Jalankan <code>php artisan db:seed --class=YplHandbooksSeeder</code>.</p>
  @endforelse
</div>
<p class="text-xs text-slate-500 mt-3">10 Living Digital Books • Version controlled • Connected knowledge</p>

@if($collections->isNotEmpty())
<h2 class="text-lg font-extrabold tracking-tight text-slate-900 mt-8 mb-3">EXPLORE KNOWLEDGE</h2>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
  @foreach($collections as $c)
    <a href="{{ route('library.show', $c) }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-5 block">
      <div class="font-extrabold text-slate-900">{{ $c->name }}</div>
      <div class="text-[13px] text-slate-500 mt-1">{{ $c->description }}</div>
      <div class="text-xs font-bold text-[var(--ypl-navy)] mt-3">Explore →</div>
    </a>
  @endforeach
</div>
@endif

<h2 class="text-lg font-extrabold tracking-tight text-slate-900 mt-8 mb-3">PETA PENGETAHUAN</h2>
<a href="{{ route('library.graph') }}" class="card-hover bg-white rounded-2xl border border-[var(--ypl-gray)] p-5 lg:p-6 flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
  <span class="w-12 h-12 rounded-2xl grid place-items-center text-white grad-navy shrink-0">@include('components.icon', ['name' => 'link', 'class' => 'w-6 h-6'])</span>
  <span class="min-w-0 flex-1">
    <span class="block font-extrabold text-slate-900">Knowledge Graph</span>
    <span class="block text-[13px] text-slate-500 mt-1">Lihat bagaimana dokumen, sistem, agenda, FAQ, dan PIC saling terhubung dalam satu peta — hanya konten yang boleh Anda lihat yang tampil.</span>
  </span>
  <span class="text-xs font-bold text-[var(--ypl-navy)] shrink-0">Buka Peta →</span>
</a>
@endsection
