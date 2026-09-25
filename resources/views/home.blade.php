@extends('layout')
@section('title', 'Beranda')
@section('content')
<div class="grid md:grid-cols-3 gap-6">
  <div class="md:col-span-2">
    <h1 class="text-2xl font-bold mb-1">Galeri Dokumen Publik</h1>
    <p class="text-sm text-stone-600 mb-4">Koleksi dokumen komunitas YPL. Gunakan pencarian atau filter kategori.</p>
    <div class="flex flex-wrap gap-2 mb-4 text-sm">
      <a href="{{ route('home') }}" class="px-3 py-1 rounded-full border {{ !$activeCat ? 'bg-emerald-700 text-white' : 'bg-white' }}">Semua</a>
      @foreach($categories as $c)
        <a href="{{ route('home', ['kategori' => $c->slug, 'q' => $q]) }}" class="px-3 py-1 rounded-full border {{ $activeCat === $c->slug ? 'bg-emerald-700 text-white' : 'bg-white' }}">{{ $c->name }}</a>
      @endforeach
    </div>
    @if($q)<p class="text-sm mb-3">Hasil pencarian: <strong>{{ $q }}</strong> ({{ $docs->total() }})</p>@endif
    <div class="grid sm:grid-cols-2 gap-4">
      @forelse($docs as $d)
        <a href="{{ route('documents.show', $d) }}" class="bg-white rounded shadow p-4 hover:shadow-lg block">
          <div class="text-xs text-emerald-700 font-semibold">{{ $d->category->name ?? 'Tanpa kategori' }} · {{ $d->source_type === 'editor' ? 'Editor' : 'PDF' }}</div>
          <h2 class="font-bold mt-1">{{ $d->title }}</h2>
          <p class="text-sm text-stone-600 line-clamp-2 mt-1">{{ $d->description }}</p>
          <div class="text-xs text-stone-500 mt-2">👁 {{ $d->views_count }} · oleh {{ $d->user->name }}</div>
        </a>
      @empty
        <p class="text-stone-500">Belum ada dokumen. @auth<a class="underline" href="{{ route('documents.create') }}">Unggah yang pertama</a>.@endauth</p>
      @endforelse
    </div>
    <div class="mt-4">{{ $docs->links() }}</div>
  </div>
  <aside>
    <div class="bg-white rounded shadow p-4">
      <h3 class="font-bold mb-3">🔥 Dokumen Paling Dibaca</h3>
      <ol class="space-y-2 text-sm">
        @forelse($popular as $i => $p)
          <li><a class="hover:underline" href="{{ route('documents.show', $p) }}"><strong>{{ $i + 1 }}.</strong> {{ $p->title }} <span class="text-stone-500">({{ $p->views_count }})</span></a></li>
        @empty
          <li class="text-stone-500">Belum ada data.</li>
        @endforelse
      </ol>
    </div>
  </aside>
</div>
@endsection
