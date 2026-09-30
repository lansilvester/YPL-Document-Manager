@extends('layout')
@section('title', 'Ubah Dokumen')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dokumen Saya', route('documents.my')], ['Ubah Dokumen']]])
<div class="max-w-xl mx-auto bg-white rounded-3xl border border-[#d6d6d6] shadow-xl shadow-[#002f80]/5 p-7 sm:p-8">
  <h1 class="text-xl font-extrabold tracking-tight text-[#002f80]">Ubah Dokumen</h1>
  <p class="text-sm text-slate-500 mb-5">{{ $document->title }}</p>
  <form method="post" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data" class="space-y-4">@csrf @method('PUT')
    <div><label class="text-[13px] font-bold">Judul *</label><input name="title" required value="{{ old('title', $document->title) }}" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="3" class="field mt-1">{{ old('description', $document->description) }}</textarea></div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="text-[13px] font-bold">Kategori</label><select name="category_id" class="field mt-1"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ $document->category_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
      <div><label class="text-[13px] font-bold">Siapa boleh lihat *</label><select name="visibility" class="field mt-1">@foreach(['public' => 'Publik', 'restricted' => 'Terbatas (login)', 'private' => 'Privat'] as $v => $l)<option value="{{ $v }}" {{ $document->visibility === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="text-[13px] font-bold">Tipe dokumen</label><select name="doc_type" class="field mt-1"><option value="">— Pilih tipe —</option>@foreach(\App\Models\Document::TYPES as $t)<option value="{{ $t }}" {{ $document->doc_type === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>@endforeach</select></div>
      <div><label class="text-[13px] font-bold">Klasifikasi</label><select name="classification" class="field mt-1"><option value="">— Pilih —</option>@foreach(['public' => 'Public', 'internal' => 'Internal', 'confidential' => 'Confidential'] as $v => $l)<option value="{{ $v }}" {{ $document->classification === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
    </div>
    <div class="grid sm:grid-cols-3 gap-4">
      <div><label class="text-[13px] font-bold">Versi</label><input name="version" value="{{ old('version', $document->version) }}" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Berlaku</label><input type="date" name="effective_at" value="{{ old('effective_at', $document->effective_at?->format('Y-m-d')) }}" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Review</label><input type="date" name="review_at" value="{{ old('review_at', $document->review_at?->format('Y-m-d')) }}" class="field mt-1"></div>
    </div>
    <div><label class="text-[13px] font-bold">Ganti file PDF (opsional)</label><input type="file" name="file" accept="application/pdf" class="field mt-1 file:mr-3 file:rounded-full file:border-0 file:bg-[#002f80]/10 file:text-[#002f80] file:text-xs file:font-bold file:px-4 file:py-2"></div>
    <button class="btn-primary w-full !py-3">@include('components.icon', ['name' => 'check', 'class' => 'w-4 h-4']) Simpan Perubahan</button>
  </form>
</div>
@endsection
