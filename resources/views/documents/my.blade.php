@extends('layout')
@section('title', 'Dokumen Saya')
@section('content')
<h1 class="text-2xl font-bold mb-4">Kelola Dokumen Saya</h1>
<div class="bg-white rounded shadow divide-y">
  @forelse($docs as $d)
    <div class="p-4 flex flex-wrap items-center gap-3">
      <div class="flex-1 min-w-52">
        <a class="font-bold hover:underline" href="{{ route('documents.show', $d) }}">{{ $d->title }}</a>
        <div class="text-xs text-stone-500">{{ $d->category->name ?? 'Tanpa kategori' }} · {{ ucfirst($d->visibility) }} · 👁 {{ $d->views_count }} · {{ $d->created_at->format('d M Y') }}</div>
      </div>
      @if($d->source_type === 'editor')
        <a href="{{ route('editor.edit', $d) }}" class="text-sm border rounded px-3 py-1">Ubah Isi</a>
      @else
        <a href="{{ route('documents.edit', $d) }}" class="text-sm border rounded px-3 py-1">Ubah</a>
      @endif
      <form method="post" action="{{ route('documents.destroy', $d) }}" onsubmit="return confirm('Hapus dokumen ini?')">@csrf @method('DELETE')<button class="text-sm border rounded px-3 py-1 text-red-700">Hapus</button></form>
    </div>
  @empty
    <p class="p-4 text-stone-500">Belum ada dokumen. <a class="underline" href="{{ route('documents.create') }}">Unggah PDF</a> atau <a class="underline" href="{{ route('editor.create') }}">tulis dokumen</a>.</p>
  @endforelse
</div>
<div class="mt-4">{{ $docs->links() }}</div>
@endsection
