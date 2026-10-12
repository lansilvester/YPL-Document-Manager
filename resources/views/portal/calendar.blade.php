@extends('layout')
@section('title', 'Kalender YPL')
@section('content')
<p class="page-eyebrow text-[#c90018] mt-2">CALENDAR</p>
<h1 class="page-title">Kalender YPL</h1>
<p class="text-sm text-slate-500 mt-1">Agenda foundation, akademik, asrama, event, dan deadline penting.</p>

<div class="flex flex-wrap gap-2 mt-4" role="group" aria-label="Filter kategori">
  <a href="{{ route('calendar.index') }}" class="px-4 py-2 rounded-full text-xs font-bold {{ ! $kategori ? 'bg-[#002f80] text-white' : 'bg-white border border-[#d6d6d6] hover:border-[#002f80]' }}">Semua</a>
  @foreach($categories as $c)
    <a href="{{ route('calendar.index', ['kategori' => $c]) }}" class="px-4 py-2 rounded-full text-xs font-bold {{ $kategori === $c ? 'bg-[#002f80] text-white' : 'bg-white border border-[#d6d6d6] hover:border-[#002f80]' }}">{{ strtoupper($c) }}</a>
  @endforeach
</div>

<div class="grid sm:grid-cols-2 gap-3 mt-4">
  @forelse($events as $e)
    <a href="{{ route('events.show', $e) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block group">
      <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-violet-50 text-violet-700">{{ strtoupper($e->category) }}</span>
      <div class="font-bold text-sm text-slate-900 leading-snug mt-2 group-hover:text-[#002f80]">{{ $e->title }}</div>
      <div class="text-[11px] text-slate-400 mt-1">{{ $e->starts_at ? $e->starts_at->format('d M Y H:i') : 'Jadwal menyusul' }}{{ $e->location ? ' · '.$e->location : '' }}</div>
    </a>
  @empty
    <p class="text-sm text-slate-400 sm:col-span-2 bg-white rounded-2xl border border-dashed p-8 text-center">Belum ada agenda pada kategori ini.</p>
  @endforelse
</div>
<div class="mt-4">{{ $events->links() }}</div>

@if($todayDocs->isNotEmpty())
  <h2 class="section-label mt-6 mb-2">YPL TODAY — JATUH TEMPO TINJAUAN</h2>
  <div class="grid sm:grid-cols-2 gap-3">
    @foreach($todayDocs as $t)
      <a href="{{ route('governance.show', $t) }}" class="card-hover bg-white rounded-2xl border border-[#d6d6d6] p-4 block group">
        <div class="font-bold text-sm text-slate-900 leading-snug group-hover:text-[#002f80]">{{ $t->title }}</div>
        <div class="text-[11px] text-slate-400 mt-1">Tinjau sebelum {{ $t->review_at->format('d M Y') }} · {{ $t->category->name ?? 'Tanpa kategori' }}</div>
      </a>
    @endforeach
  </div>
@endif
@endsection
