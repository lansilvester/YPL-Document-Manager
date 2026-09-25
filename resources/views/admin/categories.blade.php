@extends('layout')
@section('title', 'Kelola Kategori')
@section('content')
<h1 class="text-2xl font-extrabold tracking-tight mb-1">Kelola Kategori Yayasan</h1>
<p class="text-sm text-slate-500 mb-5">Struktur kategori mengikuti mata pelajaran, departemen, atau jenis dokumen.</p>
<div class="grid md:grid-cols-2 gap-5">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
    <h2 class="font-extrabold mb-2">Daftar Kategori</h2>
    <ul class="divide-y divide-slate-100 text-sm">
      @forelse($categories as $c)
        <li class="py-2.5 flex items-center gap-2">
          <span class="w-8 h-8 rounded-xl grid place-items-center text-white text-xs font-extrabold shrink-0" style="background:linear-gradient(135deg,#065f46,#0d9488)">🗂</span>
          <span class="flex-1"><strong>{{ $c->name }}</strong><br><span class="text-slate-400 text-xs">{{ $c->documents_count }} dokumen</span></span>
          <form method="post" action="{{ route('admin.categories.destroy', $c) }}" onsubmit="return confirm('Hapus kategori? Dokumen di dalamnya jadi tanpa kategori.')">@csrf @method('DELETE')<button class="text-[13px] font-bold border border-red-200 text-red-600 rounded-full px-3 py-1 hover:bg-red-50">Hapus</button></form>
        </li>
      @empty
        <li class="text-slate-400 py-4">Belum ada kategori.</li>
      @endforelse
    </ul>
  </div>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 h-fit">
    <h2 class="font-extrabold mb-3">Tambah Kategori</h2>
    <form method="post" action="{{ route('admin.categories.store') }}" class="space-y-3">@csrf
      <div><label class="text-[13px] font-bold">Nama *</label><input name="name" required placeholder="cth: Pendidikan Agama" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="3" class="field mt-1"></textarea></div>
      <button class="btn-primary">＋ Tambah Kategori</button>
    </form>
  </div>
</div>
@endsection
