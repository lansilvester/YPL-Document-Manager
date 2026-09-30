@extends('layout')
@section('title', $category->name)
@section('content')
<p class="text-xs font-bold tracking-widest text-slate-500 mt-2">LIBRARY / {{ strtoupper($category->collection ?? 'KOLEKSI') }} / {{ strtoupper($category->code ?? 'YPL') }}</p>

@php
  $palet = ['navy' => 'linear-gradient(135deg,#001c55,#002f80)', 'blue' => 'linear-gradient(135deg,#0a4fc4,#002f80)', 'red' => 'linear-gradient(135deg,#7a0010,#c90018)', 'deep-navy' => 'linear-gradient(135deg,#000f2e,#1e3a8a)', 'purple' => 'linear-gradient(135deg,#4c1d95,#7c3aed)', 'slate' => 'linear-gradient(135deg,#334155,#64748b)', 'teal' => 'linear-gradient(135deg,#0f766e,#14b8a6)', 'violet' => 'linear-gradient(135deg,#6d28d9,#8b5cf6)', 'charcoal' => 'linear-gradient(135deg,#1f2937,#4b5563)', 'brown' => 'linear-gradient(135deg,#7c2d12,#b45309)'];
  $bg = $palet[$category->color ?? ''] ?? 'linear-gradient(135deg,#001c55,#002f80)';
  $first = $docs->first();
@endphp

<div class="grid md:grid-cols-[280px_minmax(0,1fr)] gap-6 mt-3 items-start">
  {{-- Cover + metadata --}}
  <div class="relative rounded-r-2xl rounded-l-md text-white p-6 pl-9 min-h-80 flex flex-col shadow-2xl" style="background:{{ $bg }}">
    <span class="absolute inset-y-0 left-0 w-5 rounded-l-md" style="background:linear-gradient(90deg, rgba(0,0,0,.45), rgba(255,255,255,.25) 55%, rgba(0,0,0,.25))" aria-hidden="true"></span>
    <span class="absolute top-2 bottom-2 right-0 w-2 rounded-r-2xl" style="background:repeating-linear-gradient(90deg, #f5f1e8 0 1.5px, #d9d2c2 1.5px 2.5px)" aria-hidden="true"></span>
    <span class="text-xs font-bold opacity-80">{{ $category->code ?? 'YPL' }} · LIVING BOOK</span>
    <span class="font-extrabold text-xl leading-tight mt-2 flex-1">{{ str_replace(($category->code ?? '').' — ', '', $category->name) }}</span>
    <span class="text-[10px] font-bold tracking-wider opacity-70 mt-4">YAYASAN PENDIDIKAN LOKON</span>
    <span class="text-[10px] tracking-wider opacity-70 mt-1">{{ date('Y') }} · VERSION 1.0</span>
  </div>

  <div class="min-w-0">
    <div class="flex flex-wrap items-center gap-2">
      <span class="text-xs font-bold bg-green-100 text-green-700 px-3 py-1 rounded-full">Active</span>
      <span class="text-xs font-bold bg-[#002f80]/5 text-[#002f80] px-3 py-1 rounded-full">v1.0</span>
      <span class="text-xs font-bold bg-slate-100 text-slate-600 px-3 py-1 rounded-full">{{ $docs->total() }} dokumen</span>
    </div>
    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#002f80] mt-2">{{ $category->name }}</h1>
    <p class="text-sm text-slate-500 mt-1 max-w-2xl">{{ $category->description }}</p>
    <div class="flex flex-wrap gap-2 mt-4">
      @if($first)
        <a href="{{ route('reader.show', $first) }}" class="btn-primary">OPEN BOOK →</a>
        @auth
          @if(\App\Models\Setting::feature('favorite'))
            @php $bookFav = \App\Models\Favorite::where('user_id', auth()->id())->where('document_id', $first->id)->exists(); @endphp
            <form method="post" action="{{ route('documents.favorite', $first) }}" class="inline">@csrf<button class="btn-ghost">Bookmark</button></form>
          @endif
        @else
          <a href="{{ route('login') }}" class="btn-ghost">Bookmark</a>
        @endauth
      @else
        <span class="btn-ghost opacity-50">Belum ada isi</span>
      @endif
      <a href="{{ route('library.index') }}" class="btn-ghost">← Bookshelf</a>
    </div>

    {{-- TOC --}}
    <h2 class="text-sm font-extrabold tracking-widest text-slate-500 mt-6 mb-2">TABLE OF CONTENTS</h2>
    <div class="bg-white rounded-2xl border border-[#d6d6d6] divide-y divide-slate-100 overflow-hidden">
      @forelse($docs as $i => $d)
        <div class="flex items-center gap-3 px-4 py-3">
          <span class="text-sm font-extrabold text-slate-300 w-7 text-center">{{ $i + 1 }}</span>
          <a href="{{ route('documents.show', $d) }}" class="min-w-0 flex-1">
            <span class="block text-sm font-bold text-slate-800 truncate hover:text-[#002f80]">{{ $d->title }}</span>
            <span class="text-[11px] text-slate-400">{{ number_format($d->views_count) }} dibaca</span>
          </a>
          <a href="{{ route('reader.show', $d) }}" class="shrink-0 text-xs font-bold text-[#002f80] border border-[#002f80]/20 rounded-full px-3 py-1.5 hover:bg-[#002f80] hover:text-white transition">Read →</a>
        </div>
      @empty
        <p class="text-sm text-slate-400 px-4 py-6 text-center">Belum ada dokumen di buku ini. Dokumen yang dikategorikan ke sini tampil otomatis sebagai bab.</p>
      @endforelse
    </div>
    <div class="mt-4">{{ $docs->links() }}</div>
    <p class="text-[11px] text-slate-400 mt-3">Document Passport penuh (owner, approver, effective/review date, relasi Policy–SOP–Form) hadir di Phase 3.</p>
  </div>
</div>
@endsection
