@extends('layout')
@section('title', $category->name)
@section('content')
<p class="text-xs font-bold tracking-widest text-slate-500 mt-2">LIBRARY / {{ strtoupper($category->collection ?? 'KOLEKSI') }} / {{ strtoupper($category->code ?? 'YPL') }}</p>

@php
  $palet = ['navy' => 'linear-gradient(135deg,#001c55,#002f80)', 'blue' => 'linear-gradient(135deg,#0a4fc4,#002f80)', 'red' => 'linear-gradient(135deg,#7a0010,#c90018)', 'deep-navy' => 'linear-gradient(135deg,#000f2e,#1e3a8a)', 'purple' => 'linear-gradient(135deg,#4c1d95,#7c3aed)', 'slate' => 'linear-gradient(135deg,#334155,#64748b)', 'teal' => 'linear-gradient(135deg,#0f766e,#14b8a6)', 'violet' => 'linear-gradient(135deg,#6d28d9,#8b5cf6)', 'charcoal' => 'linear-gradient(135deg,#1f2937,#4b5563)', 'brown' => 'linear-gradient(135deg,#7c2d12,#b45309)'];
  $bg = $palet[$category->color ?? ''] ?? 'linear-gradient(135deg,#001c55,#002f80)';
  $first = $docs->first();
@endphp

<div class="grid md:grid-cols-[340px_minmax(0,1fr)] lg:grid-cols-[400px_minmax(0,1fr)] gap-6 lg:gap-10 mt-3 items-start">
  {{-- Cover + metadata --}}
  <div class="relative rounded-r-2xl rounded-l-md text-white p-7 pl-11 min-h-[460px] lg:min-h-[540px] flex flex-col shadow-2xl" style="background:{{ $bg }}">
    <span class="absolute inset-y-0 left-0 w-6 rounded-l-md" style="background:linear-gradient(90deg, rgba(0,0,0,.45), rgba(255,255,255,.25) 55%, rgba(0,0,0,.25))" aria-hidden="true"></span>
    <span class="absolute top-2 bottom-2 right-0 w-2.5 rounded-r-2xl" style="background:repeating-linear-gradient(90deg, #f5f1e8 0 1.5px, #d9d2c2 1.5px 2.5px)" aria-hidden="true"></span>
    <span class="text-sm font-bold opacity-80 tracking-widest">{{ $category->code ?? 'YPL' }} · LIVING BOOK</span>
    <span class="font-extrabold text-2xl lg:text-3xl leading-tight mt-3 flex-1">{{ str_replace(($category->code ?? '').' — ', '', $category->name) }}</span>
    <span class="text-[11px] font-bold tracking-wider opacity-70 mt-4">YAYASAN PENDIDIKAN LOKON</span>
    <span class="text-[11px] tracking-wider opacity-70 mt-1">{{ date('Y') }} · VERSION 1.0</span>
  </div>

  <div class="min-w-0">
    <div class="flex flex-wrap items-center gap-2">
      <span class="text-xs font-bold bg-green-100 text-green-700 px-3 py-1 rounded-full">Active</span>
      <span class="text-xs font-bold bg-[#002f80]/5 text-[#002f80] px-3 py-1 rounded-full">v1.0</span>
      <span class="text-xs font-bold bg-slate-100 text-slate-600 px-3 py-1 rounded-full">{{ $docs->total() }} dokumen</span>
    </div>
    <h1 class="page-title mt-2">{{ $category->name }}</h1>
    <p class="text-sm text-slate-500 mt-1 max-w-2xl">{{ $category->description }}</p>
    <div class="flex flex-wrap items-center gap-3 mt-4">
      @if($first)
        <a href="{{ route('reader.show', $first) }}" class="btn-primary h-10 px-5 rounded-full inline-flex items-center justify-center gap-2 text-sm font-bold shadow-sm">
          @include('components.icon', ['name' => 'book', 'class' => 'w-4 h-4'])
          <span>Open Book</span>
          <span class="opacity-70">→</span>
        </a>
        @auth
          @if(\App\Models\Setting::feature('bookmark'))
            @include('components.bookmark-button', [
              'document' => $first,
              'marked' => $firstMarked,
              'labelOn' => 'Tersimpan',
              'labelOff' => 'Bookmark',
              'btnClass' => 'btn-ghost h-10 px-4 rounded-full inline-flex items-center justify-center gap-2 text-sm font-bold cursor-pointer transition shadow-sm',
              'iconClass' => 'w-4 h-4',
              'activeClass' => '!border-amber-400 !bg-amber-50 !text-amber-800',
              'inactiveClass' => 'border-[#d6d6d6] bg-white text-slate-700 hover:border-[#002f80] hover:text-[#002f80]',
            ])
          @endif
        @else
          <a href="{{ route('login') }}" class="btn-ghost h-10 px-4 rounded-full inline-flex items-center justify-center gap-2 text-sm font-bold text-slate-700 hover:border-[#002f80] hover:text-[#002f80] transition shadow-sm">
            @include('components.icon', ['name' => 'bookmark', 'class' => 'w-4 h-4'])
            <span>Bookmark</span>
          </a>
        @endauth
      @else
        <span class="btn-ghost h-10 px-4 rounded-full inline-flex items-center justify-center gap-2 text-sm font-medium opacity-50 cursor-not-allowed">Belum ada isi</span>
      @endif
      <a href="{{ route('library.index') }}" class="btn-ghost h-10 px-4 rounded-full inline-flex items-center justify-center gap-2 text-sm font-bold text-slate-700 hover:border-[#002f80] hover:text-[#002f80] transition shadow-sm">
        <span>← Bookshelf</span>
      </a>
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
