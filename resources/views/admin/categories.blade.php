@extends('layout')
@section('title', 'Kelola Kategori')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dasbor Admin', route('admin.dashboard')], ['Kelola Kategori']]])
<h1 class="text-2xl font-extrabold tracking-tight text-[#002f80] mb-1">Kelola Handbook & Koleksi</h1>
<p class="text-sm text-slate-500 mb-5">10 Handbook B01–B10 adalah entitas resmi — hanya admin yang dapat mengubahnya. Slug dijaga stabil agar URL bookshelf tidak rusak.</p>

<h2 class="font-extrabold text-[#002f80] mb-2">10 Living Handbook</h2>
<div class="grid md:grid-cols-2 gap-4 mb-8">
  @foreach($handbooks as $h)
    <details class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm overflow-hidden group">
      <summary class="cursor-pointer list-none flex items-center gap-3 p-4">
        <span class="shrink-0 text-xs font-extrabold bg-[#002f80] text-white px-2.5 py-1 rounded-full">{{ $h->code }}</span>
        <span class="flex-1 min-w-0"><span class="block font-bold truncate">{{ $h->name }}</span><span class="block text-xs text-slate-400 truncate">{{ $h->description }} · {{ $h->documents_count }} dokumen</span></span>
        <span class="text-xs font-bold text-[#002f80]">Ubah ▾</span>
      </summary>
      <form method="post" action="{{ route('admin.categories.update', $h) }}" class="border-t border-slate-100 p-4 grid sm:grid-cols-2 gap-3">@csrf @method('PUT')
        <div><label class="text-[12px] font-bold">Kode *</label><input name="code" required value="{{ old('code', $h->code) }}" maxlength="10" class="field mt-1"></div>
        <div><label class="text-[12px] font-bold">Urutan</label><input type="number" name="sort_order" value="{{ old('sort_order', $h->sort_order) }}" min="0" max="999" class="field mt-1"></div>
        <div class="sm:col-span-2"><label class="text-[12px] font-bold">Nama handbook *</label><input name="name" required value="{{ old('name', $h->name) }}" maxlength="100" class="field mt-1"></div>
        <div class="sm:col-span-2"><label class="text-[12px] font-bold">Primary role / deskripsi</label><textarea name="description" rows="2" class="field mt-1">{{ old('description', $h->description) }}</textarea></div>
        <div><label class="text-[12px] font-bold">Koleksi</label><select name="collection" class="field mt-1">@foreach(['handbook', 'governance', 'academic', 'boarding', 'staff', 'parent', 'public'] as $col)<option value="{{ $col }}" {{ old('collection', $h->collection) === $col ? 'selected' : '' }}>{{ ucfirst($col) }}</option>@endforeach</select></div>
        <div><label class="text-[12px] font-bold">Warna cover</label><select name="color" class="field mt-1">@foreach(['navy', 'blue', 'red', 'deep-navy', 'purple', 'slate', 'teal', 'violet', 'charcoal', 'brown'] as $col)<option value="{{ $col }}" {{ old('color', $h->color) === $col ? 'selected' : '' }}>{{ $col }}</option>@endforeach</select></div>
        <div class="sm:col-span-2"><button class="btn-primary w-full !py-2">Simpan {{ $h->code }}</button></div>
      </form>
    </details>
  @endforeach
</div>

<div class="grid md:grid-cols-2 gap-5">
  <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5">
    <h2 class="font-extrabold mb-2 text-[#002f80]">Koleksi Lain</h2>
    <ul class="divide-y divide-slate-100 text-sm">
      @forelse($others as $c)
        <li class="py-2.5 flex items-center gap-2">
          <span class="w-8 h-8 rounded-xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,#001c55,#0046c0)">@include('components.icon', ['name' => 'folder', 'class' => 'w-4 h-4'])</span>
          <span class="flex-1"><strong>{{ $c->name }}</strong><br><span class="text-slate-400 text-xs">{{ $c->collection ?? '—' }} · {{ $c->documents_count }} dokumen</span></span>
          @if($c->documents_count === 0)
            <form method="post" action="{{ route('admin.categories.destroy', $c) }}" data-confirm="Hapus koleksi ini?">@csrf @method('DELETE')<button class="text-[13px] font-bold border border-[#c90018]/30 text-[#c90018] rounded-full px-3 py-1 hover:bg-[#c90018]/5 inline-flex items-center gap-1">@include('components.icon', ['name' => 'trash', 'class' => 'w-3.5 h-3.5']) Hapus</button></form>
          @else
            <span class="text-[11px] font-bold text-slate-400" title="Berisi dokumen — tidak dapat dihapus">🔒</span>
          @endif
        </li>
      @empty
        <li class="text-slate-400 py-4">Belum ada koleksi lain.</li>
      @endforelse
    </ul>
  </div>
  <div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm p-5 h-fit">
    <h2 class="font-extrabold mb-3 text-[#002f80]">Tambah Koleksi</h2>
    <form method="post" action="{{ route('admin.categories.store') }}" class="space-y-3">@csrf
      <div><label class="text-[13px] font-bold">Nama *</label><input name="name" required placeholder="cth: Koleksi Riset" class="field mt-1"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-[13px] font-bold">Kode</label><input name="code" maxlength="10" placeholder="opsional" class="field mt-1"></div>
        <div><label class="text-[13px] font-bold">Urutan</label><input type="number" name="sort_order" min="0" max="999" class="field mt-1"></div>
      </div>
      <div><label class="text-[13px] font-bold">Koleksi</label><select name="collection" class="field mt-1"><option value="">— Umum —</option>@foreach(['handbook', 'governance', 'academic', 'boarding', 'staff', 'parent', 'public'] as $col)<option value="{{ $col }}">{{ ucfirst($col) }}</option>@endforeach</select></div>
      <div><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="3" class="field mt-1"></textarea></div>
      <button class="btn-primary">Tambah Koleksi</button>
    </form>
  </div>
</div>
@endsection
