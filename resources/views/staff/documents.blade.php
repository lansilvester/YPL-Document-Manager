@extends('layout')
@section('title', 'Ruang Kerja Staff')
@section('content')
<div class="rounded-3xl p-6 sm:p-7 text-white shadow-xl shadow-emerald-900/20 mb-6" style="background:linear-gradient(120deg,#052e22,#065f46 60%,#0f766e)">
  <h1 class="text-2xl font-extrabold tracking-tight">🛠 Ruang Kerja Staff</h1>
  <p class="text-sm text-emerald-100/90">Halo, <strong>{{ auth()->user()->name }}</strong> — kelola perpustakaan dalam satu tampilan: tambah/upload PDF, ganti file, beri badge/tag, dan hapus.</p>
  <form action="{{ route('staff.documents') }}" method="get" class="mt-4 flex gap-2 bg-white rounded-full p-1.5 max-w-md">
    <input name="q" value="{{ $q }}" placeholder="Cari dokumen…" class="flex-1 bg-transparent text-slate-800 text-sm px-4 outline-none min-w-0">
    <button class="shrink-0 rounded-full font-bold text-sm px-5 py-2 text-white" style="background:linear-gradient(135deg,#059669,#0d9488)">Cari</button>
  </form>
</div>

<div class="grid lg:grid-cols-3 gap-5">
  {{-- Kolom utama: tambah + tabel CRUD --}}
  <div class="lg:col-span-2 space-y-5">
    <details class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" {{ $errors->any() ? 'open' : '' }}>
      <summary class="cursor-pointer p-5 font-extrabold flex items-center gap-3 hover:bg-emerald-50/50">
        <span class="w-9 h-9 rounded-xl grid place-items-center text-white" style="background:linear-gradient(135deg,#059669,#0d9488)">＋</span>
        Tambah / Upload PDF Baru
      </summary>
      <form method="post" action="{{ route('staff.documents.store') }}" enctype="multipart/form-data" class="p-5 pt-0 space-y-3 border-t border-slate-100">@csrf
        <div class="grid sm:grid-cols-2 gap-3 pt-4">
          <div class="sm:col-span-2"><label class="text-[13px] font-bold">Judul *</label><input name="title" required value="{{ old('title') }}" class="field mt-1"></div>
          <div class="sm:col-span-2"><label class="text-[13px] font-bold">Deskripsi</label><textarea name="description" rows="2" class="field mt-1">{{ old('description') }}</textarea></div>
          <div><label class="text-[13px] font-bold">Kategori</label><select name="category_id" class="field mt-1"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
          <div><label class="text-[13px] font-bold">Visibilitas *</label><select name="visibility" class="field mt-1"><option value="public">🌍 Publik</option><option value="restricted">🔒 Terbatas</option><option value="private">🙈 Privat</option></select></div>
          <div class="sm:col-span-2">
            <label class="text-[13px] font-bold">Badge / Tag</label>
            <div class="flex flex-wrap gap-2 mt-1.5">@foreach($tags as $t)<label class="text-xs font-bold border border-slate-200 rounded-full px-3 py-1.5 cursor-pointer has-checked:bg-emerald-600 has-checked:text-white has-checked:border-emerald-600"><input type="checkbox" name="tags[]" value="{{ $t->id }}" class="sr-only">🏷 {{ $t->name }}</label>@endforeach</div>
          </div>
          <div class="sm:col-span-2"><label class="text-[13px] font-bold">File PDF *</label><input type="file" name="file" accept="application/pdf" required class="field mt-1 file:mr-3 file:rounded-full file:border-0 file:bg-emerald-100 file:text-emerald-800 file:text-xs file:font-bold file:px-4 file:py-2"></div>
        </div>
        <button class="btn-primary">⬆ Upload ke Perpustakaan</button>
      </form>
    </details>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-4 border-b border-slate-100 font-extrabold">📚 Semua Dokumen ({{ $docs->total() }})</div>
      <div class="divide-y divide-slate-100">
        @forelse($docs as $d)
          <div class="p-4">
            <div class="flex flex-wrap items-center gap-3">
              <div class="flex-1 min-w-48">
                <a class="font-bold hover:text-emerald-700" href="{{ route('documents.show', $d) }}">{{ $d->title }}</a>
                <div class="text-xs text-slate-400 mt-0.5">{{ $d->category->name ?? 'Tanpa kategori' }} · {{ ucfirst($d->visibility) }} · oleh {{ $d->user->name }} · 👁 {{ number_format($d->views_count) }}</div>
                @if($d->tags->isNotEmpty())<div class="flex flex-wrap gap-1 mt-1.5">@include('components.tag-badges', ['tags' => $d->tags])</div>@endif
              </div>
            </div>
            {{-- Panel kelola: ganti file, ubah data, atur badge, hapus --}}
            <details class="mt-2">
              <summary class="text-[13px] font-bold text-emerald-700 cursor-pointer hover:underline">⚙ Ubah / Ganti File / Atur Badge</summary>
              <form method="post" action="{{ route('staff.documents.update', $d) }}" enctype="multipart/form-data" class="mt-3 rounded-2xl bg-slate-50 border border-slate-200 p-4 grid sm:grid-cols-2 gap-3">@csrf @method('PUT')
                <div class="sm:col-span-2"><label class="text-xs font-bold">Judul *</label><input name="title" required value="{{ $d->title }}" class="field mt-1 !py-2 !text-[13px]"></div>
                <div class="sm:col-span-2"><label class="text-xs font-bold">Deskripsi</label><textarea name="description" rows="2" class="field mt-1 !py-2 !text-[13px]">{{ $d->description }}</textarea></div>
                <div><label class="text-xs font-bold">Kategori</label><select name="category_id" class="field mt-1 !py-2 !text-[13px]"><option value="">— Tanpa kategori —</option>@foreach($categories as $c)<option value="{{ $c->id }}" {{ $d->category_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select></div>
                <div><label class="text-xs font-bold">Visibilitas</label><select name="visibility" class="field mt-1 !py-2 !text-[13px]">@foreach(['public' => '🌍 Publik', 'restricted' => '🔒 Terbatas', 'private' => '🙈 Privat'] as $v => $l)<option value="{{ $v }}" {{ $d->visibility === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
                <div class="sm:col-span-2">
                  <label class="text-xs font-bold">Badge / Tag / Flag</label>
                  <div class="flex flex-wrap gap-1.5 mt-1.5">
                    @foreach($tags as $t)<label class="text-xs font-bold border rounded-full px-3 py-1 cursor-pointer {{ $d->tags->contains($t->id) ? 'bg-emerald-600 text-white border-emerald-600' : 'border-slate-200 bg-white' }}"><input type="checkbox" name="tags[]" value="{{ $t->id }}" class="sr-only" {{ $d->tags->contains($t->id) ? 'checked' : '' }}>🏷 {{ $t->name }}</label>@endforeach
                  </div>
                </div>
                <div class="sm:col-span-2"><label class="text-xs font-bold">Ganti file PDF <span class="font-normal text-slate-400">(kosongkan jika tidak diganti · saat ini: {{ $d->file_path ? basename($d->file_path).' ('.round(($d->file_size ?? 0)/1048576, 1).' MB)' : '—' }})</span></label><input type="file" name="file" accept="application/pdf" class="field mt-1 !py-2 !text-[13px] file:mr-3 file:rounded-full file:border-0 file:bg-emerald-100 file:text-emerald-800 file:text-xs file:font-bold file:px-4 file:py-1.5"></div>
                <div class="sm:col-span-2 flex gap-2">
                  <button class="btn-primary !py-2">💾 Simpan Perubahan</button>
                </div>
              </form>
              <form method="post" action="{{ route('staff.documents.destroy', $d) }}" onsubmit="return confirm('Hapus permanen “{{ addslashes($d->title) }}” dari perpustakaan?')" class="mt-2">@csrf @method('DELETE')<button class="text-[13px] font-bold text-red-600 hover:underline">🗑 Hapus dokumen ini</button></form>
            </details>
          </div>
        @empty
          <p class="p-8 text-center text-slate-400 text-sm">Tidak ada dokumen. Tambahkan yang pertama lewat formulir di atas.</p>
        @endforelse
      </div>
      <div class="p-4 border-t border-slate-100">{{ $docs->links() }}</div>
    </div>
  </div>

  {{-- Kolom badge --}}
  <aside class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
      <h2 class="font-extrabold">🏷 Kelola Badge / Tag</h2>
      <p class="text-xs text-slate-400 mb-3">Badge tampil di kartu & halaman dokumen.</p>
      <div class="flex flex-wrap gap-1.5 mb-4">
        @forelse($tags as $t)
          <span class="inline-flex items-center gap-1.5">
            @include('components.tag-badges', ['tags' => collect([$t])])
            <form method="post" action="{{ route('staff.tags.destroy', $t) }}" onsubmit="return confirm('Hapus badge {{ addslashes($t->name) }}?')" class="inline">@csrf @method('DELETE')<button class="text-slate-300 hover:text-red-500 text-sm" title="Hapus">×</button></form>
          </span>
        @empty
          <span class="text-xs text-slate-400">Belum ada badge.</span>
        @endforelse
      </div>
      <form method="post" action="{{ route('staff.tags.store') }}" class="space-y-2">@csrf
        <input name="name" required maxlength="50" placeholder="Nama badge, cth: Unggulan" class="field !py-2 !text-[13px]">
        <div class="flex gap-2">
          <select name="color" class="field !py-2 !text-[13px]">@foreach(\App\Models\Tag::COLORS as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach</select>
          <button class="btn-primary !py-2 shrink-0">＋ Tambah</button>
        </div>
      </form>
    </div>
    <div class="rounded-2xl p-5 text-white text-sm" style="background:linear-gradient(135deg,#0c4a6e,#0284c7)">
      <div class="font-extrabold mb-1">💡 Alur kerja Staff</div>
      <ol class="list-decimal ml-4 space-y-1 text-sky-50 text-[13px]">
        <li>Upload PDF + isi judul & kategori.</li>
        <li>Beri badge (mis. “Baru”, “Unggulan”).</li>
        <li>Atur visibilitas: Publik / Terbatas / Privat.</li>
        <li>Perbarui file kapan saja tanpa menghapus.</li>
      </ol>
    </div>
  </aside>
</div>
@endsection
