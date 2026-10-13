@extends('layout')
@section('main_width', 'max-w-[1200px]')
@section('title', 'Kelola Agenda')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Ruang Staff', route('staff.documents')], ['Kelola Agenda']]])
<h1 class="page-title mb-1">Kelola Agenda</h1>
<p class="text-sm text-slate-500 mb-5">Agenda tampil di Kalender YPL. Staf, admin, dan super admin dapat menambah, mengubah, dan menghapus.</p>

<div class="grid lg:grid-cols-3 gap-5">
  <div class="lg:col-span-2 space-y-4">
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100 font-extrabold text-[var(--ypl-navy)]">Agenda ({{ $events->count() }})</div>
      <div class="divide-y divide-slate-100">
        @forelse($events as $e)
          <div class="p-4 sm:p-5">
            <div class="flex flex-wrap items-start gap-2 justify-between">
              <div class="min-w-0">
                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-violet-50 text-violet-700">{{ strtoupper($e->category) }}</span>
                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full {{ $e->visibility === 'public' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $e->visibility === 'public' ? 'Publik' : 'Internal' }}</span>
                <h2 class="font-bold text-slate-900 mt-1.5">{{ $e->title }}</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  {{ $e->starts_at ? $e->starts_at->format('d M Y H:i') : 'Jadwal menyusul' }}{{ $e->ends_at ? ' – '.$e->ends_at->format('d M Y H:i') : '' }}{{ $e->location ? ' · '.$e->location : '' }}
                </p>
              </div>
              <a href="{{ route('events.show', $e) }}" class="text-[12px] font-bold text-[var(--ypl-navy)] hover:underline shrink-0">Lihat ↗</a>
            </div>
            <details class="mt-3">
              <summary class="cursor-pointer text-[13px] font-bold text-[var(--ypl-navy)] hover:underline ml-auto w-fit">Ubah ▾</summary>
              <form method="post" action="{{ route('staff.events.update', $e) }}" class="mt-3 rounded-2xl bg-slate-50 border border-[var(--ypl-gray)] p-4 grid sm:grid-cols-2 gap-3">@csrf @method('PUT')
                <div class="sm:col-span-2"><label class="text-[12px] font-bold">Judul *</label><input name="title" required value="{{ old('title', $e->title) }}" maxlength="255" class="field mt-1"></div>
                <div class="sm:col-span-2"><label class="text-[12px] font-bold">Deskripsi</label><textarea name="description" rows="2" class="field mt-1">{{ old('description', $e->description) }}</textarea></div>
                <div><label class="text-[12px] font-bold">Kategori *</label><select name="category" class="field mt-1">@foreach(\App\Models\Event::CATEGORIES as $c)<option value="{{ $c }}" @selected(old('category', $e->category) === $c)>{{ ucfirst($c) }}</option>@endforeach</select></div>
                <div><label class="text-[12px] font-bold">Visibilitas *</label><select name="visibility" class="field mt-1"><option value="public" @selected(old('visibility', $e->visibility) === 'public')>Publik</option><option value="internal" @selected(old('visibility', $e->visibility) === 'internal')>Internal</option></select></div>
                <div><label class="text-[12px] font-bold">Mulai *</label><input type="datetime-local" name="starts_at" required value="{{ old('starts_at', $e->starts_at?->format('Y-m-d\TH:i')) }}" class="field mt-1"></div>
                <div><label class="text-[12px] font-bold">Selesai</label><input type="datetime-local" name="ends_at" value="{{ old('ends_at', $e->ends_at?->format('Y-m-d\TH:i')) }}" class="field mt-1"></div>
                <div><label class="text-[12px] font-bold">Lokasi</label><input name="location" value="{{ old('location', $e->location) }}" maxlength="120" class="field mt-1"></div>
                <div class="sm:col-span-2 flex items-center justify-between gap-2">
                  <button class="btn-primary !py-2">Simpan</button>
                  <button form="del-event-{{ $e->id }}" class="text-[13px] font-bold border border-[var(--ypl-red)]/30 text-[var(--ypl-red)] rounded-full px-3 py-1.5 hover:bg-[var(--ypl-red)]/5">Hapus</button>
                </div>
              </form>
              <form id="del-event-{{ $e->id }}" method="post" action="{{ route('staff.events.destroy', $e) }}" data-confirm="Hapus agenda {{ $e->title }}? Tautan dokumen pada agenda ini ikut dilepas.">@csrf @method('DELETE')</form>
            </details>
          </div>
        @empty
          <p class="text-sm text-slate-400 p-8 text-center">Belum ada agenda. Tambahkan lewat kolom form di samping.</p>
        @endforelse
      </div>
    </div>
  </div>

  <div>
    <div class="bg-white rounded-2xl border border-[var(--ypl-gray)] shadow-sm p-5 h-fit">
      <h2 class="font-extrabold mb-3 text-[var(--ypl-navy)]">Tambah Agenda</h2>
      <form method="post" action="{{ route('staff.events.store') }}" class="space-y-3">@csrf
        <div><label class="text-[13px] font-bold">Judul *</label><input name="title" required maxlength="255" class="field mt-1"></div>
        <div><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="2" class="field mt-1"></textarea></div>
        <div><label class="text-[13px] font-bold">Kategori *</label><select name="category" class="field mt-1">@foreach(\App\Models\Event::CATEGORIES as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach</select></div>
        <div><label class="text-[13px] font-bold">Visibilitas *</label><select name="visibility" class="field mt-1"><option value="public">Publik</option><option value="internal">Internal</option></select></div>
        <div><label class="text-[13px] font-bold">Mulai *</label><input type="datetime-local" name="starts_at" required class="field mt-1"></div>
        <div><label class="text-[13px] font-bold">Selesai</label><input type="datetime-local" name="ends_at" class="field mt-1"></div>
        <div><label class="text-[13px] font-bold">Lokasi</label><input name="location" maxlength="120" class="field mt-1"></div>
        <button class="btn-primary">Tambah Agenda</button>
      </form>
    </div>

    <a href="{{ route('calendar.index') }}" class="btn-ghost w-full mt-3 justify-center">@include('components.icon', ['name' => 'book', 'class' => 'w-4 h-4']) Lihat Kalender Publik</a>
  </div>
</div>
@endsection