@extends('layout')
@section('title', 'Semua Dokumen')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Jelajahi Dokumen']]])
<div class="flex items-center gap-3">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,#001c55,#0046c0)">@include('components.icon', ['name' => 'compass', 'class' => 'w-5 h-5'])</span>
  <div>
    <h1 class="text-2xl font-extrabold tracking-tight text-[#002f80]">Jelajahi Dokumen</h1>
    <p class="text-sm text-slate-500">{{ $docs->total() }} dokumen publik komunitas YPL.</p>
  </div>
</div>
<div class="flex gap-2 mt-4 mb-5 text-[13px] font-semibold overflow-x-auto pb-1">
  <a href="{{ route('documents.index', ['q' => $q]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ !$activeCat ? 'bg-[#002f80] text-white border-[#002f80] shadow' : 'bg-white border-[#d6d6d6] hover:border-[#002f80]' }}">Semua</a>
  @foreach($categories as $c)
    <a href="{{ route('documents.index', ['kategori' => $c->slug, 'q' => $q]) }}" class="shrink-0 px-4 py-1.5 rounded-full border transition {{ $activeCat === $c->slug ? 'bg-[#002f80] text-white border-[#002f80] shadow' : 'bg-white border-[#d6d6d6] hover:border-[#002f80]' }}">{{ $c->name }}</a>
  @endforeach
</div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
  @forelse($docs as $d)
    @include('components.doc-card', ['d' => $d])
  @empty
    <p class="text-slate-500 bg-white rounded-2xl border border-dashed border-[#d6d6d6] p-8 text-center sm:col-span-2 lg:col-span-3">Tidak ada dokumen ditemukan. Coba kata kunci atau kategori lain.</p>
  @endforelse
</div>
<div class="mt-5">{{ $docs->links() }}</div>
@endsection
