@extends('layout')
@section('title', 'Ubah Dokumen')
@section('content')
<div class="max-w-xl mx-auto bg-white rounded-3xl border border-slate-200 shadow-xl shadow-emerald-900/5 p-7 sm:p-8">
  <h1 class="text-xl font-extrabold tracking-tight">Ubah Dokumen</h1>
  <p class="text-sm text-slate-500 mb-5">{{ $document->title }}</p>
  <form method="post" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data" class="space-y-4">@csrf @method('PUT')
    <div><label class="text-[13px] font-bold">Judul *</label><input name="title" required value="{{ old('title', $document->title) }}" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="3" class="field mt-1">{{ old('description', $document->description) }}</textarea></div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="text-[13px] font-bold">Kategori</label><select name="category_id" class="field mt-1"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ $document->category_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
      <div><label class="text-[13px] font-bold">Siapa boleh lihat *</label><select name="visibility" class="field mt-1">@foreach(['public' => '🌍 Publik', 'restricted' => '🔒 Terbatas (login)', 'private' => '🙈 Privat'] as $v => $l)<option value="{{ $v }}" {{ $document->visibility === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    </div>
    <div><label class="text-[13px] font-bold">Ganti file PDF (opsional)</label><input type="file" name="file" accept="application/pdf" class="field mt-1 file:mr-3 file:rounded-full file:border-0 file:bg-emerald-100 file:text-emerald-800 file:text-xs file:font-bold file:px-4 file:py-2"></div>
    <button class="btn-primary w-full !py-3">Simpan Perubahan</button>
  </form>
</div>
@endsection
