@extends('layout')
@section('title', 'Favorit Saya')
@section('content')
<div class="flex items-center gap-3 mb-1">
  <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,#a30014,#c90018)">@include('components.icon', ['name' => 'star', 'class' => 'w-5 h-5'])</span>
  <div>
    <h1 class="text-2xl font-extrabold tracking-tight text-[#002f80]">Dokumen Favorit</h1>
    <p class="text-sm text-slate-500">Referensi penting yang kamu simpan.</p>
  </div>
</div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
  @forelse($docs as $d)
    @include('components.doc-card', ['d' => $d])
  @empty
    <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-2xl border border-dashed border-[#d6d6d6] p-10 text-center text-slate-400">
      <span class="inline-block">@include('components.icon', ['name' => 'bookmark', 'class' => 'w-12 h-12'])</span>
      <p class="font-bold mt-2 text-slate-700">Belum ada favorit</p>
      <p class="text-sm text-slate-500">Buka dokumen lalu tekan <strong>Simpan ke Favorit</strong>.</p>
    </div>
  @endforelse
</div>
<div class="mt-4">{{ $docs->links() }}</div>
@endsection
