@extends('layout')
@section('title', 'Favorit Saya')
@section('content')
<h1 class="text-2xl font-bold mb-4">★ Dokumen Favorit</h1>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
  @forelse($docs as $d)
    <a href="{{ route('documents.show', $d) }}" class="bg-white rounded shadow p-4 hover:shadow-lg block">
      <div class="text-xs text-emerald-700 font-semibold">{{ $d->category->name ?? 'Tanpa kategori' }}</div>
      <h2 class="font-bold mt-1">{{ $d->title }}</h2>
      <div class="text-xs text-stone-500 mt-2">👁 {{ $d->views_count }} · oleh {{ $d->user->name }}</div>
    </a>
  @empty
    <p class="text-stone-500">Belum ada favorit. Tandai dokumen dengan tombol ☆ Favorit.</p>
  @endforelse
</div>
<div class="mt-4">{{ $docs->links() }}</div>
@endsection
