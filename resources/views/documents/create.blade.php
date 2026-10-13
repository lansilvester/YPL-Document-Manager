@extends('layout')
@section('title', 'Unggah PDF')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dokumen Saya', route('documents.my')], ['Unggah PDF']]])
<div class="max-w-xl mx-auto bg-white rounded-3xl border border-[var(--ypl-gray)] shadow-xl shadow-[var(--ypl-navy)]/5 p-7 sm:p-8">
  <div class="w-12 h-12 rounded-2xl grid place-items-center text-white mb-3 grad-navy">@include('components.icon', ['name' => 'upload', 'class' => 'w-5 h-5'])</div>
  <h1 class="text-xl font-extrabold tracking-tight text-[var(--ypl-navy)]">Unggah File PDF</h1>
  <p class="text-sm text-slate-500 mb-5">Bagikan materi ke seluruh komunitas YPL dalam hitungan detik.</p>
  <form method="post" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-4">@csrf
    <div><label class="text-[13px] font-bold">Judul dokumen *</label><input name="title" required value="{{ old('title') }}" placeholder="cth: Panduan Praktikum IPA Kelas 8" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Subjudul</label><input name="subtitle" value="{{ old('subtitle') }}" placeholder="Anak judul / konteks singkat…" class="field mt-1"></div>
    <div><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="3" placeholder="Ringkasan isi dokumen…" class="field mt-1">{{ old('description') }}</textarea></div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="text-[13px] font-bold">Kategori</label><select name="category_id" class="field mt-1"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
      <div><label class="text-[13px] font-bold">Siapa boleh lihat *</label><select name="visibility" class="field mt-1"><option value="public">Publik — seluruh komunitas</option><option value="restricted">Terbatas — pengguna login</option><option value="private">Privat — hanya saya</option></select></div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="text-[13px] font-bold">Tipe dokumen</label><select name="doc_type" class="field mt-1"><option value="">— Pilih tipe —</option>@foreach(\App\Models\Document::TYPES as $t)<option value="{{ $t }}">{{ \App\Models\Document::typeLabel($t) }}</option>@endforeach</select></div>
      <div><label class="text-[13px] font-bold">Klasifikasi</label><select name="classification" class="field mt-1"><option value="">— Pilih —</option><option value="public">Public</option><option value="internal">Internal</option><option value="confidential">Confidential</option></select></div>
    </div>
    <div class="grid sm:grid-cols-3 gap-4">
      <div><label class="text-[13px] font-bold">Versi</label><input name="version" value="1.0" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Berlaku</label><input type="date" name="effective_at" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Review</label><input type="date" name="review_at" class="field mt-1"></div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="text-[13px] font-bold">Unit</label><input name="unit" value="{{ old('unit') }}" placeholder="cth: Direktorat Keuangan" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">PIC</label><select name="pic_id" class="field mt-1"><option value="">— Pilih PIC —</option>@foreach($pics as $p)<option value="{{ $p->id }}">{{ $p->name }} · {{ $p->role }}</option>@endforeach</select></div>
    </div>
    <div><span class="text-[13px] font-bold">Audience</span><div class="flex flex-wrap gap-2 mt-1">@foreach(\App\Models\Document::AUDIENCES as $a)<label class="text-xs font-semibold inline-flex items-center gap-1.5 bg-slate-50 border rounded-full px-3 py-1.5"><input type="checkbox" name="audience[]" value="{{ $a }}"> {{ str_replace('_', ' ', $a) }}</label>@endforeach</div></div>
    <p class="text-[11px] text-slate-400">Dokumen baru masuk sebagai <strong>DRAFT</strong>. Alur persetujuan ada di halaman detail.</p>
    <div><label class="text-[13px] font-bold">File PDF (maks 9 MB) *</label><input type="file" name="file" accept="application/pdf" required class="field mt-1 file:mr-3 file:rounded-full file:border-0 file:bg-[var(--ypl-navy)]/10 file:text-[var(--ypl-navy)] file:text-xs file:font-bold file:px-4 file:py-2"></div>
    <button class="btn-primary w-full !py-3">@include('components.icon', ['name' => 'upload', 'class' => 'w-4 h-4']) Unggah Dokumen</button>
  </form>
</div>
@endsection
