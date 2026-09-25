@extends('layout')
@section('title', 'Unggah PDF')
@section('content')
<div class="max-w-xl mx-auto bg-white rounded shadow p-6">
  <h1 class="text-xl font-bold mb-4">Unggah File PDF</h1>
  <form method="post" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-3">@csrf
    <div><label class="text-sm">Judul *</label><input name="title" required value="{{ old('title') }}" class="w-full border rounded px-3 py-2"></div>
    <div><label class="text-sm">Deskripsi</label><textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ old('description') }}</textarea></div>
    <div><label class="text-sm">Kategori</label><select name="category_id" class="w-full border rounded px-3 py-2"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
    <div><label class="text-sm">Siapa boleh lihat *</label><select name="visibility" class="w-full border rounded px-3 py-2"><option value="public">Publik (seluruh komunitas YPL)</option><option value="restricted">Terbatas (pengguna login)</option><option value="private">Privat (hanya saya)</option></select></div>
    <div><label class="text-sm">File PDF (maks 50 MB) *</label><input type="file" name="file" accept="application/pdf" required class="w-full border rounded px-3 py-2"></div>
    <button class="w-full bg-emerald-700 text-white rounded py-2">Unggah</button>
  </form>
</div>
@endsection
