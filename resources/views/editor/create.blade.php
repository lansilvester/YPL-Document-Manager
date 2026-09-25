@extends('layout')
@section('title', 'Tulis Dokumen')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded shadow p-6">
  <h1 class="text-xl font-bold mb-1">Tulis Isi Dokumen</h1>
  <p class="text-sm text-stone-600 mb-4">Susun materi langsung di platform — otomatis disimpan sebagai PDF yang siap dibagikan.</p>
  <form method="post" action="{{ route('editor.store') }}" class="space-y-3">@csrf
    <div><label class="text-sm">Judul *</label><input name="title" required value="{{ old('title') }}" class="w-full border rounded px-3 py-2"></div>
    <div class="grid sm:grid-cols-2 gap-3">
      <div><label class="text-sm">Kategori</label><select name="category_id" class="w-full border rounded px-3 py-2"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
      <div><label class="text-sm">Siapa boleh lihat *</label><select name="visibility" class="w-full border rounded px-3 py-2"><option value="public">Publik</option><option value="restricted">Terbatas (login)</option><option value="private">Privat</option></select></div>
    </div>
    <div><label class="text-sm">Deskripsi</label><textarea name="description" rows="2" class="w-full border rounded px-3 py-2">{{ old('description') }}</textarea></div>
    <div><label class="text-sm">Isi dokumen * (boleh HTML sederhana: p, h2/h3, ul, strong)</label><textarea name="content" rows="14" required class="w-full border rounded px-3 py-2 font-mono text-sm">{{ old('content') }}</textarea></div>
    <button class="w-full bg-emerald-700 text-white rounded py-2">Simpan sebagai PDF</button>
  </form>
</div>
@endsection
