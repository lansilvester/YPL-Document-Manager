@extends('layout')
@section('title', 'Ubah Isi Dokumen')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-3xl border border-[#d6d6d6] shadow-xl shadow-[#002f80]/5 p-7 sm:p-8">
  <h1 class="text-xl font-extrabold tracking-tight text-[#002f80]">Ubah Isi Dokumen</h1>
  <p class="text-sm text-slate-500 mb-5">{{ $document->title }} — PDF akan dibuat ulang otomatis.</p>
  <form method="post" action="{{ route('editor.update', $document) }}" class="space-y-4">@csrf @method('PUT')
    <div><label class="text-[13px] font-bold">Judul *</label><input name="title" required value="{{ old('title', $document->title) }}" class="field mt-1"></div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="text-[13px] font-bold">Kategori</label><select name="category_id" class="field mt-1"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ $document->category_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
      <div><label class="text-[13px] font-bold">Siapa boleh lihat *</label><select name="visibility" class="field mt-1">@foreach(['public' => 'Publik', 'restricted' => 'Terbatas (login)', 'private' => 'Privat'] as $v => $l)<option value="{{ $v }}" {{ $document->visibility === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    </div>
    <div><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="2" class="field mt-1">{{ old('description', $document->description) }}</textarea></div>
    <div><label class="text-[13px] font-bold">Isi dokumen *</label><textarea name="content" rows="14" required class="field mt-1 font-mono !text-[13px] !leading-relaxed">{{ old('content', $document->content) }}</textarea></div>
    <button class="btn-primary w-full !py-3">@include('components.icon', ['name' => 'check', 'class' => 'w-4 h-4']) Perbarui & Buat Ulang PDF</button>
  </form>
</div>
@endsection
