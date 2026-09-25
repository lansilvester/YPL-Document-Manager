@extends('layout')
@section('title', 'Semua Dokumen')
@section('content')
<h1 class="text-2xl font-bold mb-4">Semua Dokumen Publik</h1>
<div class="flex flex-wrap gap-2 mb-4 text-sm">
  <a href="{{ route('documents.index') }}" class="px-3 py-1 rounded-full border {{ !$activeCat ? 'bg-emerald-700 text-white' : 'bg-white' }}">Semua</a>
  @foreach($categories as $c)
    <a href="{{ route('documents.index', ['kategori' => $c->slug, 'q' => $q]) }}" class="px-3 py-1 rounded-full border {{ $activeCat === $c->slug ? 'bg-emerald-700 text-white' : 'bg-white' }}">{{ $c->name }}</a>
  @endforeach
</div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
  @forelse($docs as $d)
    <a href="{{ route('documents.show', $d) }}" class="bg-white rounded shadow p-4 hover:shadow-lg block">
      <div class="text-xs text-emerald-700 font-semibold">{{ $d->category->name ?? 'Tanpa kategori' }}</div>
      <h2 class="font-bold mt-1">{{ $d->title }}</h2>
      <p class="text-sm text-stone-600 line-clamp-2 mt-1">{{ $d->description }}</p>
      <div class="text-xs text-stone-500 mt-2">👁 {{ $d->views_count }} · oleh {{ $d->user->name }}</div>
    </a>
  @empty
    <p class="text-stone-500">Tidak ada dokumen ditemukan.</p>
  @endforelse
</div>
<div class="mt-4">{{ $docs->links() }}</div>
@endsection
