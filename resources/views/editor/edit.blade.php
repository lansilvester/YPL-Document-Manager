@extends('layout')
@section('title', 'Ubah Isi Dokumen')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded shadow p-6">
  <h1 class="text-xl font-bold mb-4">Ubah Isi: {{ $document->title }}</h1>
  <form method="post" action="{{ route('editor.update', $document) }}" class="space-y-3">@csrf @method('PUT')
    <div><label class="text-sm">Judul *</label><input name="title" required value="{{ old('title', $document->title) }}" class="w-full border rounded px-3 py-2"></div>
    <div class="grid sm:grid-cols-2 gap-3">
      <div><label class="text-sm">Kategori</label><select name="category_id" class="w-full border rounded px-3 py-2"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ $document->category_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
      <div><label class="text-sm">Siapa boleh lihat *</label><select name="visibility" class="w-full border rounded px-3 py-2">@foreach(['public' => 'Publik', 'restricted' => 'Terbatas (login)', 'private' => 'Privat'] as $v => $l)<option value="{{ $v }}" {{ $document->visibility === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    </div>
    <div><label class="text-sm">Deskripsi</label><textarea name="description" rows="2" class="w-full border rounded px-3 py-2">{{ old('description', $document->description) }}</textarea></div>
    <div><label class="text-sm">Isi dokumen *</label><textarea name="content" rows="14" required class="w-full border rounded px-3 py-2 font-mono text-sm">{{ old('content', $document->content) }}</textarea></div>
    <button class="w-full bg-emerald-700 text-white rounded py-2">Perbarui & Buat Ulang PDF</button>
  </form>
</div>
@endsection
