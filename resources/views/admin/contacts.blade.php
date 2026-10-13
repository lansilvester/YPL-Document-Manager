@extends('layout')
@section('main_width', 'max-w-[1200px]')
@section('title', 'Kelola Kontak PIC')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dasbor Admin', route('admin.dashboard')], ['Kelola Kontak PIC']]])
<h1 class="page-title mb-1">Kelola Kontak PIC</h1>
<p class="text-sm text-slate-500 mb-5">Kontak PIC tampil publik di halaman Komunitas dan hasil pencarian, agar orang dapat menghubungi unit yang tepat.</p>

<div class="grid md:grid-cols-3 gap-5">
  <div class="md:col-span-2">
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5">
      <h2 class="font-extrabold mb-3 text-[var(--ypl-navy)]">Kontak ({{ $contacts->count() }})</h2>
      <div class="space-y-3">
        @forelse($contacts as $c)
          <details class="border border-slate-100 rounded-xl overflow-hidden">
            <summary class="cursor-pointer list-none flex items-center gap-3 p-3">
              <span class="flex-1 min-w-0"><span class="block font-bold truncate">{{ $c->name }} <span class="text-slate-400 font-semibold">{{ $c->position ? '· '.$c->position : '' }}</span></span><span class="block text-xs text-slate-400 truncate">{{ $c->unit->name ?? 'Tanpa unit' }} · {{ $c->email ?? $c->phone ?? '—' }}</span></span>
              <span class="text-xs font-bold text-[var(--ypl-navy)]">Ubah ▾</span>
            </summary>
            <form method="post" action="{{ route('admin.contacts.update', $c) }}" class="border-t border-slate-100 p-3 grid sm:grid-cols-2 gap-3">@csrf @method('PUT')
              <div><label class="text-[12px] font-bold">Nama *</label><input name="name" required value="{{ old('name', $c->name) }}" maxlength="120" class="field mt-1"></div>
              <div><label class="text-[12px] font-bold">Jabatan</label><input name="position" value="{{ old('position', $c->position) }}" maxlength="120" class="field mt-1"></div>
              <div><label class="text-[12px] font-bold">Email</label><input type="email" name="email" value="{{ old('email', $c->email) }}" maxlength="160" class="field mt-1"></div>
              <div><label class="text-[12px] font-bold">Telepon</label><input name="phone" value="{{ old('phone', $c->phone) }}" maxlength="40" class="field mt-1"></div>
              <div><label class="text-[12px] font-bold">Unit</label><select name="unit_id" class="field mt-1"><option value="">— Tanpa unit —</option>@foreach($units as $u)<option value="{{ $u->id }}" {{ (int) old('unit_id', $c->unit_id) === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>@endforeach</select></div>
              <div><label class="text-[12px] font-bold">Urutan</label><input type="number" name="sort_order" value="{{ old('sort_order', $c->sort_order) }}" min="0" max="999" class="field mt-1"></div>
              <div class="sm:col-span-2 flex items-center justify-between gap-2">
                <button class="btn-primary !py-2">Simpan</button>
                <button form="del-contact-{{ $c->id }}" class="text-[13px] font-bold border border-[var(--ypl-red)]/30 text-[var(--ypl-red)] rounded-full px-3 py-1.5 hover:bg-[var(--ypl-red)]/5">Hapus</button>
              </div>
            </form>
            <form id="del-contact-{{ $c->id }}" method="post" action="{{ route('admin.contacts.destroy', $c) }}" data-confirm="Hapus kontak {{ $c->name }}?">@csrf @method('DELETE')</form>
          </details>
        @empty
          <p class="text-sm text-slate-400 py-4">Belum ada kontak.</p>
        @endforelse
      </div>
    </div>
  </div>
  <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5 h-fit">
    <h2 class="font-extrabold mb-3 text-[var(--ypl-navy)]">Tambah Kontak</h2>
    <form method="post" action="{{ route('admin.contacts.store') }}" class="space-y-3">@csrf
      <div><label class="text-[13px] font-bold">Nama *</label><input name="name" required maxlength="120" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Jabatan</label><input name="position" maxlength="120" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Email</label><input type="email" name="email" maxlength="160" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Telepon</label><input name="phone" maxlength="40" class="field mt-1"></div>
      <div><label class="text-[13px] font-bold">Unit</label><select name="unit_id" class="field mt-1"><option value="">— Tanpa unit —</option>@foreach($units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
      <button class="btn-primary">Tambah Kontak</button>
    </form>
  </div>
</div>
@endsection
