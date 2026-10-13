@extends('layout')
@section('title', 'Semua Dokumen')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Jelajahi Dokumen']]])
<div class="flex items-center gap-3">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,var(--ypl-navy-800),var(--ypl-navy-600))">@include('components.icon', ['name' => 'compass', 'class' => 'w-5 h-5'])</span>
  <div>
    <h1 class="page-title">Jelajahi Dokumen</h1>
    <p class="text-sm text-slate-500">{{ $docs->total() }} dokumen publik komunitas YPL.</p>
  </div>
</div>
<div class="flex gap-2 mt-4 mb-5 text-[13px] font-semibold overflow-x-auto pb-1">
  <a href="{{ route('documents.index', ['q' => $q]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ !$activeCat ? 'bg-[var(--ypl-navy)] text-white border-[var(--ypl-navy)] shadow' : 'bg-white border-[var(--ypl-gray)] hover:border-[var(--ypl-navy)]' }}">Semua</a>
  @foreach($categories as $c)
    <a href="{{ route('documents.index', ['kategori' => $c->slug, 'q' => $q]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ $activeCat === $c->slug ? 'bg-[var(--ypl-navy)] text-white border-[var(--ypl-navy)] shadow' : 'bg-white border-[var(--ypl-gray)] hover:border-[var(--ypl-navy)]' }}">{{ $c->name }}</a>
  @endforeach
</div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
  @forelse($docs as $d)
    @include('components.doc-card', ['d' => $d])
  @empty
    <p class="text-slate-500 bg-white rounded-2xl border border-dashed border-[var(--ypl-gray)] p-8 text-center sm:col-span-2 lg:col-span-3">Tidak ada dokumen ditemukan. Coba kata kunci atau kategori lain.</p>
  @endforelse
</div>
<div class="mt-5">{{ $docs->links() }}</div>
@endsection
