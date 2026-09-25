@extends('layout')
@section('title', 'Unggah PDF')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dokumen Saya', route('documents.my')], ['Unggah PDF']]])
<div class="max-w-xl mx-auto bg-white rounded-3xl border border-[#d6d6d6] shadow-xl shadow-[#002f80]/5 p-7 sm:p-8">
  <div class="w-12 h-12 rounded-2xl grid place-items-center text-white mb-3" style="background:linear-gradient(135deg,#002f80,#0046c0)">@include('components.icon', ['name' => 'upload', 'class' => 'w-5 h-5'])</div>
  <h1 class="text-xl font-extrabold tracking-tight text-[#002f80]">Unggah File PDF</h1>
  <p class="text-sm text-slate-500 mb-5">Bagikan materi ke seluruh komunitas YPL dalam hitungan detik.</p>
  <form method="post" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-4">@csrf
    <div><label class="text-[13px] font-bold">Judul dokumen *</label><input name="title" required value="{{ old('title') }}" placeholder="cth: Panduan Praktikum IPA Kelas 8" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="3" placeholder="Ringkasan isi dokumen…" class="field mt-1">{{ old('description') }}</textarea></div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="text-[13px] font-bold">Kategori</label><select name="category_id" class="field mt-1"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
      <div><label class="text-[13px] font-bold">Siapa boleh lihat *</label><select name="visibility" class="field mt-1"><option value="public">Publik — seluruh komunitas</option><option value="restricted">Terbatas — pengguna login</option><option value="private">Privat — hanya saya</option></select></div>
    </div>
    <div><label class="text-[13px] font-bold">File PDF (maks 50 MB) *</label><input type="file" name="file" accept="application/pdf" required class="field mt-1 file:mr-3 file:rounded-full file:border-0 file:bg-[#002f80]/10 file:text-[#002f80] file:text-xs file:font-bold file:px-4 file:py-2"></div>
    <button class="btn-primary w-full !py-3">@include('components.icon', ['name' => 'upload', 'class' => 'w-4 h-4']) Unggah Dokumen</button>
  </form>
</div>
@endsection
