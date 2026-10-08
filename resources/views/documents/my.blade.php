@extends('layout')
@section('title', 'Dokumen Saya')
@section('content')
@include('components.back-bar', ['trail' => [['Beranda', route('home')], ['Dokumen Saya']]])
<div class="flex items-center justify-between flex-wrap gap-3 mb-5">
  <div class="flex items-center gap-3">
    <span class="w-11 h-11 rounded-2xl grid place-items-center text-white shrink-0" style="background:linear-gradient(135deg,#001c55,#0046c0)">@include('components.icon', ['name' => 'folder', 'class' => 'w-5 h-5'])</span>
    <div>
      <h1 class="page-title">Kelola Dokumen Saya</h1>
      <p class="text-sm text-slate-500">{{ $docs->total() }} dokumen milikmu.</p>
    </div>
  </div>
  <div class="flex gap-2 text-[13px] font-bold">
    <a href="{{ route('documents.create') }}" class="btn-primary">Unggah Baru</a>
    <a href="{{ route('editor.create') }}" class="btn-ghost">Tulis Baru</a>
  </div>
</div>
<div class="bg-white rounded-2xl border border-[#d6d6d6] shadow-sm divide-y divide-slate-100 overflow-hidden">
  @forelse($docs as $d)
    <div class="p-4 flex flex-wrap items-center gap-3 hover:bg-[#002f80]/[.03] transition">
      <span class="w-11 h-14 rounded-lg grid place-items-center text-white shrink-0 shadow" style="background:linear-gradient(160deg,#001c55,#0046c0)">@include('components.icon', ['name' => 'file', 'class' => 'w-5 h-5'])</span>
      <div class="flex-1 min-w-52">
        <a class="font-bold hover:text-[#002f80]" href="{{ route('documents.show', $d) }}">{{ $d->title }}</a>
        <div class="text-xs text-slate-400 mt-0.5">{{ $d->category->name ?? 'Tanpa kategori' }} · {{ ucfirst($d->visibility) }} · {{ number_format($d->views_count) }} dibaca · {{ $d->created_at->format('d M Y') }}</div>
      </div>
      @if($d->source_type === 'editor')
        <a href="{{ route('editor.edit', $d) }}" class="text-[13px] font-bold border border-[#d6d6d6] rounded-full px-4 py-1.5 hover:border-[#002f80] hover:text-[#002f80] inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'pen', 'class' => 'w-3.5 h-3.5']) Ubah Isi</a>
      @else
        <a href="{{ route('documents.edit', $d) }}" class="text-[13px] font-bold border border-[#d6d6d6] rounded-full px-4 py-1.5 hover:border-[#002f80] hover:text-[#002f80] inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'pen', 'class' => 'w-3.5 h-3.5']) Ubah</a>
      @endif
      <form method="post" action="{{ route('documents.destroy', $d) }}" data-confirm="Hapus dokumen ini secara permanen?">@csrf @method('DELETE')<button class="text-[13px] font-bold border border-[#c90018]/30 text-[#c90018] rounded-full px-4 py-1.5 hover:bg-[#c90018]/5 inline-flex items-center gap-1.5">@include('components.icon', ['name' => 'trash', 'class' => 'w-3.5 h-3.5']) Hapus</button></form>
    </div>
  @empty
    <div class="p-10 text-center text-slate-400">
      <span class="inline-block">@include('components.icon', ['name' => 'file', 'class' => 'w-12 h-12'])</span>
      <p class="font-bold mt-2 text-slate-700">Belum ada dokumen</p>
      <p class="text-sm text-slate-500">Mulai dengan <a class="text-[#002f80] underline font-semibold" href="{{ route('documents.create') }}">mengunggah PDF</a> atau <a class="text-[#002f80] underline font-semibold" href="{{ route('editor.create') }}">menulis dokumen</a>.</p>
    </div>
  @endforelse
</div>
<div class="mt-4">{{ $docs->links() }}</div>
@endsection
