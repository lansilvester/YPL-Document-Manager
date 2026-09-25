@extends('layout')
@section('title', 'Dokumen Saya')
@section('content')
<div class="flex items-center justify-between flex-wrap gap-3 mb-5">
  <div>
    <h1 class="text-2xl font-extrabold tracking-tight">Kelola Dokumen Saya</h1>
    <p class="text-sm text-slate-500">{{ $docs->total() }} dokumen milikmu.</p>
  </div>
  <div class="flex gap-2 text-[13px] font-bold">
    <a href="{{ route('documents.create') }}" class="btn-primary">⬆ Unggah Baru</a>
    <a href="{{ route('editor.create') }}" class="btn-ghost">✍ Tulis Baru</a>
  </div>
</div>
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm divide-y divide-slate-100 overflow-hidden">
  @forelse($docs as $d)
    <div class="p-4 flex flex-wrap items-center gap-3 hover:bg-emerald-50/40 transition">
      <span class="w-11 h-14 rounded-lg grid place-items-center text-white font-extrabold text-lg shrink-0 shadow" style="background:linear-gradient(160deg,#065f46,#0d9488)">📄</span>
      <div class="flex-1 min-w-52">
        <a class="font-bold hover:text-emerald-700" href="{{ route('documents.show', $d) }}">{{ $d->title }}</a>
        <div class="text-xs text-slate-400 mt-0.5">{{ $d->category->name ?? 'Tanpa kategori' }} · {{ ucfirst($d->visibility) }} · 👁 {{ number_format($d->views_count) }} · {{ $d->created_at->format('d M Y') }}</div>
      </div>
      @if($d->source_type === 'editor')
        <a href="{{ route('editor.edit', $d) }}" class="text-[13px] font-bold border border-slate-200 rounded-full px-4 py-1.5 hover:border-emerald-500 hover:text-emerald-700">Ubah Isi</a>
      @else
        <a href="{{ route('documents.edit', $d) }}" class="text-[13px] font-bold border border-slate-200 rounded-full px-4 py-1.5 hover:border-emerald-500 hover:text-emerald-700">Ubah</a>
      @endif
      <form method="post" action="{{ route('documents.destroy', $d) }}" onsubmit="return confirm('Hapus dokumen ini?')">@csrf @method('DELETE')<button class="text-[13px] font-bold border border-red-200 text-red-600 rounded-full px-4 py-1.5 hover:bg-red-50">Hapus</button></form>
    </div>
  @empty
    <div class="p-10 text-center">
      <div class="text-4xl">📝</div>
      <p class="font-bold mt-2">Belum ada dokumen</p>
      <p class="text-sm text-slate-500">Mulai dengan <a class="text-emerald-700 underline font-semibold" href="{{ route('documents.create') }}">mengunggah PDF</a> atau <a class="text-emerald-700 underline font-semibold" href="{{ route('editor.create') }}">menulis dokumen</a>.</p>
    </div>
  @endforelse
</div>
<div class="mt-4">{{ $docs->links() }}</div>
@endsection
