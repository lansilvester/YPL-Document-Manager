@extends('layout')
@section('title', 'Tulis Dokumen')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-3xl border border-[#d6d6d6] shadow-xl shadow-[#002f80]/5 p-7 sm:p-8">
  <div class="w-12 h-12 rounded-2xl grid place-items-center text-white mb-3" style="background:linear-gradient(135deg,#002f80,#0046c0)">@include('components.icon', ['name' => 'pen', 'class' => 'w-5 h-5'])</div>
  <h1 class="text-xl font-extrabold tracking-tight text-[#002f80]">Tulis Isi Dokumen</h1>
  <p class="text-sm text-slate-500 mb-5">Susun materi langsung di platform — otomatis disimpan sebagai PDF yang siap dibagikan.</p>
  <form method="post" action="{{ route('editor.store') }}" class="space-y-4">@csrf
    <div><label class="text-[13px] font-bold">Judul *</label><input name="title" required value="{{ old('title') }}" placeholder="cth: Modul Pelatihan Guru Baru" class="field mt-1"></div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="text-[13px] font-bold">Kategori</label><select name="category_id" class="field mt-1"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
      <div><label class="text-[13px] font-bold">Siapa boleh lihat *</label><select name="visibility" class="field mt-1"><option value="public">Publik</option><option value="restricted">Terbatas (login)</option><option value="private">Privat</option></select></div>
    </div>
    <div><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="2" placeholder="Ringkasan…" class="field mt-1">{{ old('description') }}</textarea></div>
    <div>
      <label class="text-[13px] font-bold">Isi dokumen *</label>
      <p class="text-xs text-slate-400 mb-1">Mendukung HTML sederhana: <code>&lt;p&gt; &lt;h2&gt; &lt;h3&gt; &lt;ul&gt; &lt;ol&gt; &lt;strong&gt; &lt;em&gt;</code></p>
      <textarea name="content" rows="14" required placeholder="<h2>Pendahuluan</h2>&#10;<p>Tulis materimu di sini…</p>" class="field mt-1 font-mono !text-[13px] !leading-relaxed">{{ old('content') }}</textarea>
    </div>
    <button class="btn-primary w-full !py-3">@include('components.icon', ['name' => 'file', 'class' => 'w-4 h-4']) Simpan sebagai PDF</button>
  </form>
</div>
@endsection
