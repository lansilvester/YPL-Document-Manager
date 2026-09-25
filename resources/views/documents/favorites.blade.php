@extends('layout')
@section('title', 'Favorit Saya')
@section('content')
<h1 class="text-2xl font-extrabold tracking-tight">★ Dokumen Favorit</h1>
<p class="text-sm text-slate-500 mb-5">Referensi penting yang kamu simpan.</p>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
  @forelse($docs as $d)
    @include('components.doc-card', ['d' => $d])
  @empty
    <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-2xl border border-dashed border-slate-300 p-10 text-center">
      <div class="text-4xl">☆</div>
      <p class="font-bold mt-2">Belum ada favorit</p>
      <p class="text-sm text-slate-500">Buka dokumen lalu tekan <strong>☆ Simpan ke Favorit</strong>.</p>
    </div>
  @endforelse
</div>
<div class="mt-4">{{ $docs->links() }}</div>
@endsection
