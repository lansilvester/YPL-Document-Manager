@extends('layout')
@section('main_width', 'max-w-[1200px]')
@section('title', 'Kelola Unit')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dasbor Admin', route('admin.dashboard')], ['Kelola Unit']]])
<h1 class="page-title mb-1">Kelola Unit</h1>
<p class="text-sm text-slate-500 mb-5">Unit organisasi YPL tampil sebagai direktori publik di halaman Komunitas.</p>

<div class="grid md:grid-cols-3 gap-5">
  <div class="md:col-span-2">
    <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
      <h2 class="font-extrabold mb-3 text-[#002f80]">Unit ({{ $units->count() }})</h2>
      <div class="space-y-3">
        @forelse($units as $u)
          <details class="border border-slate-100 rounded-xl overflow-hidden">
            <summary class="cursor-pointer list-none flex items-center gap-3 p-3">
              <span class="flex-1 min-w-0"><span class="block font-bold truncate">{{ $u->name }}</span><span class="block text-xs text-slate-400 truncate">{{ $u->description ?? '—' }} · {{ $u->contacts_count }} kontak</span></span>
              <span class="text-xs font-bold text-[#002f80]">Ubah ▾</span>
            </summary>
            <form method="post" action="{{ route('admin.units.update', $u) }}" class="border-t border-slate-100 p-3 grid sm:grid-cols-2 gap-3">@csrf @method('PUT')
              <div class="sm:col-span-2"><label class="text-[12px] font-bold">Nama *</label><input name="name" required value="{{ old('name', $u->name) }}" maxlength="120" class="field mt-1"></div>
              <div class="sm:col-span-2"><label class="text-[12px] font-bold">Deskripsi</label><input name="description" value="{{ old('description', $u->description) }}" maxlength="255" class="field mt-1"></div>
              <div><label class="text-[12px] font-bold">Urutan</label><input type="number" name="sort_order" value="{{ old('sort_order', $u->sort_order) }}" min="0" max="999" class="field mt-1"></div>
              <div class="flex items-end justify-between gap-2">
                <button class="btn-primary !py-2">Simpan</button>
                <button form="del-unit-{{ $u->id }}" class="text-[13px] font-bold border border-[#c90018]/30 text-[#c90018] rounded-full px-3 py-1.5 hover:bg-[#c90018]/5">Hapus</button>
              </div>
            </form>
            <form id="del-unit-{{ $u->id }}" method="post" action="{{ route('admin.units.destroy', $u) }}" data-confirm="Hapus unit {{ $u->name }}? Kontak terkait akan kehilangan unitnya.">@csrf @method('DELETE')</form>
          </details>
        @empty
          <p class="text-sm text-slate-400 py-4">Belum ada unit.</p>
        @endforelse
      </div>
    </div>
  </div>
  <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 h-fit">
    <h2 class="font-extrabold mb-3 text-[#002f80]">Tambah Unit</h2>
    <form method="post" action="{{ route('admin.units.store') }}" class="space-y-3">@csrf
      <div><label class="text-[13px] font-bold">Nama *</label><input name="name" required maxlength="120" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Deskripsi</label><input name="description" maxlength="255" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Urutan</label><input type="number" name="sort_order" min="0" max="999" class="field mt-1"></div>
      <button class="btn-primary">Tambah Unit</button>
    </form>
  </div>
</div>
@endsection
