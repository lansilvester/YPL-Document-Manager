@extends('layout')
@section('title', 'Kelola Kategori')
@section('content')
<h1 class="text-2xl font-bold mb-4">Kelola Kategori Yayasan</h1>
<div class="grid md:grid-cols-2 gap-6">
  <div class="bg-white rounded shadow p-4">
    <h2 class="font-bold mb-2">Daftar Kategori</h2>
    <ul class="divide-y text-sm">
      @foreach($categories as $c)
        <li class="py-2 flex items-center gap-2"><span class="flex-1"><strong>{{ $c->name }}</strong> <span class="text-stone-500">({{ $c->documents_count }} dokumen)</span></span>
        <form method="post" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Hapus kategori?')">@csrf @method('DELETE')<button class="text-red-700 border rounded px-2 py-0.5">Hapus</button></form></li>
      @endforeach
    </ul>
  </div>
  <div class="bg-white rounded shadow p-4">
    <h2 class="font-bold mb-2">Tambah Kategori</h2>
    <form method="post" action="{{ route('admin.categories.store') }}" class="space-y-3">@csrf
      <div><label class="text-sm">Nama *</label><input name="name" required class="w-full border rounded px-3 py-2"></div>
      <div><label class="text-sm">Deskripsi</label><textarea name="description" rows="3" class="w-full border rounded px-3 py-2"></textarea></div>
      <button class="bg-emerald-700 text-white rounded px-4 py-2 text-sm">Tambah</button>
    </form>
  </div>
</div>
@endsection
